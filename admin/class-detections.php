<?php
if (!defined('ABSPATH')) exit;

class AIPDB_Detections {
    
    public function __construct() {
        add_action('wp_ajax_aipdb_get_detections', array($this, 'ajax_get_detections'));
        add_action('wp_ajax_aipdb_delete_detection', array($this, 'ajax_delete_detection'));
        add_action('wp_ajax_aipdb_bulk_action', array($this, 'ajax_bulk_action'));
    }
    
    public function get_detections($args = array()) {
        global $wpdb;
        
        $defaults = array(
            'per_page' => 20,
            'page' => 1,
            'orderby' => 'created_at',
            'order' => 'DESC',
            'search' => '',
            'event_type' => '',
            'threat_level' => '',
            'date_from' => '',
            'date_to' => ''
        );
        
        $args = wp_parse_args($args, $defaults);
        
        $table_name = $wpdb->prefix . 'aipdb_detections';
        $where_clauses = array();
        $where_values = array();
        
        // Filtros
        if (!empty($args['search'])) {
            $where_clauses[] = "(ip_address LIKE %s OR user_agent LIKE %s OR request_uri LIKE %s)";
            $search_term = '%' . $wpdb->esc_like($args['search']) . '%';
            $where_values[] = $search_term;
            $where_values[] = $search_term;
            $where_values[] = $search_term;
        }
        
        if (!empty($args['event_type'])) {
            $where_clauses[] = "event_type = %s";
            $where_values[] = $args['event_type'];
        }
        
        if (!empty($args['threat_level'])) {
            $where_clauses[] = "threat_level = %s";
            $where_values[] = $args['threat_level'];
        }
        
        if (!empty($args['date_from'])) {
            $where_clauses[] = "DATE(created_at) >= %s";
            $where_values[] = $args['date_from'];
        }
        
        if (!empty($args['date_to'])) {
            $where_clauses[] = "DATE(created_at) <= %s";
            $where_values[] = $args['date_to'];
        }
        
        // Construir WHERE clause
        $where_sql = '';
        if (!empty($where_clauses)) {
            $where_sql = 'WHERE ' . implode(' AND ', $where_clauses);
        }
        
        // Contar total
        $count_sql = "SELECT COUNT(*) FROM $table_name $where_sql";
        if (!empty($where_values)) {
            $count_sql = $wpdb->prepare($count_sql, $where_values);
        }
        $total = $wpdb->get_var($count_sql);
        
        // Obtener resultados con paginación
        $offset = ($args['page'] - 1) * $args['per_page'];
        $order_by = sanitize_sql_orderby($args['orderby'] . ' ' . $args['order']);
        
        $sql = "SELECT * FROM $table_name $where_sql ORDER BY $order_by LIMIT %d OFFSET %d";
        $sql_values = array_merge($where_values, array($args['per_page'], $offset));
        
        $results = $wpdb->get_results($wpdb->prepare($sql, $sql_values));
        
        return array(
            'data' => $results,
            'total' => $total,
            'pages' => ceil($total / $args['per_page']),
            'current_page' => $args['page']
        );
    }
    
    public function ajax_get_detections() {
        check_ajax_referer('aipdb_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Permission denied', 'wp-abuseipdb-integration'));
        }
        
        $args = array(
            'per_page' => intval($_POST['per_page'] ?? 20),
            'page' => intval($_POST['page'] ?? 1),
            'orderby' => sanitize_text_field($_POST['orderby'] ?? 'created_at'),
            'order' => sanitize_text_field($_POST['order'] ?? 'DESC'),
            'search' => sanitize_text_field($_POST['search'] ?? ''),
            'event_type' => sanitize_text_field($_POST['event_type'] ?? ''),
            'threat_level' => sanitize_text_field($_POST['threat_level'] ?? ''),
            'date_from' => sanitize_text_field($_POST['date_from'] ?? ''),
            'date_to' => sanitize_text_field($_POST['date_to'] ?? '')
        );
        
        $detections = $this->get_detections($args);
        
        wp_send_json_success($detections);
    }
    
    public function ajax_delete_detection() {
        check_ajax_referer('aipdb_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Permission denied', 'wp-abuseipdb-integration'));
        }
        
        global $wpdb;
        $detection_id = intval($_POST['id']);
        $table_name = $wpdb->prefix . 'aipdb_detections';
        
        $result = $wpdb->delete($table_name, array('id' => $detection_id), array('%d'));
        
        if ($result !== false) {
            wp_send_json_success(array('message' => __('Detection deleted', 'wp-abuseipdb-integration')));
        } else {
            wp_send_json_error(array('message' => __('Failed to delete detection', 'wp-abuseipdb-integration')));
        }
    }
    
    public function ajax_bulk_action() {
        check_ajax_referer('aipdb_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Permission denied', 'wp-abuseipdb-integration'));
        }
        
        $action = sanitize_text_field($_POST['bulk_action']);
        $ids = array_map('intval', $_POST['ids'] ?? array());
        
        if (empty($ids)) {
            wp_send_json_error(array('message' => __('No items selected', 'wp-abuseipdb-integration')));
        }
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'aipdb_detections';
        $placeholders = implode(',', array_fill(0, count($ids), '%d'));
        
        switch ($action) {
            case 'delete':
                $result = $wpdb->query($wpdb->prepare(
                    "DELETE FROM $table_name WHERE id IN ($placeholders)",
                    $ids
                ));
                
                if ($result !== false) {
                    wp_send_json_success(array('message' => sprintf(__('%d detections deleted', 'wp-abuseipdb-integration'), $result)));
                } else {
                    wp_send_json_error(array('message' => __('Failed to delete detections', 'wp-abuseipdb-integration')));
                }
                break;
                
            case 'whitelist_ip':
                $ips_sql = "SELECT DISTINCT ip_address FROM $table_name WHERE id IN ($placeholders)";
                $ips = $wpdb->get_col($wpdb->prepare($ips_sql, $ids));
                
                $current_whitelist = get_option('aipdb_whitelist_ips', '');
                $whitelist_array = array_filter(explode("\n", $current_whitelist));
                $whitelist_array = array_merge($whitelist_array, $ips);
                $whitelist_array = array_unique($whitelist_array);
                
                update_option('aipdb_whitelist_ips', implode("\n", $whitelist_array));
                
                wp_send_json_success(array('message' => sprintf(__('%d IPs added to whitelist', 'wp-abuseipdb-integration'), count($ips))));
                break;
                
            default:
                wp_send_json_error(array('message' => __('Invalid action', 'wp-abuseipdb-integration')));
        }
    }
}
