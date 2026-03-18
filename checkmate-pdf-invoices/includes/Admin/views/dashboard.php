<?php
/**
 * Dashboard View - Premium UI
 *
 * Modern, premium dashboard with glassmorphism, bento grids, and clean aesthetics.
 *
 * @package Checkmate\PdfInvoices
 */

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Variables are local to this view file.

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$theme_mode = get_user_meta( get_current_user_id(), 'checkmate_pdf_theme_mode', true );
$allowed_theme_modes = [ 'dark', 'light', 'auto' ];
if ( ! in_array( $theme_mode, $allowed_theme_modes, true ) ) {
	$theme_mode = 'dark';
}

// Get template stats from database
use Checkmate\PdfInvoices\Editor\TemplateRepository;

$repository = TemplateRepository::instance();
$all_templates = $repository->find_all();
$total_templates = count( $all_templates );

// Count by document type
$invoice_templates = 0;
$packing_templates = 0;
$credit_templates = 0;

foreach ( $all_templates as $tpl ) {
	switch ( $tpl->get_document_type() ) {
		case 'invoice':
			$invoice_templates++;
			break;
		case 'packing-slip':
			$packing_templates++;
			break;
		case 'credit-note':
			$credit_templates++;
			break;
	}
}

// Document types for the dashboard
$document_types = [
	'invoice' => [
		'title'       => esc_html__( 'Invoice', 'checkmate-pdf-invoices' ),
		'description' => esc_html__( 'Professional invoice documents for completed orders', 'checkmate-pdf-invoices' ),
		'icon'        => 'dashicons-media-text',
		'color'       => '#0071e3',
		'count'       => $invoice_templates,
	],
	'packing-slip' => [
		'title'       => esc_html__( 'Packing Slip', 'checkmate-pdf-invoices' ),
		'description' => esc_html__( 'Shipping documents with order details', 'checkmate-pdf-invoices' ),
		'icon'        => 'dashicons-clipboard',
		'color'       => '#34c759',
		'count'       => $packing_templates,
	],
	'credit-note' => [
		'title'       => esc_html__( 'Credit Note', 'checkmate-pdf-invoices' ),
		'description' => esc_html__( 'Refund and credit documentation', 'checkmate-pdf-invoices' ),
		'icon'        => 'dashicons-money-alt',
		'color'       => '#af52de',
		'count'       => $credit_templates,
	],
	'delivery-note' => [
		'title'       => esc_html__( 'Delivery Note', 'checkmate-pdf-invoices' ),
		'description' => esc_html__( 'Delivery confirmation documents', 'checkmate-pdf-invoices' ),
		'icon'        => 'dashicons-car',
		'color'       => '#ff9500',
		'count'       => 0,
	],
];

// Features list
$features = [
	'wysiwyg-editor' => [
		'title'       => esc_html__( 'WYSIWYG Editor', 'checkmate-pdf-invoices' ),
		'description' => esc_html__( 'What you see is what you get. Edit templates visually with our block-based editor.', 'checkmate-pdf-invoices' ),
		'icon'        => 'dashicons-edit',
		'status'      => 'available',
	],
	'block-library' => [
		'title'       => esc_html__( 'Block Library', 'checkmate-pdf-invoices' ),
		'description' => esc_html__( 'Rich collection of blocks specifically designed for PDF documents.', 'checkmate-pdf-invoices' ),
		'icon'        => 'dashicons-screenoptions',
		'status'      => 'available',
	],
	'preset-templates' => [
		'title'       => esc_html__( 'Preset Templates', 'checkmate-pdf-invoices' ),
		'description' => esc_html__( 'Start with professionally designed templates and customize to your needs.', 'checkmate-pdf-invoices' ),
		'icon'        => 'dashicons-layout',
		'status'      => 'available',
	],
	'auto-attach' => [
		'title'       => esc_html__( 'Auto-Attach to Emails', 'checkmate-pdf-invoices' ),
		'description' => esc_html__( 'Automatically attach PDF documents to WooCommerce order emails.', 'checkmate-pdf-invoices' ),
		'icon'        => 'dashicons-email-alt',
		'status'      => 'available',
	],
	'bulk-generate' => [
		'title'       => esc_html__( 'Bulk Generation', 'checkmate-pdf-invoices' ),
		'description' => esc_html__( 'Generate PDFs for multiple orders at once with a single click.', 'checkmate-pdf-invoices' ),
		'icon'        => 'dashicons-download',
		'status'      => 'available',
	],
	'custom-fonts' => [
		'title'       => esc_html__( 'Custom Fonts', 'checkmate-pdf-invoices' ),
		'description' => esc_html__( 'Use any font you want in your PDF documents for perfect branding.', 'checkmate-pdf-invoices' ),
		'icon'        => 'dashicons-editor-textcolor',
		'status'      => 'coming-soon',
	],
];
?>

