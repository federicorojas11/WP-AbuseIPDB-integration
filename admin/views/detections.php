<?php
if (!defined('ABSPATH')) exit;

$detections_handler = new AIPDB_Detections();
$detections = $detections_handler->get_detections(array(
    'per_page' => 20,
    'page' => 1
));
?>

<div class="aipdb-detections-page">
    <!-- Filtros -->
    <div class="aipdb-detections-filters">
        <div class="aipdb-filter-row">
            <div class="aipdb-filter-group">
                <label for="detection-search"><?php _e('Search:', 'wp-abuseipdb-integration'); ?></label>
                <input type="text" id="detection-search" placeholder="<?php _e('IP address, User Agent, URI...', 'wp-abuseipdb-integration'); ?>" />
            </div>
            
            <div class="aipdb-filter-group">
                <label for="event-type-filter"><?php _e('Event Type:', 'wp-abuseipdb-integration'); ?></label>
                <select id="event-type-filter">
                    <option value=""><?php _e('All Types', 'wp-abuseipdb-integration'); ?></option>
                    <option value="login_failure"><?php _e('Login Failure', 'wp-abuseipdb-integration'); ?></option>
                    <option value="suspicious_request"><?php _e('Suspicious Request', 'wp-abuseipdb-integration'); ?></option>
                    <option value="comment_spam"><?php _e('Comment Spam', 'wp-abuseipdb-integration'); ?></option>
                    <option value="rest_api"><?php _e('REST API', 'wp-abuseipdb-integration'); ?></option>
                    <option value="xmlrpc"><?php _e('XML-RPC', 'wp-abuseipdb-integration'); ?></option>
                    <option value="404_error"><?php _e('404 Error', 'wp-abuseipdb-integration'); ?></option>
                </select>
            </div>
            
            <div class="aipdb-filter-group">
                <label for="threat-level-filter"><?php _e('Threat Level:', 'wp-abuseipdb-integration'); ?></label>
                <select id="threat-level-filter">
                    <option value=""><?php _e('All Levels', 'wp-abuseipdb-integration'); ?></option>
                    <option value="low"><?php _e('Low', 'wp-abuseipdb-integration'); ?></option>
                    <option value="medium"><?php _e('Medium', 'wp-abuseipdb-integration'); ?></option>
                    <option value="high"><?php _e('High', 'wp-abuseipdb-integration'); ?></option>
                    <option value="critical"><?php _e('Critical', 'wp-abuseipdb-integration'); ?></option>
                </select>
            </div>
            
            <div class="aipdb-filter-group">
                <label for="date-from"><?php _e('From:', 'wp-abuseipdb-integration'); ?></label>
                <input type="date" id="date-from" />
            </div>
            
            <div class="aipdb-filter-group">
                <label for="date-to"><?php _e('To:', 'wp-abuseipdb-integration'); ?></label>
                <input type="date" id="date-to" />
            </div>
            
            <button type="button" class="button aipdb-filter-apply"><?php _e('Apply Filters', 'wp-abuseipdb-integration'); ?></button>
            <button type="button" class="button aipdb-filter-reset"><?php _e('Reset', 'wp-abuseipdb-integration'); ?></button>
        </div>
    </div>
    
    <!-- Acciones masivas -->
    <div class="aipdb-bulk-actions">
        <select id="bulk-action-selector">
            <option value=""><?php _e('Bulk Actions', 'wp-abuseipdb-integration'); ?></option>
            <option value="delete"><?php _e('Delete', 'wp-abuseipdb-integration'); ?></option>
            <option value="whitelist_ip"><?php _e('Add IP to Whitelist', 'wp-abuseipdb-integration'); ?></option>
        </select>
        <button type="button" class="button aipdb-bulk-apply"><?php _e('Apply', 'wp-abuseipdb-integration'); ?></button>
        
        <div class="aipdb-results-count">
            <span id="detections-count"><?php printf(__('Total: %d detections', 'wp-abuseipdb-integration'), $detections['total']); ?></span>
        </div>
    </div>
    
    <!-- Tabla de detecciones -->
    <div class="aipdb-detections-table-container">
        <table class="wp-list-table widefat fixed striped aipdb-detections-table">
            <thead>
                <tr>
                    <td class="check-column">
                        <input type="checkbox" id="select-all-detections" />
                    </td>
                    <th class="sortable" data-sort="created_at">
                        <a href="#">
                            <?php _e('Date', 'wp-abuseipdb-integration'); ?>
                            <span class="sorting-indicator"></span>
                        </a>
                    </th>
                    <th class="sortable" data-sort="ip_address">
                        <a href="#">
                            <?php _e('IP Address', 'wp-abuseipdb-integration'); ?>
                            <span class="sorting-indicator"></span>
                        </a>
                    </th>
                    <th class="sortable" data-sort="event_type">
                        <a href="#">
                            <?php _e('Event Type', 'wp-abuseipdb-integration'); ?>
                            <span class="sorting-indicator"></span>
                        </a>
                    </th>
                    <th class="sortable" data-sort="threat_level">
                        <a href="#">
                            <?php _e('Threat Level', 'wp-abuseipdb-integration'); ?>
                            <span class="sorting-indicator"></span>
                        </a>
                    </th>
                    <th class="sortable" data-sort="abuseipdb_score">
                        <a href="#">
                            <?php _e('AbuseIPDB Score', 'wp-abuseipdb-integration'); ?>
                            <span class="sorting-indicator"></span>
                        </a>
                    </th>
                    <th><?php _e('Country', 'wp-abuseipdb-integration'); ?></th>
                    <th><?php _e('Action Taken', 'wp-abuseipdb-integration'); ?></th>
                    <th><?php _e('Actions', 'wp-abuseipdb-integration'); ?></th>
                </tr>
            </thead>
            <tbody id="detections-table-body">
                <?php if (!empty($detections['data'])): ?>
                    <?php foreach ($detections['data'] as $detection): ?>
                        <tr data-id="<?php echo intval($detection->id); ?>">
                            <th class="check-column">
                                <input type="checkbox" class="detection-checkbox" value="<?php echo intval($detection->id); ?>" />
                            </th>
                            <td>
                                <?php echo esc_html(mysql2date(__('M j, Y g:i A'), $detection->created_at)); ?>
                            </td>
                            <td>
                                <strong><?php echo esc_html($detection->ip_address); ?></strong>
                                <div class="row-actions">
                                    <span><a href="https://www.abuseipdb.com/check/<?php echo urlencode($detection->ip_address); ?>" target="_blank"><?php _e('Check AbuseIPDB', 'wp-abuseipdb-integration'); ?></a></span>
                                </div>
                            </td>
                            <td>
                                <span class="aipdb-event-type aipdb-event-<?php echo esc_attr($detection->event_type); ?>">
                                    <?php echo esc_html(ucfirst(str_replace('_', ' ', $detection->event_type))); ?>
                                </span>
                            </td>
                            <td>
                                <span class="aipdb-threat-level aipdb-threat-<?php echo esc_attr($detection->threat_level); ?>">
                                    <?php echo esc_html(ucfirst($detection->threat_level)); ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($detection->abuseipdb_score !== null): ?>
                                    <span class="aipdb-score aipdb-score-<?php echo $detection->abuseipdb_score >= 75 ? 'high' : ($detection->abuseipdb_score >= 25 ? 'medium' : 'low'); ?>">
                                        <?php echo intval($detection->abuseipdb_score); ?>%
                                    </span>
                                <?php else: ?>
                                    <span class="aipdb-score-na">N/A</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (!empty($detection->country_code)): ?>
                                    <span class="aipdb-country" title="<?php echo esc_attr(aipdb_get_country_name($detection->country_code)); ?>">
                                        <?php echo esc_html($detection->country_code); ?>
                                    </span>
                                <?php else: ?>
                                    <span class="aipdb-country-unknown">-</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (!empty($detection->action_taken)): ?>
                                    <span class="aipdb-action aipdb-action-<?php echo esc_attr($detection->action_taken); ?>">
                                        <?php echo esc_html(ucfirst(str_replace('_', ' ', $detection->action_taken))); ?>
                                    </span>
                                <?php else: ?>
                                    <span class="aipdb-action-none"><?php _e('None', 'wp-abuseipdb-integration'); ?></span>
                                <?php endif; ?>
                            </td>
                            <td class="aipdb-actions">                                
                                <button type="button" 
                                        class="button button-small aipdb-view-details" 
                                        data-id="<?php echo esc_attr($detection->id); ?>">
                                    <span class="dashicons dashicons-visibility"></span>
                                    <?php _e('Details', 'wp-abuseipdb-integration'); ?>
                                </button>

                                <button type="button" 
                                        class="button button-small button-link-delete aipdb-delete-detection" 
                                        data-id="<?php echo esc_attr($detection->id); ?>"
                                        data-ip="<?php echo esc_attr($detection->ip_address); ?>">
                                    <span class="dashicons dashicons-trash"></span>
                                    <?php _e('Delete', 'wp-abuseipdb-integration'); ?>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr class="no-items">
                        <td class="colspanchange" colspan="9">
                            <?php _e('No detections found.', 'wp-abuseipdb-integration'); ?>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    
    <!-- Paginación -->
    <?php if ($detections['pages'] > 1): ?>
        <div class="aipdb-pagination">
            <span class="displaying-num"><?php printf(__('%d items', 'wp-abuseipdb-integration'), $detections['total']); ?></span>
            
            <span class="pagination-links">
                <?php for ($i = 1; $i <= $detections['pages']; $i++): ?>
                    <button type="button" class="button aipdb-page-btn <?php echo $i === $detections['current_page'] ? 'current' : ''; ?>" 
                            data-page="<?php echo $i; ?>"><?php echo $i; ?></button>
                <?php endfor; ?>
            </span>
        </div>
    <?php endif; ?>
