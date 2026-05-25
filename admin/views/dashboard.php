<?php
/**
 * Vista: Dashboard – WP AbuseIPDB Integration
 *
 * Se encarga de mostrar las métricas principales del plugin.
 *   – Estadísticas generales (consultas API, detecciones, etc.)
 *   – Actividad reciente
 *   – Configuración rápida (API Key y activación)
 *
 * Esta vista depende del controlador AIPDB_Dashboard para los
 * datos vía AJAX y del CSS `admin/css/admin.css` para los estilos.
 */
if (!defined('ABSPATH')) {
	exit;
}

$dashboard = new AIPDB_Dashboard();
$stats = $dashboard->get_dashboard_stats();
?>
<div class="aipdb-dashboard">

	<!-- Tarjetas de estadísticas -->
	<div class="aipdb-stats-grid">
		<div class="aipdb-stat-card">
			<div class="aipdb-stat-icon dashicons dashicons-networking"></div>
			<div class="aipdb-stat-content">
<<<<<<< HEAD
				<h3><?php echo number_format( $stats['api_calls_today'] ); ?></h3>
				<p><?php _e( 'API calls today', 'wp-abuseipdb-integration' ); ?></p>
=======
				<h3><?php echo number_format($stats['api_calls_today']); ?></h3>
				<p><?php _e('API calls today', 'abuseipdb-wp-integration'); ?></p>
>>>>>>> 39b53601c2a9ab52dff675be235763be9898100c
			</div>
		</div>

		<div class="aipdb-stat-card">
			<div class="aipdb-stat-icon dashicons dashicons-warning"></div>
			<div class="aipdb-stat-content">
<<<<<<< HEAD
				<h3><?php echo number_format( $stats['total_detections'] ); ?></h3>
				<p><?php _e( 'Total detections', 'wp-abuseipdb-integration' ); ?></p>
=======
				<h3><?php echo number_format($stats['total_detections']); ?></h3>
				<p><?php _e('Total detections', 'abuseipdb-wp-integration'); ?></p>
>>>>>>> 39b53601c2a9ab52dff675be235763be9898100c
			</div>
		</div>

		<div class="aipdb-stat-card">
			<div class="aipdb-stat-icon dashicons dashicons-shield-alt"></div>
			<div class="aipdb-stat-content">
<<<<<<< HEAD
				<h3><?php echo number_format( $stats['blocked_ips'] ); ?></h3>
				<p><?php _e( 'IPs blocked', 'wp-abuseipdb-integration' ); ?></p>
=======
				<h3><?php echo number_format($stats['blocked_ips']); ?></h3>
				<p><?php _e('IPs blocked', 'abuseipdb-wp-integration'); ?></p>
>>>>>>> 39b53601c2a9ab52dff675be235763be9898100c
			</div>
		</div>

		<div class="aipdb-stat-card">
			<div class="aipdb-stat-icon dashicons dashicons-admin-generic"></div>
			<div class="aipdb-stat-content">
				<h3>
					<?php
					echo 'ok' === $stats['api_status']
<<<<<<< HEAD
						? __( 'Connected', 'wp-abuseipdb-integration' )
						: __( 'Unknown', 'wp-abuseipdb-integration' );
					?>
				</h3>
				<p><?php _e( 'API status', 'wp-abuseipdb-integration' ); ?></p>
=======
						? __('Connected', 'abuseipdb-wp-integration')
						: __('Unknown', 'abuseipdb-wp-integration');
					?>
				</h3>
				<p><?php _e('API status', 'abuseipdb-wp-integration'); ?></p>
>>>>>>> 39b53601c2a9ab52dff675be235763be9898100c
			</div>
		</div>
	</div><!-- .aipdb-stats-grid -->

	<!-- Configuración rápida -->
	<div class="aipdb-quick-settings">
<<<<<<< HEAD
		<h2><?php _e( 'Quick Setup', 'wp-abuseipdb-integration' ); ?></h2>
=======
		<h2><?php _e('Quick Setup', 'abuseipdb-wp-integration'); ?></h2>
>>>>>>> 39b53601c2a9ab52dff675be235763be9898100c

		<form id="aipdb-quick-form" class="aipdb-quick-form">
			<table class="form-table">
				<tr>
					<th scope="row">
<<<<<<< HEAD
						<label for="aipdb_api_key"><?php _e( 'AbuseIPDB API Key', 'wp-abuseipdb-integration' ); ?></label>
=======
						<label
							for="aipdb_api_key"><?php _e('AbuseIPDB API Key', 'abuseipdb-wp-integration'); ?></label>
>>>>>>> 39b53601c2a9ab52dff675be235763be9898100c
					</th>
					<td>
						<div class="aipdb-input-flex">
							<input type="password" id="aipdb_api_key" name="aipdb_api_key"
								value="<?php echo esc_attr(get_option('aipdb_api_key', '')); ?>"
								class="regular-text" />
							<button type="button" class="button aipdb-test-api">
