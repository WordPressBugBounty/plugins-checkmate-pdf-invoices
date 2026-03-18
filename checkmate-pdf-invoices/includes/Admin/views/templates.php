<?php
/**
 * Templates View - Template Management Page
 *
 * Shows saved templates with management actions + preset browser modal.
 *
 * @package Checkmate\PdfInvoices
 */

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Variables are local to this view file.

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Checkmate\PdfInvoices\Editor\TemplateRepository;
use Checkmate\PdfInvoices\Editor\PresetTemplates;

$theme_mode = get_user_meta( get_current_user_id(), 'checkmate_pdf_theme_mode', true );
$allowed_theme_modes = [ 'dark', 'light', 'auto' ];
if ( ! in_array( $theme_mode, $allowed_theme_modes, true ) ) {
	$theme_mode = 'dark';
}

// Current filter type.
// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only GET parameters for page display.
$current_type = isset( $_GET['type'] ) ? sanitize_key( (string) wp_unslash( $_GET['type'] ) ) : 'all';
$allowed_types = [ 'all', 'invoice', 'packing-slip', 'credit-note', 'delivery-note' ];
if ( ! in_array( $current_type, $allowed_types, true ) ) {
	$current_type = 'all';
}

// Sorting.
// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only GET parameters for page display.
$orderby = isset( $_GET['orderby'] ) ? sanitize_text_field( wp_unslash( $_GET['orderby'] ) ) : 'updated_at';
// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only GET parameters for page display.
$order = isset( $_GET['order'] ) ? sanitize_text_field( wp_unslash( $_GET['order'] ) ) : 'desc';
$allowed_orderby = [ 'name', 'updated_at' ];
$allowed_order = [ 'asc', 'desc' ];
if ( ! in_array( $orderby, $allowed_orderby, true ) ) {
	$orderby = 'updated_at';
}
if ( ! in_array( $order, $allowed_order, true ) ) {
	$order = 'desc';
}

// Fetch saved templates
$repository = TemplateRepository::instance();
$templates_used = $repository->get_templates_used();
$templates_limit = $repository->get_template_limit();
$template_limit_enabled = ( $templates_limit > 0 );
$template_limit_reached = ( $template_limit_enabled && $templates_used >= $templates_limit );

$query_args = [
	'orderby' => $orderby,
	'order'   => $order,
];
if ( $current_type !== 'all' ) {
	$query_args['document_type'] = $current_type;
}
$saved_templates = $repository->find_all( $query_args );

// Get preset catalog for modal
$preset_templates = PresetTemplates::get_catalog();

// Document type labels
$type_labels = [
	'all'           => esc_html__( 'All Templates', 'checkmate-pdf-invoices' ),
	'invoice'       => esc_html__( 'Invoices', 'checkmate-pdf-invoices' ),
	'packing-slip'  => esc_html__( 'Packing Slips', 'checkmate-pdf-invoices' ),
	'credit-note'   => esc_html__( 'Credit Notes', 'checkmate-pdf-invoices' ),
	'delivery-note' => esc_html__( 'Delivery Notes', 'checkmate-pdf-invoices' ),
];

// Email event labels (for assignment dropdown)
// Grouped by category for better UX
$email_events = [
	'' => esc_html__( '— Not assigned —', 'checkmate-pdf-invoices' ),
	
	// Admin Emails
	'admin_emails_group' => [
		'label' => esc_html__( 'Admin Emails', 'checkmate-pdf-invoices' ),
		'options' => [
			'new_order'       => esc_html__( 'New Order', 'checkmate-pdf-invoices' ),
			'cancelled_order' => esc_html__( 'Cancelled Order', 'checkmate-pdf-invoices' ),
			'failed_order'    => esc_html__( 'Failed Order', 'checkmate-pdf-invoices' ),
		],
	],
	
	// Customer Emails - Order Status
	'customer_status_group' => [
		'label' => esc_html__( 'Customer Emails — Order Status', 'checkmate-pdf-invoices' ),
		'options' => [
			'customer_on_hold_order'    => esc_html__( 'Order On-Hold', 'checkmate-pdf-invoices' ),
			'customer_processing_order' => esc_html__( 'Processing Order', 'checkmate-pdf-invoices' ),
			'customer_completed_order'  => esc_html__( 'Completed Order', 'checkmate-pdf-invoices' ),
			'customer_refunded_order'   => esc_html__( 'Refunded Order', 'checkmate-pdf-invoices' ),
		],
	],
	
	// Customer Emails - Other
	'customer_other_group' => [
		'label' => esc_html__( 'Customer Emails — Other', 'checkmate-pdf-invoices' ),
		'options' => [
			'customer_invoice'          => esc_html__( 'Customer Invoice (Manual)', 'checkmate-pdf-invoices' ),
			'customer_note'             => esc_html__( 'Customer Note', 'checkmate-pdf-invoices' ),
			'customer_partially_refunded_order' => esc_html__( 'Partially Refunded Order', 'checkmate-pdf-invoices' ),
		],
	],
];