</div>

<!-- Modal para detalles -->
<div id="aipdb-detection-modal" class="aipdb-modal" style="display: none;">
    <div class="aipdb-modal-content">
        <span class="aipdb-modal-close">&times;</span>
        <h2><?php _e('Detection Details', 'wp-abuseipdb-integration'); ?></h2>
        <div id="aipdb-detection-details"></div>
    </div>
</div>

<!-- Action buttons -->
 <script type="text/javascript">
jQuery(document).ready(function($) {
    
    // Obtener nonce del PHP
    var aipdbNonce = '<?php echo wp_create_nonce('aipdb_admin_nonce'); ?>';
    
    // Manejar click en botón Delete
    $(document).on('click', '.aipdb-delete-detection', function(e) {
        e.preventDefault();
        
        var $button = $(this);
        var detectionId = $button.data('id');
        var detectionIP = $button.data('ip');
        var $row = $button.closest('tr');
        
        // Confirmación
        if (!confirm('<?php _e("¿Estás seguro de que deseas eliminar esta detección?", "wp-abuseipdb-integration"); ?>\n\nIP: ' + detectionIP)) {
            return;
        }
        
        // Deshabilitar botón y mostrar loading
        $button.prop('disabled', true).html('<span class="spinner is-active" style="float:none;margin:0;"></span> <?php _e("Eliminando...", "wp-abuseipdb-integration"); ?>');
        
        // Petición AJAX
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'aipdb_delete_detection',
                id: detectionId,
                nonce: aipdbNonce
            },
            success: function(response) {
                if (response.success) {
                    // Animar eliminación de la fila
                    $row.fadeOut(500, function() {
                        $(this).remove();
                        
                        // Actualizar contador total
                        var currentTotal = parseInt($('.aipdb-total-count').text().match(/\d+/)[0]);
                        $('.aipdb-total-count').text('<?php _e("Total: ", "wp-abuseipdb-integration"); ?>' + (currentTotal - 1) + ' <?php _e("detections", "wp-abuseipdb-integration"); ?>');
                        
                        // Verificar si quedan filas
                        if ($('#aipdb-detections-table tbody tr').length === 0) {
                            $('#aipdb-detections-table tbody').html('<tr><td colspan="9" style="text-align:center;padding:20px;"><?php _e("No detections found.", "wp-abuseipdb-integration"); ?></td></tr>');
                        }
                    });
                    
                    // Mostrar mensaje de éxito
                    showNotification('success', response.data.message || '<?php _e("Detección eliminada correctamente", "wp-abuseipdb-integration"); ?>');
                    
                } else {
                    // Error - restaurar botón
                    $button.prop('disabled', false).html('<span class="dashicons dashicons-trash"></span> <?php _e("Delete", "wp-abuseipdb-integration"); ?>');
                    showNotification('error', response.data.message || '<?php _e("Error al eliminar la detección", "wp-abuseipdb-integration"); ?>');
                }
            },
            error: function(xhr, status, error) {
                // Error de conexión - restaurar botón
                $button.prop('disabled', false).html('<span class="dashicons dashicons-trash"></span> <?php _e("Delete", "wp-abuseipdb-integration"); ?>');
                showNotification('error', '<?php _e("Error de conexión. Inténtalo de nuevo.", "wp-abuseipdb-integration"); ?>');
                console.error('AJAX Error:', error);
            }
        });
    });
    
    // Función para mostrar notificaciones
    function showNotification(type, message) {
        var noticeClass = type === 'success' ? 'notice-success' : 'notice-error';
        var $notice = $('<div class="notice ' + noticeClass + ' is-dismissible" style="margin:15px 0;"><p>' + message + '</p></div>');
        
        // Insertar después del título
        $('.wrap h1').after($notice);
        
        // Auto-hide después de 5 segundos
        setTimeout(function() {
            $notice.fadeOut(500, function() {
                $(this).remove();
            });
        }, 5000);
        
        // Permitir cerrar manualmente
        $notice.on('click', '.notice-dismiss', function() {
            $notice.remove();
        });
    }
    
    // Manejar botón View Details
    $(document).on('click', '.aipdb-view-details', function(e) {
        e.preventDefault();
        var detectionId = $(this).data('id');
        
        // Encontrar la fila y obtener datos
        var $row = $(this).closest('tr');
        var data = {
            id: detectionId,
            ip: $row.find('td:nth-child(3)').text().trim(),
            date: $row.find('td:nth-child(2)').text().trim(),
            eventType: $row.find('td:nth-child(4)').text().trim(),
            threatLevel: $row.find('td:nth-child(5)').text().trim(),
            score: $row.find('td:nth-child(6)').text().trim(),
            country: $row.find('td:nth-child(7)').text().trim(),
            action: $row.find('td:nth-child(8)').text().trim()
        };
        
        // Mostrar modal con detalles (implementar según necesidades)
        showDetailsModal(data);
    });
    
    // Función para mostrar modal de detalles
    function showDetailsModal(data) {
        var modalContent = '<div class="aipdb-details-modal-overlay">' +
            '<div class="aipdb-details-modal">' +
                '<div class="aipdb-modal-header">' +
                    '<h3><?php _e("Detalles de Detección", "wp-abuseipdb-integration"); ?> #' + data.id + '</h3>' +
                    '<span class="aipdb-modal-close">&times;</span>' +
                '</div>' +
                '<div class="aipdb-modal-body">' +
                    '<table class="aipdb-details-table">' +
                        '<tr><td><strong><?php _e("IP Address:", "wp-abuseipdb-integration"); ?></strong></td><td>' + data.ip + '</td></tr>' +
                        '<tr><td><strong><?php _e("Fecha:", "wp-abuseipdb-integration"); ?></strong></td><td>' + data.date + '</td></tr>' +
                        '<tr><td><strong><?php _e("Tipo de Evento:", "wp-abuseipdb-integration"); ?></strong></td><td>' + data.eventType + '</td></tr>' +
                        '<tr><td><strong><?php _e("Nivel de Amenaza:", "wp-abuseipdb-integration"); ?></strong></td><td>' + data.threatLevel + '</td></tr>' +
                        '<tr><td><strong><?php _e("AbuseIPDB Score:", "wp-abuseipdb-integration"); ?></strong></td><td>' + data.score + '</td></tr>' +
                        '<tr><td><strong><?php _e("País:", "wp-abuseipdb-integration"); ?></strong></td><td>' + data.country + '</td></tr>' +
                        '<tr><td><strong><?php _e("Acción Tomada:", "wp-abuseipdb-integration"); ?></strong></td><td>' + data.action + '</td></tr>' +
                    '</table>' +
                '</div>' +
            '</div>' +
        '</div>';
        
        $('body').append(modalContent);
        
        // Cerrar modal
        $(document).on('click', '.aipdb-modal-close, .aipdb-details-modal-overlay', function(e) {
            if (e.target === this) {
                $('.aipdb-details-modal-overlay').remove();
            }
        });
    }
});
</script>

