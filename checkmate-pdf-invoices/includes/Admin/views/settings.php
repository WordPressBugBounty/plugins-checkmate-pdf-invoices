<?php
/**
 * Settings View - Premium UI
 *
 * Plugin settings and configuration page.
 *
 * @package Checkmate\PdfInvoices
 */

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Variables are local to this view file.

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Handle settings save
$settings_saved = false;
$settings_error = '';

if ( isset( $_POST['checkmate_pdf_settings_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['checkmate_pdf_settings_nonce'] ) ), 'checkmate_pdf_settings' ) ) {
	$new_settings = [
		// Numbering
		'invoice_number_format'  => isset( $_POST['invoice_number_format'] ) ? sanitize_text_field( wp_unslash( $_POST['invoice_number_format'] ) ) : 'INV-{year}-{number}',
		'invoice_next_number'    => isset( $_POST['invoice_next_number'] ) ? absint( wp_unslash( $_POST['invoice_next_number'] ) ) : 1,
		'invoice_number_padding' => isset( $_POST['invoice_number_padding'] ) ? absint( wp_unslash( $_POST['invoice_number_padding'] ) ) : 5,
		'invoice_reset_yearly'   => isset( $_POST['invoice_reset_yearly'] ),
		'packing_slip_prefix'    => isset( $_POST['packing_slip_prefix'] ) ? sanitize_text_field( wp_unslash( $_POST['packing_slip_prefix'] ) ) : 'PS-{year}-{number}',
		'credit_note_prefix'     => isset( $_POST['credit_note_prefix'] ) ? sanitize_text_field( wp_unslash( $_POST['credit_note_prefix'] ) ) : 'CN-{year}-{number}',
		// Storage
		'storage_enabled'        => isset( $_POST['storage_enabled'] ),
		'storage_path'           => isset( $_POST['storage_path'] ) ? sanitize_file_name( wp_unslash( $_POST['storage_path'] ) ) : 'checkmate-pdf-invoices',
		'auto_generate'          => isset( $_POST['auto_generate'] ),
		// Access - Customer
		'customer_access'        => isset( $_POST['customer_access'] ),
		'guest_access'           => isset( $_POST['guest_access'] ),
		// Access - Admin Interface
		'admin_order_buttons'    => isset( $_POST['admin_order_buttons'] ),
		'admin_bulk_actions'     => isset( $_POST['admin_bulk_actions'] ),
		'admin_order_column'     => isset( $_POST['admin_order_column'] ),
		// Advanced
		'debug_mode'             => isset( $_POST['debug_mode'] ),
		'cache_enabled'          => isset( $_POST['cache_enabled'] ),
		'cleanup_days'           => isset( $_POST['cleanup_days'] ) ? absint( wp_unslash( $_POST['cleanup_days'] ) ) : 30,
	];

	// Merge with existing settings (preserves any future settings)
	$existing_settings = get_option( 'checkmate_pdf_settings', [] );
	$new_settings = array_merge( $existing_settings, $new_settings );

	update_option( 'checkmate_pdf_settings', $new_settings, false );
	$settings_saved = true;
}

$theme_mode = get_user_meta( get_current_user_id(), 'checkmate_pdf_theme_mode', true );
$allowed_theme_modes = [ 'dark', 'light', 'auto' ];
if ( ! in_array( $theme_mode, $allowed_theme_modes, true ) ) {
	$theme_mode = 'dark';
}

// Get saved settings
$settings = get_option( 'checkmate_pdf_settings', [] );
$defaults = [
	// Numbering
	'invoice_number_format'  => 'INV-{year}-{number}',
	'invoice_next_number'    => 1,
	'invoice_number_padding' => 5,
	'invoice_reset_yearly'   => false,
	'packing_slip_prefix'    => 'PS-{year}-{number}',
	'credit_note_prefix'     => 'CN-{year}-{number}',
	// Storage
	'storage_enabled'        => false,
	'storage_path'           => 'checkmate-pdf-invoices',
	'auto_generate'          => true,
	// Access - Customer
	'customer_access'        => true,
	'guest_access'           => false,
	// Access - Admin Interface
	'admin_order_buttons'    => true,
	'admin_bulk_actions'     => true,
	'admin_order_column'     => false,
	// Advanced
	'debug_mode'             => false,
	'cache_enabled'          => true,
	'cleanup_days'           => 30,
];
$settings = wp_parse_args( $settings, $defaults );

// Settings sections
$settings_sections = [
	'numbering' => [
		'title' => esc_html__( 'Document Numbering', 'checkmate-pdf-invoices' ),
		'icon'  => 'dashicons-editor-ol',
	],
	'storage' => [
		'title' => esc_html__( 'Storage', 'checkmate-pdf-invoices' ),
		'icon'  => 'dashicons-portfolio',
	],
	'access' => [
		'title' => esc_html__( 'Access & Interface', 'checkmate-pdf-invoices' ),
		'icon'  => 'dashicons-admin-users',
	],
	'advanced' => [
		'title' => esc_html__( 'Advanced', 'checkmate-pdf-invoices' ),
		'icon'  => 'dashicons-admin-tools',
	],
];

$current_section = isset( $_GET['section'] ) ? sanitize_key( (string) wp_unslash( $_GET['section'] ) ) : 'numbering';
if ( ! isset( $settings_sections[ $current_section ] ) ) {
	$current_section = 'numbering';
}
?>

<div class="cm-wrap">
	<!-- Header -->
	<div class="cm-header cm-header-compact">
		<div class="cm-header-content">
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=checkmate-pdf-invoices' ) ); ?>" class="cm-back-link">
				<span class="dashicons dashicons-arrow-left-alt2"></span>
				<?php esc_html_e( 'Dashboard', 'checkmate-pdf-invoices' ); ?>
			</a>
			<h1 class="cm-title cm-title-sm"><?php esc_html_e( 'Settings', 'checkmate-pdf-invoices' ); ?></h1>
			<p class="cm-subtitle"><?php esc_html_e( 'Configure your PDF invoice settings', 'checkmate-pdf-invoices' ); ?></p>
		</div>
	</div>

	<?php if ( $settings_saved ) : ?>
	<div class="cm-notice cm-notice-success">
		<span class="dashicons dashicons-yes-alt"></span>
		<?php esc_html_e( 'Settings saved successfully.', 'checkmate-pdf-invoices' ); ?>
	</div>
	<?php endif; ?>

	<div class="cm-settings-layout">
		<!-- Sidebar Navigation -->
		<div class="cm-settings-sidebar">
			<nav class="cm-settings-nav">
				<?php foreach ( $settings_sections as $section_id => $section ) : ?>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=checkmate-pdf-settings&section=' . $section_id ) ); ?>" 
				   class="cm-settings-nav-item <?php echo esc_attr( $current_section === $section_id ? 'active' : '' ); ?>">
					<span class="dashicons <?php echo esc_attr( $section['icon'] ); ?>"></span>
					<?php echo esc_html( $section['title'] ); ?>
				</a>
				<?php endforeach; ?>
			</nav>
		</div>

		<!-- Settings Content -->
		<div class="cm-settings-content">
			<form method="post" action="" class="cm-settings-form">
				<?php wp_nonce_field( 'checkmate_pdf_settings', 'checkmate_pdf_settings_nonce' ); ?>

				<?php if ( $current_section === 'numbering' ) : ?>
				<!-- Document Numbering Settings -->
				<div class="cm-settings-section">
					<h2 class="cm-settings-section-title"><?php esc_html_e( 'Invoice Numbering', 'checkmate-pdf-invoices' ); ?></h2>
					
					<div class="cm-setting-row">
						<div class="cm-setting-label">
							<label for="invoice_number_format"><?php esc_html_e( 'Invoice Number Format', 'checkmate-pdf-invoices' ); ?></label>
							<p class="cm-setting-desc"><?php esc_html_e( 'Use placeholders: {year}, {month}, {day}, {number}', 'checkmate-pdf-invoices' ); ?></p>
						</div>
						<div class="cm-setting-field">
							<input type="text" id="invoice_number_format" name="invoice_number_format" value="<?php echo esc_attr( $settings['invoice_number_format'] ); ?>" class="cm-input">
							<span class="cm-field-preview"><?php esc_html_e( 'Preview:', 'checkmate-pdf-invoices' ); ?> <?php echo esc_html( str_replace( [ '{year}', '{month}', '{day}', '{number}' ], [ wp_date('Y'), wp_date('m'), wp_date('d'), str_pad( $settings['invoice_next_number'], $settings['invoice_number_padding'], '0', STR_PAD_LEFT ) ], $settings['invoice_number_format'] ) ); ?></span>
						</div>
					</div>

					<div class="cm-setting-row">
						<div class="cm-setting-label">
							<label for="invoice_next_number"><?php esc_html_e( 'Next Invoice Number', 'checkmate-pdf-invoices' ); ?></label>
							<p class="cm-setting-desc"><?php esc_html_e( 'The sequential number for the next invoice', 'checkmate-pdf-invoices' ); ?></p>
						</div>
						<div class="cm-setting-field">
							<input type="number" id="invoice_next_number" name="invoice_next_number" value="<?php echo esc_attr( $settings['invoice_next_number'] ); ?>" min="1" class="cm-input cm-input-sm">
						</div>
					</div>

					<div class="cm-setting-row">
						<div class="cm-setting-label">
							<label for="invoice_number_padding"><?php esc_html_e( 'Number Padding', 'checkmate-pdf-invoices' ); ?></label>
							<p class="cm-setting-desc"><?php esc_html_e( 'Minimum digits for sequential number (e.g., 5 = 00001)', 'checkmate-pdf-invoices' ); ?></p>
						</div>
						<div class="cm-setting-field">
							<select id="invoice_number_padding" name="invoice_number_padding" class="cm-select">
								<option value="3" <?php selected( $settings['invoice_number_padding'], 3 ); ?>>3 (001)</option>
								<option value="4" <?php selected( $settings['invoice_number_padding'], 4 ); ?>>4 (0001)</option>
								<option value="5" <?php selected( $settings['invoice_number_padding'], 5 ); ?>>5 (00001)</option>
								<option value="6" <?php selected( $settings['invoice_number_padding'], 6 ); ?>>6 (000001)</option>
							</select>
						</div>
					</div>

					<div class="cm-setting-row">
						<div class="cm-setting-label">
							<label><?php esc_html_e( 'Reset Yearly', 'checkmate-pdf-invoices' ); ?></label>
							<p class="cm-setting-desc"><?php esc_html_e( 'Reset the invoice number counter at the start of each year', 'checkmate-pdf-invoices' ); ?></p>
						</div>
						<div class="cm-setting-field">
							<label class="cm-toggle">
								<input type="checkbox" name="invoice_reset_yearly" value="1" class="cm-toggle-input" <?php checked( $settings['invoice_reset_yearly'] ); ?>>
								<span class="cm-toggle-slider"></span>
							</label>
						</div>
					</div>
				</div>

				<div class="cm-settings-section">
					<h2 class="cm-settings-section-title"><?php esc_html_e( 'Other Document Types', 'checkmate-pdf-invoices' ); ?></h2>
					
					<div class="cm-setting-row">
						<div class="cm-setting-label">
							<label for="packing_slip_prefix"><?php esc_html_e( 'Packing Slip Format', 'checkmate-pdf-invoices' ); ?></label>
							<p class="cm-setting-desc"><?php esc_html_e( 'Number format for packing slips', 'checkmate-pdf-invoices' ); ?></p>
						</div>
						<div class="cm-setting-field">
							<input type="text" id="packing_slip_prefix" name="packing_slip_prefix" value="<?php echo esc_attr( $settings['packing_slip_prefix'] ); ?>" class="cm-input">
						</div>
					</div>

					<div class="cm-setting-row">
						<div class="cm-setting-label">
							<label for="credit_note_prefix"><?php esc_html_e( 'Credit Note Format', 'checkmate-pdf-invoices' ); ?></label>
							<p class="cm-setting-desc"><?php esc_html_e( 'Number format for credit notes', 'checkmate-pdf-invoices' ); ?></p>
						</div>
						<div class="cm-setting-field">
							<input type="text" id="credit_note_prefix" name="credit_note_prefix" value="<?php echo esc_attr( $settings['credit_note_prefix'] ); ?>" class="cm-input">
						</div>
					</div>
				</div>

				<?php elseif ( $current_section === 'storage' ) : ?>
				<!-- Storage Settings -->
				<div class="cm-settings-section">
					<h2 class="cm-settings-section-title"><?php esc_html_e( 'PDF Storage', 'checkmate-pdf-invoices' ); ?></h2>
					
					<div class="cm-setting-row">
						<div class="cm-setting-label">
							<label><?php esc_html_e( 'Save PDFs to Server', 'checkmate-pdf-invoices' ); ?></label>
							<p class="cm-setting-desc"><?php esc_html_e( 'Store generated PDFs on the server for faster access', 'checkmate-pdf-invoices' ); ?></p>
						</div>
						<div class="cm-setting-field">
							<label class="cm-toggle">
								<input type="checkbox" name="storage_enabled" value="1" class="cm-toggle-input" <?php checked( $settings['storage_enabled'] ); ?>>
								<span class="cm-toggle-slider"></span>
							</label>
						</div>
					</div>

					<div class="cm-setting-row">
						<div class="cm-setting-label">
							<label for="storage_path"><?php esc_html_e( 'Storage Folder', 'checkmate-pdf-invoices' ); ?></label>
							<p class="cm-setting-desc"><?php esc_html_e( 'Folder name inside wp-content/uploads/', 'checkmate-pdf-invoices' ); ?></p>
						</div>
						<div class="cm-setting-field">
							<div class="cm-input-prefix">
								<span class="cm-prefix-text">wp-content/uploads/</span>
								<input type="text" id="storage_path" name="storage_path" value="<?php echo esc_attr( $settings['storage_path'] ); ?>" class="cm-input">
							</div>
						</div>
					</div>

					<div class="cm-setting-row">
						<div class="cm-setting-label">
							<label><?php esc_html_e( 'Auto-generate PDFs', 'checkmate-pdf-invoices' ); ?></label>
							<p class="cm-setting-desc"><?php esc_html_e( 'Automatically generate PDFs when orders are created', 'checkmate-pdf-invoices' ); ?></p>
						</div>
						<div class="cm-setting-field">
							<label class="cm-toggle">
								<input type="checkbox" name="auto_generate" value="1" class="cm-toggle-input" <?php checked( $settings['auto_generate'] ); ?>>
								<span class="cm-toggle-slider"></span>
							</label>
						</div>
					</div>
				</div>

				<?php elseif ( $current_section === 'access' ) : ?>
				<!-- Access & Permissions Settings -->
				<div class="cm-settings-section">
					<h2 class="cm-settings-section-title"><?php esc_html_e( 'Customer Access', 'checkmate-pdf-invoices' ); ?></h2>
					
					<div class="cm-setting-row">
						<div class="cm-setting-label">
							<label><?php esc_html_e( 'My Account Downloads', 'checkmate-pdf-invoices' ); ?></label>
							<p class="cm-setting-desc"><?php esc_html_e( 'Allow logged-in customers to download invoices from their account', 'checkmate-pdf-invoices' ); ?></p>
						</div>
						<div class="cm-setting-field">
							<label class="cm-toggle">
								<input type="checkbox" name="customer_access" value="1" class="cm-toggle-input" <?php checked( $settings['customer_access'] ); ?>>
								<span class="cm-toggle-slider"></span>
							</label>
						</div>
					</div>

					<div class="cm-setting-row">
						<div class="cm-setting-label">
							<label><?php esc_html_e( 'Guest Access', 'checkmate-pdf-invoices' ); ?></label>
							<p class="cm-setting-desc"><?php esc_html_e( 'Allow guests to download invoices via order email links', 'checkmate-pdf-invoices' ); ?></p>
						</div>
						<div class="cm-setting-field">
							<label class="cm-toggle">
								<input type="checkbox" name="guest_access" value="1" class="cm-toggle-input" <?php checked( $settings['guest_access'] ); ?>>
								<span class="cm-toggle-slider"></span>
							</label>
						</div>
					</div>
				</div>

				<div class="cm-settings-section">
					<h2 class="cm-settings-section-title"><?php esc_html_e( 'Admin Interface', 'checkmate-pdf-invoices' ); ?></h2>
					
					<div class="cm-setting-row">
						<div class="cm-setting-label">
							<label><?php esc_html_e( 'Order Page Buttons', 'checkmate-pdf-invoices' ); ?></label>
							<p class="cm-setting-desc"><?php esc_html_e( 'Show PDF download/print buttons on order edit page', 'checkmate-pdf-invoices' ); ?></p>
						</div>
						<div class="cm-setting-field">
							<label class="cm-toggle">
								<input type="checkbox" name="admin_order_buttons" value="1" class="cm-toggle-input" <?php checked( $settings['admin_order_buttons'] ); ?>>
								<span class="cm-toggle-slider"></span>
							</label>
						</div>
					</div>

					<div class="cm-setting-row">
						<div class="cm-setting-label">
							<label><?php esc_html_e( 'Bulk Actions', 'checkmate-pdf-invoices' ); ?></label>
							<p class="cm-setting-desc"><?php esc_html_e( 'Add PDF actions to orders list bulk actions menu', 'checkmate-pdf-invoices' ); ?></p>
						</div>
						<div class="cm-setting-field">
							<label class="cm-toggle">
								<input type="checkbox" name="admin_bulk_actions" value="1" class="cm-toggle-input" <?php checked( $settings['admin_bulk_actions'] ); ?>>
								<span class="cm-toggle-slider"></span>
							</label>
						</div>
					</div>

					<div class="cm-setting-row">
						<div class="cm-setting-label">
							<label><?php esc_html_e( 'Invoice Column', 'checkmate-pdf-invoices' ); ?></label>
							<p class="cm-setting-desc"><?php esc_html_e( 'Add invoice number column to orders list table', 'checkmate-pdf-invoices' ); ?></p>
						</div>
						<div class="cm-setting-field">
							<label class="cm-toggle">
								<input type="checkbox" name="admin_order_column" value="1" class="cm-toggle-input" <?php checked( $settings['admin_order_column'] ); ?>>
								<span class="cm-toggle-slider"></span>
							</label>
						</div>
					</div>
				</div>

				<?php elseif ( $current_section === 'advanced' ) : ?>
				<!-- Advanced Settings -->
				<div class="cm-settings-section">
					<h2 class="cm-settings-section-title"><?php esc_html_e( 'Performance', 'checkmate-pdf-invoices' ); ?></h2>
					
					<div class="cm-setting-row">
						<div class="cm-setting-label">
							<label><?php esc_html_e( 'Enable Caching', 'checkmate-pdf-invoices' ); ?></label>
							<p class="cm-setting-desc"><?php esc_html_e( 'Cache rendered templates for faster PDF generation', 'checkmate-pdf-invoices' ); ?></p>
						</div>
						<div class="cm-setting-field">
							<label class="cm-toggle">
								<input type="checkbox" name="cache_enabled" value="1" class="cm-toggle-input" <?php checked( $settings['cache_enabled'] ); ?>>
								<span class="cm-toggle-slider"></span>
							</label>
						</div>
					</div>

					<div class="cm-setting-row">
						<div class="cm-setting-label">
							<label for="cleanup_days"><?php esc_html_e( 'Auto Cleanup', 'checkmate-pdf-invoices' ); ?></label>
							<p class="cm-setting-desc"><?php esc_html_e( 'Automatically delete cached PDFs older than specified days (0 = never)', 'checkmate-pdf-invoices' ); ?></p>
						</div>
						<div class="cm-setting-field">
							<div class="cm-input-suffix">
								<input type="number" id="cleanup_days" name="cleanup_days" value="<?php echo esc_attr( $settings['cleanup_days'] ); ?>" min="0" class="cm-input cm-input-sm">
								<span class="cm-suffix-text"><?php esc_html_e( 'days', 'checkmate-pdf-invoices' ); ?></span>
							</div>
						</div>
					</div>
				</div>

				<div class="cm-settings-section">
					<h2 class="cm-settings-section-title"><?php esc_html_e( 'Debugging', 'checkmate-pdf-invoices' ); ?></h2>
					
					<div class="cm-setting-row">
						<div class="cm-setting-label">
							<label><?php esc_html_e( 'Debug Mode', 'checkmate-pdf-invoices' ); ?></label>
							<p class="cm-setting-desc"><?php esc_html_e( 'Enable detailed error logging for troubleshooting', 'checkmate-pdf-invoices' ); ?></p>
						</div>
						<div class="cm-setting-field">
							<label class="cm-toggle">
								<input type="checkbox" name="debug_mode" value="1" class="cm-toggle-input" <?php checked( $settings['debug_mode'] ); ?>>
								<span class="cm-toggle-slider"></span>
							</label>
						</div>
					</div>
				</div>

				<div class="cm-settings-section cm-settings-section-danger">
					<h2 class="cm-settings-section-title"><?php esc_html_e( 'Reset & Cleanup', 'checkmate-pdf-invoices' ); ?></h2>
					
					<div class="cm-setting-row">
						<div class="cm-setting-label">
							<label><?php esc_html_e( 'Clear PDF Cache', 'checkmate-pdf-invoices' ); ?></label>
							<p class="cm-setting-desc"><?php esc_html_e( 'Delete all cached PDF files from the server', 'checkmate-pdf-invoices' ); ?></p>
						</div>
						<div class="cm-setting-field">
							<button type="button" class="cm-btn cm-btn-secondary cm-btn-sm" id="cm-clear-cache">
								<span class="dashicons dashicons-trash"></span>
								<?php esc_html_e( 'Clear Cache', 'checkmate-pdf-invoices' ); ?>
							</button>
						</div>
					</div>
				</div>
				<?php endif; ?>

				<div class="cm-settings-footer">
					<button type="submit" class="cm-btn cm-btn-primary">
						<span class="dashicons dashicons-saved"></span>
						<?php esc_html_e( 'Save Settings', 'checkmate-pdf-invoices' ); ?>
					</button>
				</div>
			</form>
		</div>
	</div>
</div>