$nonce = wp_create_nonce( 'checkmate_admin_nonce' );
$ajax_url = admin_url( 'admin-ajax.php' );
$editor_url = admin_url( 'admin.php?page=checkmate-pdf-editor' );

// Check if we should auto-open the preset browser modal.
// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only GET parameter for UI state.
$action = isset( $_GET['action'] ) ? sanitize_key( (string) wp_unslash( $_GET['action'] ) ) : '';
$auto_open_modal = ( $action === 'create' ) && ! $template_limit_reached;
?>

<div class="cm-wrap">
	<!-- Header -->
	<div class="cm-header cm-header-compact">
		<div class="cm-header-content">
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=checkmate-pdf-invoices' ) ); ?>" class="cm-back-link">
				<span class="dashicons dashicons-arrow-left-alt2"></span>
				<?php esc_html_e( 'Dashboard', 'checkmate-pdf-invoices' ); ?>
			</a>
			<h1 class="cm-title cm-title-sm"><?php esc_html_e( 'Templates', 'checkmate-pdf-invoices' ); ?></h1>
			<p class="cm-subtitle"><?php esc_html_e( 'Manage your PDF templates and assign them to email events', 'checkmate-pdf-invoices' ); ?></p>
		</div>
		<div class="cm-header-actions">
			<?php if ( $template_limit_enabled ) : ?>
				<span class="cm-badge">
					<?php
					echo esc_html(
						sprintf(
							/* translators: 1: used templates count, 2: templates limit */
							__( 'Used %1$d / %2$d', 'checkmate-pdf-invoices' ),
							(int) $templates_used,
							(int) $templates_limit
						)
					);
					?>
				</span>
			<?php endif; ?>
			<button type="button" class="cm-btn cm-btn-primary" id="cm-create-new" <?php echo $template_limit_reached ? 'disabled="disabled" aria-disabled="true"' : ''; ?>>
				<span class="dashicons dashicons-plus"></span>
				<?php esc_html_e( 'Create Template', 'checkmate-pdf-invoices' ); ?>
			</button>
		</div>
	</div>

	<!-- Filter Navigation -->
	<div class="cm-nav">
		<?php foreach ( $type_labels as $type_key => $type_label ) : ?>
		<button type="button" 
		   class="cm-nav-item <?php echo esc_attr( $current_type === $type_key ? 'active' : '' ); ?>"
		   data-filter="<?php echo esc_attr( $type_key ); ?>">
			<?php echo esc_html( $type_label ); ?>
		</button>
		<?php endforeach; ?>
	</div>

	<!-- Templates Table -->
	<?php if ( ! empty( $saved_templates ) ) : 
		// Build sort URL helper
		$base_url = admin_url( 'admin.php?page=checkmate-pdf-templates' );
		if ( $current_type !== 'all' ) {
			$base_url .= '&type=' . rawurlencode( $current_type );
		}
		$name_order = ( $orderby === 'name' && $order === 'asc' ) ? 'desc' : 'asc';
		$date_order = ( $orderby === 'updated_at' && $order === 'desc' ) ? 'asc' : 'desc';
	?>
	<div class="cm-table-wrap">
		<table class="cm-table">
			<thead>
				<tr>
					<th class="cm-col-name cm-col-sortable">
						<a href="<?php echo esc_url( $base_url . '&orderby=name&order=' . $name_order ); ?>" class="cm-sort-link <?php echo esc_attr( $orderby === 'name' ? 'is-sorted' : '' ); ?>">
							<?php esc_html_e( 'Template', 'checkmate-pdf-invoices' ); ?>
							<span class="cm-sort-icon <?php echo esc_attr( ( $orderby === 'name' && $order === 'asc' ) ? 'asc' : 'desc' ); ?>"></span>
						</a>
					</th>
					<th class="cm-col-type"><?php esc_html_e( 'Type', 'checkmate-pdf-invoices' ); ?></th>
					<th class="cm-col-event"><?php esc_html_e( 'Attached to Event', 'checkmate-pdf-invoices' ); ?></th>
					<th class="cm-col-status"><?php esc_html_e( 'Status', 'checkmate-pdf-invoices' ); ?></th>
					<th class="cm-col-date cm-col-sortable">
						<a href="<?php echo esc_url( $base_url . '&orderby=updated_at&order=' . $date_order ); ?>" class="cm-sort-link <?php echo esc_attr( $orderby === 'updated_at' ? 'is-sorted' : '' ); ?>">
							<?php esc_html_e( 'Modified', 'checkmate-pdf-invoices' ); ?>
							<span class="cm-sort-icon <?php echo esc_attr( ( $orderby === 'updated_at' && $order === 'asc' ) ? 'asc' : 'desc' ); ?>"></span>
						</a>
					</th>
					<th class="cm-col-actions"><?php esc_html_e( 'Actions', 'checkmate-pdf-invoices' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $saved_templates as $template ) : 
					$tpl_id = $template->get_id();
					$tpl_name = $template->get_name() ?: __( 'Untitled', 'checkmate-pdf-invoices' );
					$tpl_type = $template->get_document_type();
					$tpl_active = $template->is_active();
					$tpl_updated = $template->get_updated_at();
					$tpl_event = get_option( 'checkmate_template_event_' . $tpl_id, '' );
				?>
				<tr data-template-id="<?php echo esc_attr( $tpl_id ); ?>" data-type="<?php echo esc_attr( $tpl_type ); ?>">
					<td class="cm-col-name">
						<div class="cm-tpl-name">
							<a href="<?php echo esc_url( $editor_url . '&template_id=' . $tpl_id ); ?>" class="cm-tpl-link">
								<?php echo esc_html( $tpl_name ); ?>
							</a>
						</div>
					</td>
					<td class="cm-col-type">
						<span class="cm-type-badge cm-type-<?php echo esc_attr( $tpl_type ); ?>">
							<?php echo esc_html( $type_labels[ $tpl_type ] ?? $tpl_type ); ?>
						</span>
					</td>
					<td class="cm-col-event">
						<select class="cm-event-select" data-template-id="<?php echo esc_attr( $tpl_id ); ?>">
							<?php foreach ( $email_events as $event_key => $event_data ) : ?>
								<?php if ( is_array( $event_data ) && isset( $event_data['options'] ) ) : ?>
									<optgroup label="<?php echo esc_attr( $event_data['label'] ); ?>">
										<?php foreach ( $event_data['options'] as $opt_key => $opt_label ) : ?>
											<option value="<?php echo esc_attr( $opt_key ); ?>" <?php selected( $tpl_event, $opt_key ); ?>>
												<?php echo esc_html( $opt_label ); ?>
											</option>
										<?php endforeach; ?>
									</optgroup>
								<?php else : ?>
									<option value="<?php echo esc_attr( $event_key ); ?>" <?php selected( $tpl_event, $event_key ); ?>>
										<?php echo esc_html( $event_data ); ?>
									</option>
								<?php endif; ?>
							<?php endforeach; ?>
						</select>
					</td>
					<td class="cm-col-status">
						<label class="cm-toggle" data-template-id="<?php echo esc_attr( $tpl_id ); ?>">
							<input type="checkbox" class="cm-toggle-input" <?php checked( $tpl_active ); ?>>
							<span class="cm-toggle-slider"></span>
						</label>
					</td>
					<td class="cm-col-date">
						<?php echo esc_html( human_time_diff( strtotime( $tpl_updated ), time() ) . ' ' . __( 'ago', 'checkmate-pdf-invoices' ) ); ?>
					</td>
					<td class="cm-col-actions">
						<div class="cm-actions-group">
							<a href="<?php echo esc_url( $editor_url . '&template_id=' . $tpl_id ); ?>" class="cm-action-btn" title="<?php esc_attr_e( 'Edit', 'checkmate-pdf-invoices' ); ?>">
								<span class="dashicons dashicons-edit"></span>
							</a>
							<button type="button" class="cm-action-btn cm-action-preview" data-template-id="<?php echo esc_attr( $tpl_id ); ?>" title="<?php esc_attr_e( 'Preview', 'checkmate-pdf-invoices' ); ?>">
								<span class="dashicons dashicons-visibility"></span>
							</button>
							<button type="button" class="cm-action-btn cm-action-duplicate" data-template-id="<?php echo esc_attr( $tpl_id ); ?>" title="<?php esc_attr_e( 'Duplicate', 'checkmate-pdf-invoices' ); ?>" <?php echo $template_limit_reached ? 'disabled="disabled" aria-disabled="true"' : ''; ?>>
								<span class="dashicons dashicons-admin-page"></span>
							</button>
							<button type="button" class="cm-action-btn cm-action-delete" data-template-id="<?php echo esc_attr( $tpl_id ); ?>" title="<?php esc_attr_e( 'Delete', 'checkmate-pdf-invoices' ); ?>">
								<span class="dashicons dashicons-trash"></span>
							</button>
						</div>
					</td>
				</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	</div>
	<?php else : ?>
	<!-- Empty State -->
	<div class="cm-empty-state">
		<div class="cm-empty-icon">
			<span class="dashicons dashicons-media-text"></span>
		</div>
		<h3 class="cm-empty-title"><?php esc_html_e( 'No templates yet', 'checkmate-pdf-invoices' ); ?></h3>
		<p class="cm-empty-desc"><?php esc_html_e( 'Create your first PDF template to get started. Choose from preset designs or start from scratch.', 'checkmate-pdf-invoices' ); ?></p>
		<button type="button" class="cm-btn cm-btn-primary" id="cm-create-first" <?php echo $template_limit_reached ? 'disabled="disabled" aria-disabled="true"' : ''; ?>>
			<span class="dashicons dashicons-plus"></span>
			<?php esc_html_e( 'Create Template', 'checkmate-pdf-invoices' ); ?>
		</button>
	</div>
	<?php endif; ?>
