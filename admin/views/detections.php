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
                    <th><?php _e('Details', 'wp-abuseipdb-integration'); ?></th>
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
                            <td>
                                <button type="button" class="button button-small aipdb-view-details" data-id="<?php echo intval($detection->id); ?>">
                                    <?php _e('View', 'wp-abuseipdb-integration'); ?>
                                </button>
                            </td>
                            <td>
                                <button type="button" class="button button-small button-link-delete aipdb-delete-detection" data-id="<?php echo intval($detection->id); ?>">
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