<div class="cm-wrap">
	<!-- Header -->
	<div class="cm-header">
		<div class="cm-header-content">
			<div class="cm-logo">
				<svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="url(#cm-logo-gradient)" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
					<defs>
						<linearGradient id="cm-logo-gradient" x1="0%" y1="0%" x2="100%" y2="100%">
							<stop offset="0%" style="stop-color:#0071e3"/>
							<stop offset="100%" style="stop-color:#34c759"/>
						</linearGradient>
					</defs>
					<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
					<polyline points="14 2 14 8 20 8"></polyline>
					<line x1="16" y1="13" x2="8" y2="13"></line>
					<line x1="16" y1="17" x2="8" y2="17"></line>
					<polyline points="10 9 9 9 8 9"></polyline>
				</svg>
			</div>
			<div>
				<h1 class="cm-title"><?php esc_html_e( 'Checkmate PDF', 'checkmate-pdf-invoices' ); ?></h1>
				<p class="cm-subtitle"><?php esc_html_e( 'Beautiful PDF invoices for WooCommerce. Design once, generate everywhere.', 'checkmate-pdf-invoices' ); ?></p>
			</div>
		</div>
		<div class="cm-header-actions">
			<div class="cm-segmented" id="cm-theme-segment" role="radiogroup" aria-label="<?php echo esc_attr__( 'Theme', 'checkmate-pdf-invoices' ); ?>" data-active="<?php echo esc_attr( $theme_mode ); ?>">
				<span class="cm-segmented-indicator" aria-hidden="true"></span>
				<button type="button" class="cm-segmented-btn" data-theme="light" aria-pressed="<?php echo esc_attr( $theme_mode === 'light' ? 'true' : 'false' ); ?>">
					<span class="cm-segmented-icon" aria-hidden="true">☀︎</span>
					<?php esc_html_e( 'Light', 'checkmate-pdf-invoices' ); ?>
				</button>
				<button type="button" class="cm-segmented-btn" data-theme="dark" aria-pressed="<?php echo esc_attr( $theme_mode === 'dark' ? 'true' : 'false' ); ?>">
					<span class="cm-segmented-icon" aria-hidden="true">☾</span>
					<?php esc_html_e( 'Dark', 'checkmate-pdf-invoices' ); ?>
				</button>
				<button type="button" class="cm-segmented-btn" data-theme="auto" aria-pressed="<?php echo esc_attr( $theme_mode === 'auto' ? 'true' : 'false' ); ?>">
					<span class="cm-segmented-icon" aria-hidden="true">◐</span>
					<?php esc_html_e( 'Auto', 'checkmate-pdf-invoices' ); ?>
				</button>
			</div>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=checkmate-pdf-templates&action=create' ) ); ?>" class="cm-btn cm-btn-primary">
				<span class="dashicons dashicons-plus-alt2"></span>
				<?php esc_html_e( 'Create Template', 'checkmate-pdf-invoices' ); ?>
			</a>
		</div>
	</div>

	<!-- Hero Bento Grid -->
	<div class="cm-bento">
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=checkmate-pdf-templates&action=create' ) ); ?>" class="cm-bento-card cm-bento-hero cm-bento-card-link" aria-label="<?php echo esc_attr__( 'Browse Templates', 'checkmate-pdf-invoices' ); ?>">
			<div class="cm-bento-bg" style="background: linear-gradient(135deg, #0071e3, #34c759); width: 400px; height: 400px; top: -150px; right: -100px;"></div>
			<div class="cm-bento-bg" style="background: #af52de; width: 200px; height: 200px; bottom: -50px; left: -50px;"></div>
			<div class="cm-bento-content">
				<span class="cm-bento-badge"><?php esc_html_e( 'Getting Started', 'checkmate-pdf-invoices' ); ?></span>
				<h2 class="cm-bento-title"><?php esc_html_e( 'Create your first PDF template', 'checkmate-pdf-invoices' ); ?></h2>
				<p class="cm-bento-desc"><?php esc_html_e( 'Choose from preset designs or start from scratch. Our WYSIWYG editor makes customization effortless.', 'checkmate-pdf-invoices' ); ?></p>
				<span class="cm-btn cm-btn-glass" aria-hidden="true">
					<?php esc_html_e( 'Browse Templates', 'checkmate-pdf-invoices' ); ?>
					<span class="cm-btn-arrow">→</span>
				</span>
			</div>
			<div class="cm-bento-visual">
				<div class="cm-preview-doc">
					<div class="cm-preview-header"></div>
					<div class="cm-preview-line cm-preview-line-short"></div>
					<div class="cm-preview-line"></div>
					<div class="cm-preview-line"></div>
					<div class="cm-preview-line cm-preview-line-medium"></div>
					<div class="cm-preview-table">
						<div class="cm-preview-row"></div>
						<div class="cm-preview-row"></div>
						<div class="cm-preview-row"></div>
					</div>
					<div class="cm-preview-footer"></div>
				</div>
			</div>
		</a>

		<div class="cm-bento-card">
			<div class="cm-bento-bg" style="background: #0071e3; width: 150px; height: 150px; top: -40px; right: -40px;"></div>
			<span class="cm-bento-label"><?php esc_html_e( 'Templates', 'checkmate-pdf-invoices' ); ?></span>
			<div class="cm-bento-stat">
				<div class="cm-bento-value"><?php echo esc_html( $total_templates ); ?></div>
				<div class="cm-bento-stat-desc"><?php esc_html_e( 'Active Templates', 'checkmate-pdf-invoices' ); ?></div>
			</div>
		</div>

		<div class="cm-bento-card">
			<div class="cm-bento-bg" style="background: #34c759; width: 150px; height: 150px; bottom: -40px; left: -40px;"></div>
			<span class="cm-bento-label"><?php esc_html_e( 'Document Types', 'checkmate-pdf-invoices' ); ?></span>
			<div class="cm-bento-stat">
				<div class="cm-bento-value"><?php echo esc_html( count( $document_types ) ); ?></div>
				<div class="cm-bento-stat-desc"><?php esc_html_e( 'Supported Types', 'checkmate-pdf-invoices' ); ?></div>
			</div>
		</div>
	</div>

	<!-- Document Types Section -->
	<section class="cm-section">
		<div class="cm-section-header">
			<h2 class="cm-section-title"><?php esc_html_e( 'Document Types', 'checkmate-pdf-invoices' ); ?></h2>
			<p class="cm-section-subtitle"><?php esc_html_e( 'Create templates for different document types', 'checkmate-pdf-invoices' ); ?></p>
		</div>

		<div class="cm-doc-grid">
			<?php foreach ( $document_types as $type_id => $type ) : ?>
			<div class="cm-doc-card" data-type="<?php echo esc_attr( $type_id ); ?>">
				<div class="cm-doc-icon" style="background: <?php echo esc_attr( $type['color'] ); ?>20; color: <?php echo esc_attr( $type['color'] ); ?>">
					<span class="dashicons <?php echo esc_attr( $type['icon'] ); ?>"></span>
				</div>
				<div class="cm-doc-content">
					<h3 class="cm-doc-title"><?php echo esc_html( $type['title'] ); ?></h3>
					<p class="cm-doc-desc"><?php echo esc_html( $type['description'] ); ?></p>
				</div>
				<div class="cm-doc-actions">
					<span class="cm-doc-count"><?php echo esc_html( $type['count'] ); ?> <?php esc_html_e( 'templates', 'checkmate-pdf-invoices' ); ?></span>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=checkmate-pdf-templates&type=' . $type_id ) ); ?>" class="cm-doc-link">
						<?php esc_html_e( 'Manage', 'checkmate-pdf-invoices' ); ?>
						<span class="cm-arrow">→</span>
					</a>
				</div>
			</div>
			<?php endforeach; ?>
		</div>
	</section>

	<!-- Features Section -->
	<section class="cm-section">
		<div class="cm-section-header">
			<h2 class="cm-section-title"><?php esc_html_e( 'Features', 'checkmate-pdf-invoices' ); ?></h2>
			<p class="cm-section-subtitle"><?php esc_html_e( 'Everything you need to create professional PDF documents', 'checkmate-pdf-invoices' ); ?></p>
		</div>

		<div class="cm-features-grid">
			<?php foreach ( $features as $feature_id => $feature ) : ?>
			<div class="cm-feature-card <?php echo esc_attr( $feature['status'] === 'coming-soon' ? 'cm-feature-coming' : '' ); ?>">
				<div class="cm-feature-icon">
					<span class="dashicons <?php echo esc_attr( $feature['icon'] ); ?>"></span>
				</div>
				<div class="cm-feature-content">
					<h3 class="cm-feature-title">
						<?php echo esc_html( $feature['title'] ); ?>
						<?php if ( $feature['status'] === 'coming-soon' ) : ?>
						<span class="cm-badge cm-badge-soon"><?php esc_html_e( 'Soon', 'checkmate-pdf-invoices' ); ?></span>
						<?php endif; ?>
					</h3>
					<p class="cm-feature-desc"><?php echo esc_html( $feature['description'] ); ?></p>
				</div>
			</div>
			<?php endforeach; ?>
		</div>
	</section>

	<!-- Quick Actions -->
	<section class="cm-section">
		<div class="cm-section-header">
			<h2 class="cm-section-title"><?php esc_html_e( 'Quick Actions', 'checkmate-pdf-invoices' ); ?></h2>
		</div>

		<div class="cm-actions-grid">
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=checkmate-pdf-templates' ) ); ?>" class="cm-action-card">
				<div class="cm-action-icon" style="background: linear-gradient(135deg, #0071e3, #00a2ff);">
					<span class="dashicons dashicons-plus-alt2"></span>
				</div>
				<span class="cm-action-title"><?php esc_html_e( 'New Template', 'checkmate-pdf-invoices' ); ?></span>
			</a>

			<a href="<?php echo esc_url( admin_url( 'admin.php?page=checkmate-pdf-settings' ) ); ?>" class="cm-action-card">
				<div class="cm-action-icon" style="background: linear-gradient(135deg, #5856d6, #af52de);">
					<span class="dashicons dashicons-admin-generic"></span>
				</div>
				<span class="cm-action-title"><?php esc_html_e( 'Settings', 'checkmate-pdf-invoices' ); ?></span>
			</a>

			<a href="<?php echo esc_url( admin_url( 'edit.php?post_type=shop_order' ) ); ?>" class="cm-action-card">
				<div class="cm-action-icon" style="background: linear-gradient(135deg, #34c759, #30d158);">
					<span class="dashicons dashicons-list-view"></span>
				</div>
				<span class="cm-action-title"><?php esc_html_e( 'View Orders', 'checkmate-pdf-invoices' ); ?></span>
			</a>
		</div>
	</section>

	<!-- Footer -->
	<div class="cm-footer">
		<p class="cm-footer-text">
			<?php
			printf(
				/* translators: %s: plugin version */
				esc_html__( 'Checkmate PDF Invoices v%s', 'checkmate-pdf-invoices' ),
				'2.0.1'
			);
			?>
			<span class="cm-footer-sep">•</span>
			<?php esc_html_e( 'Made with ♥ for WooCommerce', 'checkmate-pdf-invoices' ); ?>
		</p>
	</div>
</div>