<style>
.aipdb-actions {
    white-space: nowrap;
}

.aipdb-actions .button {
    margin-right: 5px;
    vertical-align: middle;
}

.aipdb-actions .dashicons {
    width: 14px;
    height: 14px;
    font-size: 14px;
    line-height: 1;
    margin-right: 3px;
}

.button-link-delete {
    color: #a00 !important;
    border-color: #a00 !important;
}

.button-link-delete:hover {
    background: #a00 !important;
    color: #fff !important;
}

/* Modal styles */
.aipdb-details-modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.7);
    z-index: 100000;
    display: flex;
    align-items: center;
    justify-content: center;
}

.aipdb-details-modal {
    background: #fff;
    border-radius: 5px;
    padding: 0;
    max-width: 600px;
    width: 90%;
    max-height: 80vh;
    overflow-y: auto;
    box-shadow: 0 5px 15px rgba(0,0,0,0.3);
}

.aipdb-modal-header {
    padding: 15px 20px;
    border-bottom: 1px solid #ddd;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.aipdb-modal-header h3 {
    margin: 0;
    font-size: 16px;
}

.aipdb-modal-close {
    font-size: 24px;
    cursor: pointer;
    color: #666;
}

.aipdb-modal-close:hover {
    color: #000;
}

.aipdb-modal-body {
    padding: 20px;
}

.aipdb-details-table {
    width: 100%;
    border-collapse: collapse;
}

.aipdb-details-table td {
    padding: 8px 12px;
    border-bottom: 1px solid #eee;
    vertical-align: top;
}

.aipdb-details-table td:first-child {
    width: 150px;
    color: #555;
}
</style>