</div>

<!-- Preset Browser Modal -->
<div class="cm-modal cm-modal-lg" id="cm-preset-modal" aria-hidden="true">
	<div class="cm-modal-backdrop" data-cm-modal-close></div>
	<div class="cm-modal-dialog" role="dialog" aria-modal="true" aria-label="<?php esc_attr_e( 'Choose Template', 'checkmate-pdf-invoices' ); ?>">
		<div class="cm-modal-header">
			<div class="cm-modal-title"><?php esc_html_e( 'Choose a Template', 'checkmate-pdf-invoices' ); ?></div>
			<div class="cm-modal-actions">
				<button type="button" class="cm-btn cm-btn-glass" data-cm-modal-close>
					<span class="dashicons dashicons-no-alt"></span>
				</button>
			</div>
		</div>
		<div class="cm-modal-body cm-modal-body-scroll">
			<!-- Preset Type Filter -->
			<div class="cm-preset-filters">
				<button type="button" class="cm-preset-filter active" data-filter="all"><?php esc_html_e( 'All', 'checkmate-pdf-invoices' ); ?></button>
				<button type="button" class="cm-preset-filter" data-filter="invoice"><?php esc_html_e( 'Invoices', 'checkmate-pdf-invoices' ); ?></button>
				<button type="button" class="cm-preset-filter" data-filter="packing-slip"><?php esc_html_e( 'Packing Slips', 'checkmate-pdf-invoices' ); ?></button>
				<button type="button" class="cm-preset-filter" data-filter="credit-note"><?php esc_html_e( 'Credit Notes', 'checkmate-pdf-invoices' ); ?></button>
				<button type="button" class="cm-preset-filter" data-filter="delivery-note"><?php esc_html_e( 'Delivery Notes', 'checkmate-pdf-invoices' ); ?></button>
			</div>

			<!-- Preset Grid -->
			<div class="cm-preset-grid">
				<!-- Blank Template Card -->
				<div class="cm-preset-card cm-preset-blank" data-preset="blank" data-type="all">
					<div class="cm-preset-thumb">
						<span class="dashicons dashicons-plus-alt2"></span>
					</div>
					<div class="cm-preset-info">
						<h4 class="cm-preset-name"><?php esc_html_e( 'Blank Template', 'checkmate-pdf-invoices' ); ?></h4>
						<p class="cm-preset-desc"><?php esc_html_e( 'Start from scratch', 'checkmate-pdf-invoices' ); ?></p>
					</div>
				</div>

				<?php foreach ( $preset_templates as $preset_id => $preset ) : 
					$preset_type = $preset['document_type'] ?? 'invoice';
				?>
				<div class="cm-preset-card" data-preset="<?php echo esc_attr( $preset_id ); ?>" data-type="<?php echo esc_attr( $preset_type ); ?>">
					<div class="cm-preset-thumb">
						<div class="cm-preview-doc cm-preview-doc-sm">
							<div class="cm-preview-header"></div>
							<div class="cm-preview-line cm-preview-line-short"></div>
							<div class="cm-preview-line"></div>
							<div class="cm-preview-line cm-preview-line-medium"></div>
							<div class="cm-preview-table">
								<div class="cm-preview-row"></div>
								<div class="cm-preview-row"></div>
							</div>
						</div>
						<?php if ( ! empty( $preset['popular'] ) ) : ?>
						<span class="cm-preset-badge"><?php esc_html_e( 'Popular', 'checkmate-pdf-invoices' ); ?></span>
						<?php endif; ?>
					</div>
					<div class="cm-preset-info">
						<h4 class="cm-preset-name"><?php echo esc_html( $preset['name'] ?? $preset_id ); ?></h4>
						<p class="cm-preset-desc"><?php echo esc_html( $preset['description'] ?? '' ); ?></p>
						<span class="cm-preset-type"><?php echo esc_html( $type_labels[ $preset_type ] ?? $preset_type ); ?></span>
					</div>
					<div class="cm-preset-actions">
						<button type="button" class="cm-btn cm-btn-sm cm-btn-primary cm-preset-use"><?php esc_html_e( 'Use', 'checkmate-pdf-invoices' ); ?></button>
						<button type="button" class="cm-btn cm-btn-sm cm-btn-glass cm-preset-preview"><?php esc_html_e( 'Preview', 'checkmate-pdf-invoices' ); ?></button>
					</div>
				</div>
				<?php endforeach; ?>
			</div>
		</div>
	</div>