<<<<<<< HEAD
								<?php _e( 'Test Connection', 'wp-abuseipdb-integration' ); ?>
=======
								<?php _e('Test Connection', 'abuseipdb-wp-integration'); ?>
>>>>>>> 39b53601c2a9ab52dff675be235763be9898100c
							</button>
						</div>
						<div id="aipdb-api-status-message"></div>
					</td>
				</tr>

				<tr>
<<<<<<< HEAD
					<th scope="row"><?php _e( 'Enable protection', 'wp-abuseipdb-integration' ); ?></th>
=======
					<th scope="row"><?php _e('Enable protection', 'abuseipdb-wp-integration'); ?></th>
>>>>>>> 39b53601c2a9ab52dff675be235763be9898100c
					<td>
						<label class="aipdb-switch">
							<input type="checkbox" name="aipdb_enabled" value="1" <?php checked(get_option('aipdb_enabled', 0), 1); ?> />
							<span class="aipdb-slider"></span>
						</label>
					</td>
				</tr>

				<tr>
					<th scope="row">
<<<<<<< HEAD
						<label for="aipdb_abuse_threshold"><?php _e( 'Abuse Threshold', 'wp-abuseipdb-integration' ); ?></label>
=======
						<label
							for="aipdb_abuse_threshold"><?php _e('Abuse Threshold', 'abuseipdb-wp-integration'); ?></label>
>>>>>>> 39b53601c2a9ab52dff675be235763be9898100c
					</th>
					<td>
						<input type="range" id="aipdb_abuse_threshold" name="aipdb_abuse_threshold" min="1" max="100"
							value="<?php echo esc_attr(get_option('aipdb_abuse_threshold', 70)); ?>">
						<output class="aipdb-threshold-output">
							<?php echo esc_html(get_option('aipdb_abuse_threshold', 70)); ?>%
						</output>
					</td>
				</tr>
			</table>

			<p class="submit">
				<button type="submit" class="button-primary">
<<<<<<< HEAD
					<?php _e( 'Save', 'wp-abuseipdb-integration' ); ?>
=======
					<?php _e('Save', 'abuseipdb-wp-integration'); ?>
>>>>>>> 39b53601c2a9ab52dff675be235763be9898100c
				</button>
			</p>
		</form>
	</div><!-- .aipdb-quick-settings -->

	<!-- Test IP Tool -->
	<div class="aipdb-test-ip-tool">
		<h2><?php _e('Test IP Address', 'abuseipdb-wp-integration'); ?></h2>
		<p><?php _e('Manually check an IP address against AbuseIPDB using the firewall logic.', 'abuseipdb-wp-integration'); ?>
		</p>

		<div class="aipdb-input-flex">
			<input type="text" id="aipdb_test_ip" placeholder="8.8.8.8" class="regular-text">
			<button type="button" class="button button-secondary aipdb-check-ip">
				<?php _e('Check IP', 'abuseipdb-wp-integration'); ?>
			</button>
		</div>
		<div id="aipdb-test-ip-result" style="margin-top: 15px;"></div>
	</div>

	<!-- Actividad reciente -->
	<div class="aipdb-recent-activity">
<<<<<<< HEAD
		<h2><?php _e( 'Recent Activity', 'wp-abuseipdb-integration' ); ?></h2>

		<?php $recent = $dashboard->get_recent_activity( 8 ); ?>
		<?php if ( empty( $recent ) ) : ?>
			<p><?php _e( 'No activity yet.', 'wp-abuseipdb-integration' ); ?></p>
		<?php else : ?>
=======
		<h2><?php _e('Recent Activity', 'abuseipdb-wp-integration'); ?></h2>

		<?php $recent = $dashboard->get_recent_activity(8); ?>
		<?php if (empty($recent)): ?>
			<p><?php _e('No activity yet.', 'abuseipdb-wp-integration'); ?></p>
		<?php else: ?>
>>>>>>> 39b53601c2a9ab52dff675be235763be9898100c
			<ul class="aipdb-activity-list">
				<?php foreach ($recent as $item): ?>
					<li>
						<span class="dashicons dashicons-<?php echo esc_attr($item['type']); ?>"></span>
						<strong><?php echo esc_html($item['message']); ?></strong>
						<small>
							<?php
<<<<<<< HEAD
							echo esc_html( $item['ip'] ) . ' – ';
							echo human_time_diff( $item['timestamp'], current_time( 'timestamp' ) ) . ' ';
							_e( 'ago', 'wp-abuseipdb-integration' );
=======
							echo esc_html($item['ip']) . ' – ';
							echo human_time_diff($item['timestamp'], current_time('timestamp')) . ' ';
							_e('ago', 'abuseipdb-wp-integration');
>>>>>>> 39b53601c2a9ab52dff675be235763be9898100c
							?>
						</small>
					</li>
				<?php endforeach; ?>
			</ul>
		<?php endif; ?>
	</div><!-- .aipdb-recent-activity -->

</div><!-- .aipdb-dashboard -->