</div>

<!-- Template Preview Modal -->
<div class="cm-modal" id="cm-preview-modal" aria-hidden="true" data-return-modal="">
	<div class="cm-modal-backdrop" data-cm-preview-close></div>
	<div class="cm-modal-dialog" role="dialog" aria-modal="true" aria-label="<?php esc_attr_e( 'Template Preview', 'checkmate-pdf-invoices' ); ?>">
		<div class="cm-modal-header">
			<div class="cm-modal-title" id="cm-preview-title"><?php esc_html_e( 'Template Preview', 'checkmate-pdf-invoices' ); ?></div>
			<div class="cm-modal-actions">
				<button type="button" class="cm-btn cm-btn-glass" data-cm-preview-close>
					<span class="dashicons dashicons-no-alt"></span>
				</button>
			</div>
		</div>
		<div class="cm-modal-body">
			<div class="cm-modal-loading" id="cm-preview-loading">
				<div class="cm-spinner"></div>
			</div>
			<iframe class="cm-modal-iframe" id="cm-preview-iframe" title="<?php esc_attr_e( 'Template Preview', 'checkmate-pdf-invoices' ); ?>" sandbox="allow-same-origin"></iframe>
		</div>
	</div>
</div>

<!-- Confirm Delete Modal -->
<div class="cm-modal cm-modal-sm" id="cm-delete-modal" aria-hidden="true">
	<div class="cm-modal-backdrop" data-cm-modal-close></div>
	<div class="cm-modal-dialog" role="dialog" aria-modal="true" aria-label="<?php esc_attr_e( 'Confirm Delete', 'checkmate-pdf-invoices' ); ?>">
		<div class="cm-modal-header">
			<div class="cm-modal-title"><?php esc_html_e( 'Delete Template', 'checkmate-pdf-invoices' ); ?></div>
		</div>
		<div class="cm-modal-body cm-modal-body-pad">
			<p><?php esc_html_e( 'Are you sure you want to delete this template? This action cannot be undone.', 'checkmate-pdf-invoices' ); ?></p>
		</div>
		<div class="cm-modal-footer">
			<button type="button" class="cm-btn cm-btn-glass" data-cm-modal-close><?php esc_html_e( 'Cancel', 'checkmate-pdf-invoices' ); ?></button>
			<button type="button" class="cm-btn cm-btn-danger" id="cm-confirm-delete"><?php esc_html_e( 'Delete', 'checkmate-pdf-invoices' ); ?></button>
		</div>
	</div>
</div>

