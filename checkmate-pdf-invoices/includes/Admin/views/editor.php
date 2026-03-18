<?php
/**
 * Template Editor Page
 *
 * @package Checkmate\PdfInvoices\Admin
 */

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Variables are local to this view file.

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Checkmate\PdfInvoices\Editor\BlockRegistry;
use Checkmate\PdfInvoices\Editor\Template;
use Checkmate\PdfInvoices\Editor\TemplateRepository;
use Checkmate\PdfInvoices\Editor\PresetTemplates;

/**
 * Get block icon SVG
 *
 * @param string $icon_name Icon name.
 * @return string SVG HTML.
 */
if ( ! function_exists( 'checkmate_get_block_icon' ) ) {
function checkmate_get_block_icon( string $icon_name ): string {
	$icons = [
		'columns'       => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><line x1="12" y1="3" x2="12" y2="21"/></svg>',
		'square'        => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/></svg>',
		'more-vertical' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="5" r="1"/><circle cx="12" cy="12" r="1"/><circle cx="12" cy="19" r="1"/></svg>',
		'minus'         => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="5" y1="12" x2="19" y2="12"/></svg>',
		'image'         => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="M21 15l-5-5L5 21"/></svg>',
		'type'          => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="4 7 4 4 20 4 20 7"/><line x1="9" y1="20" x2="15" y2="20"/><line x1="12" y1="4" x2="12" y2="20"/></svg>',
		'bold'          => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 4h8a4 4 0 0 1 4 4 4 4 0 0 1-4 4H6z"/><path d="M6 12h9a4 4 0 0 1 4 4 4 4 0 0 1-4 4H6z"/></svg>',
		'file-text'     => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>',
		'hash'          => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="4" y1="9" x2="20" y2="9"/><line x1="4" y1="15" x2="20" y2="15"/><line x1="10" y1="3" x2="8" y2="21"/><line x1="16" y1="3" x2="14" y2="21"/></svg>',
		'calendar'      => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>',
		'briefcase'     => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/></svg>',
		'map-pin'       => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>',
		'shopping-cart' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>',
		'credit-card'   => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="1" y="4" width="22" height="16" rx="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>',
		'truck'         => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="1" y="3" width="15" height="13"/><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg>',
		'message-square' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>',
		'list'          => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><line x1="3" y1="6" x2="3.01" y2="6"/><line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/></svg>',
		'dollar-sign'   => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>',
		'edit-3'        => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/></svg>',
		'align-left'    => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="17" y1="10" x2="3" y2="10"/><line x1="21" y1="6" x2="3" y2="6"/><line x1="21" y1="14" x2="3" y2="14"/><line x1="17" y1="18" x2="3" y2="18"/></svg>',
	];

	return $icons[ $icon_name ] ?? '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/></svg>';
}
} // end function_exists check

// Get template ID from URL.
// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only GET parameters for page display.
$template_id = isset( $_GET['template_id'] ) ? absint( wp_unslash( $_GET['template_id'] ) ) : 0;
// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only GET parameters for page display.
$preset_id   = isset( $_GET['preset'] ) ? sanitize_key( (string) wp_unslash( $_GET['preset'] ) ) : '';
// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only GET parameters for page display.
$doc_type    = isset( $_GET['document_type'] ) ? sanitize_key( (string) wp_unslash( $_GET['document_type'] ) ) : 'invoice';

// Initialize template
$repository = TemplateRepository::instance();
$registry   = BlockRegistry::instance();
$template   = null;

if ( $template_id > 0 ) {
	$template = $repository->find( $template_id );
}

if ( ! $template && ! empty( $preset_id ) ) {
	$template = PresetTemplates::create_template( $preset_id );
}

if ( ! $template ) {
	$template = new Template( [
		'name'          => __( 'New Template', 'checkmate-pdf-invoices' ),
		'document_type' => $doc_type,
		'blocks'        => [],
		'page_settings' => Template::get_default_page_settings(),
	] );
}

// Get blocks grouped by category
$blocks_grouped = $registry->get_blocks_grouped();

// Paper sizes for dropdown
$paper_sizes = [
	'a4'     => 'A4 (210 × 297 mm)',
	'letter' => 'Letter (8.5 × 11 in)',
	'legal'  => 'Legal (8.5 × 14 in)',
	'a3'     => 'A3 (297 × 420 mm)',
	'a5'     => 'A5 (148 × 210 mm)',
];

// Available fonts
$fonts = [
	'DejaVu Sans' => 'DejaVu Sans (Default)',
	'Helvetica'   => 'Helvetica',
	'Times'       => 'Times',
	'Courier'     => 'Courier',
];

// Document types
$document_types = [
	'invoice'       => __( 'Invoice', 'checkmate-pdf-invoices' ),
	'packing-slip'  => __( 'Packing Slip', 'checkmate-pdf-invoices' ),
	'credit-note'   => __( 'Credit Note', 'checkmate-pdf-invoices' ),
	'delivery-note' => __( 'Delivery Note', 'checkmate-pdf-invoices' ),
];

$page_settings = $template->get_page_settings();
$dimensions    = $template->get_paper_dimensions();
$content_dims  = $template->get_content_dimensions();
?>
<div class="checkmate-editor-wrap" data-theme="<?php echo esc_attr( get_user_meta( get_current_user_id(), 'checkmate_theme_mode', true ) ?: 'light' ); ?>">
	
	<!-- Editor Header -->
	<header class="editor-header">
		<div class="editor-header-left">
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=checkmate-pdf-templates' ) ); ?>" class="editor-back-btn">
				<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
					<path d="M19 12H5M12 19l-7-7 7-7"/>
				</svg>
			</a>
			<div class="template-name-wrap">
				<input type="text" 
					   id="template-name" 
					   class="template-name-input" 
					   value="<?php echo esc_attr( $template->get_name() ); ?>" 
					   placeholder="<?php esc_attr_e( 'Template Name', 'checkmate-pdf-invoices' ); ?>">
				<select id="document-type" class="document-type-select">
					<?php foreach ( $document_types as $type => $label ) : ?>
						<option value="<?php echo esc_attr( $type ); ?>" <?php selected( $template->get_document_type(), $type ); ?>>
							<?php echo esc_html( $label ); ?>
						</option>
					<?php endforeach; ?>
				</select>
			</div>
		</div>
		
		<div class="editor-header-center">
			<div class="view-toggle">
				<button type="button" class="view-btn active" data-view="editor" title="<?php esc_attr_e( 'Editor', 'checkmate-pdf-invoices' ); ?>">
					<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
						<rect x="3" y="3" width="18" height="18" rx="2"/>
						<path d="M9 3v18M3 9h6"/>
					</svg>
				</button>
				<button type="button" class="view-btn" data-view="preview" title="<?php esc_attr_e( 'Preview', 'checkmate-pdf-invoices' ); ?>">
					<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
						<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
						<circle cx="12" cy="12" r="3"/>
					</svg>
				</button>
				<button type="button" class="view-btn" data-view="split" title="<?php esc_attr_e( 'Split View', 'checkmate-pdf-invoices' ); ?>">
					<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
						<rect x="3" y="3" width="18" height="18" rx="2"/>
						<line x1="12" y1="3" x2="12" y2="21"/>
					</svg>
				</button>
			</div>
		</div>
		
		<div class="editor-header-right">
			<button type="button" class="editor-btn secondary" id="btn-undo" disabled title="<?php esc_attr_e( 'Undo', 'checkmate-pdf-invoices' ); ?>">
				<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
					<path d="M3 7v6h6M21 17a9 9 0 0 0-9-9 9 9 0 0 0-6 2.3L3 13"/>
				</svg>
			</button>
			<button type="button" class="editor-btn secondary" id="btn-redo" disabled title="<?php esc_attr_e( 'Redo', 'checkmate-pdf-invoices' ); ?>">
				<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
					<path d="M21 7v6h-6M3 17a9 9 0 0 1 9-9 9 9 0 0 1 6 2.3L21 13"/>
				</svg>
			</button>
			<div class="header-divider"></div>
			<button type="button" class="editor-btn secondary" id="btn-download-pdf">
				<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
					<path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4M7 10l5 5 5-5M12 15V3"/>
				</svg>
				<?php esc_html_e( 'Download PDF', 'checkmate-pdf-invoices' ); ?>
			</button>
			<button type="button" class="editor-btn primary" id="btn-save-template">
				<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
					<path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/>
					<polyline points="17 21 17 13 7 13 7 21"/>
					<polyline points="7 3 7 8 15 8"/>
				</svg>
				<?php esc_html_e( 'Save', 'checkmate-pdf-invoices' ); ?>
			</button>
		</div>
	</header>

	<!-- Main Editor Area -->
	<div class="editor-main">
		
		<!-- Blocks Panel (Left Sidebar) -->
		<aside class="blocks-panel">
			<div class="panel-header">
				<h3><?php esc_html_e( 'Blocks', 'checkmate-pdf-invoices' ); ?></h3>
				<button type="button" class="panel-search-toggle" title="<?php esc_attr_e( 'Search blocks', 'checkmate-pdf-invoices' ); ?>">
					<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
						<circle cx="11" cy="11" r="8"/>
						<path d="M21 21l-4.35-4.35"/>
					</svg>
				</button>
			</div>
			
			<div class="blocks-search" style="display: none;">
				<input type="text" id="blocks-search-input" placeholder="<?php esc_attr_e( 'Search blocks...', 'checkmate-pdf-invoices' ); ?>">
			</div>
			
			<div class="blocks-list">
				<?php foreach ( $blocks_grouped as $cat_id => $group ) : ?>
					<div class="blocks-category" data-category="<?php echo esc_attr( $cat_id ); ?>">
						<button type="button" class="category-header">
							<span class="category-title"><?php echo esc_html( $group['category']['title'] ); ?></span>
							<svg class="category-chevron" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
								<polyline points="6 9 12 15 18 9"/>
							</svg>
						</button>
						<div class="category-blocks">
							<?php foreach ( $group['blocks'] as $block_type => $block ) : ?>
								<?php if ( 'column' === $block_type ) : ?>
									<?php continue; ?>
								<?php endif; ?>
								<div class="block-item" 
									 draggable="true" 
									 data-block-type="<?php echo esc_attr( $block_type ); ?>"
									 title="<?php echo esc_attr( $block['description'] ?? '' ); ?>">
									<span class="block-icon">
									<?php
									// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- SVG markup is safe, generated internally
									echo checkmate_get_block_icon( $block['icon'] ?? 'square' );
									?>
									</span>
									<span class="block-title"><?php echo esc_html( $block['title'] ?? $block_type ); ?></span>
								</div>
							<?php endforeach; ?>
						</div>
					</div>
				<?php endforeach; ?>
			</div>
		</aside>

		<!-- Canvas Area -->
		<div class="editor-canvas-wrap">
			<div class="canvas-toolbar">
				<div class="zoom-controls">
					<button type="button" class="zoom-btn" id="zoom-out" title="<?php esc_attr_e( 'Zoom Out', 'checkmate-pdf-invoices' ); ?>">
						<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
							<circle cx="11" cy="11" r="8"/>
							<path d="M21 21l-4.35-4.35M8 11h6"/>
						</svg>
					</button>
					<span class="zoom-level" id="zoom-level">100%</span>
					<button type="button" class="zoom-btn" id="zoom-in" title="<?php esc_attr_e( 'Zoom In', 'checkmate-pdf-invoices' ); ?>">
						<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
							<circle cx="11" cy="11" r="8"/>
							<path d="M21 21l-4.35-4.35M11 8v6M8 11h6"/>
						</svg>
					</button>
					<button type="button" class="zoom-btn" id="zoom-fit" title="<?php esc_attr_e( 'Fit to Screen', 'checkmate-pdf-invoices' ); ?>">
						<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
							<path d="M8 3H5a2 2 0 0 0-2 2v3m18 0V5a2 2 0 0 0-2-2h-3m0 18h3a2 2 0 0 0 2-2v-3M3 16v3a2 2 0 0 0 2 2h3"/>
						</svg>
					</button>
				</div>
				
				<div class="page-info">
					<span class="paper-size"><?php echo esc_html( $paper_sizes[ $page_settings['paperSize'] ] ?? 'A4' ); ?></span>
					<span class="orientation"><?php echo esc_html( ucfirst( $page_settings['orientation'] ) ); ?></span>
				</div>
			</div>
			
			<div class="canvas-container" id="canvas-container">
				<div class="pdf-canvas" 
					 id="pdf-canvas"
					 style="--paper-width: <?php echo esc_attr( $dimensions['width'] ); ?>mm; 
							--paper-height: <?php echo esc_attr( $dimensions['height'] ); ?>mm;
							--margin-top: <?php echo esc_attr( $page_settings['marginTop'] ); ?>mm;
							--margin-right: <?php echo esc_attr( $page_settings['marginRight'] ); ?>mm;
							--margin-bottom: <?php echo esc_attr( $page_settings['marginBottom'] ); ?>mm;
							--margin-left: <?php echo esc_attr( $page_settings['marginLeft'] ); ?>mm;
							--base-font-size: <?php echo esc_attr( $page_settings['baseFontSize'] ); ?>pt;
							--text-color: <?php echo esc_attr( $page_settings['textColor'] ); ?>;
							--bg-color: <?php echo esc_attr( $page_settings['backgroundColor'] ); ?>;">
					<div class="canvas-content" id="canvas-content">
						<!-- Blocks will be rendered here -->
						<div class="canvas-empty-state" id="canvas-empty">
							<svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
								<rect x="3" y="3" width="18" height="18" rx="2"/>
								<path d="M12 8v8M8 12h8"/>
							</svg>
							<h4><?php esc_html_e( 'Start building your template', 'checkmate-pdf-invoices' ); ?></h4>
							<p><?php esc_html_e( 'Drag blocks from the left panel or click to add', 'checkmate-pdf-invoices' ); ?></p>
						</div>
					</div>
				</div>
			</div>
		</div>

		<!-- Preview Panel (for split view) -->
		<div class="preview-panel" id="preview-panel" style="display: none;">
			<div class="preview-toolbar">
				<span class="preview-title"><?php esc_html_e( 'Live Preview', 'checkmate-pdf-invoices' ); ?></span>
				<button type="button" class="preview-refresh-btn" id="btn-refresh-preview" title="<?php esc_attr_e( 'Refresh Preview', 'checkmate-pdf-invoices' ); ?>">
					<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
						<path d="M23 4v6h-6M1 20v-6h6"/>
						<path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"/>
					</svg>
				</button>
			</div>
			<div class="preview-container">
				<iframe id="preview-iframe" src="about:blank"></iframe>
			</div>
		</div>

		<!-- Settings Panel (Right Sidebar) -->
		<aside class="settings-panel" id="settings-panel">
			<!-- Block Settings -->
			<div class="settings-section block-settings" id="block-settings" style="display: none;">
				<div class="panel-header">
					<h3 id="block-settings-title"><?php esc_html_e( 'Block Settings', 'checkmate-pdf-invoices' ); ?></h3>
					<button type="button" class="delete-block-btn" id="btn-delete-block" title="<?php esc_attr_e( 'Delete Block', 'checkmate-pdf-invoices' ); ?>">
						<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
							<polyline points="3 6 5 6 21 6"/>
							<path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
						</svg>
					</button>
				</div>
				<div class="settings-content" id="block-settings-content">
					<!-- Dynamic block settings will be rendered here -->
				</div>
			</div>

			<!-- Page Settings -->
			<div class="settings-section page-settings" id="page-settings">
				<div class="panel-header">
					<h3><?php esc_html_e( 'Page Settings', 'checkmate-pdf-invoices' ); ?></h3>
				</div>
				<div class="settings-content">
					<div class="setting-group">
						<label for="paper-size"><?php esc_html_e( 'Paper Size', 'checkmate-pdf-invoices' ); ?></label>
						<select id="paper-size" class="setting-select">
							<?php foreach ( $paper_sizes as $size => $label ) : ?>
								<option value="<?php echo esc_attr( $size ); ?>" <?php selected( $page_settings['paperSize'], $size ); ?>>
									<?php echo esc_html( $label ); ?>
								</option>
							<?php endforeach; ?>
						</select>
					</div>
					
					<div class="setting-group">
						<label><?php esc_html_e( 'Orientation', 'checkmate-pdf-invoices' ); ?></label>
						<div class="orientation-toggle">
							<button type="button" class="orientation-btn <?php echo esc_attr( $page_settings['orientation'] === 'portrait' ? 'active' : '' ); ?>" 
									data-orientation="portrait" 
									title="<?php esc_attr_e( 'Portrait', 'checkmate-pdf-invoices' ); ?>">
								<svg width="20" height="26" viewBox="0 0 20 26" fill="none" stroke="currentColor" stroke-width="1.5">
									<rect x="1" y="1" width="18" height="24" rx="2"/>
								</svg>
							</button>
							<button type="button" class="orientation-btn <?php echo esc_attr( $page_settings['orientation'] === 'landscape' ? 'active' : '' ); ?>" 
									data-orientation="landscape" 
									title="<?php esc_attr_e( 'Landscape', 'checkmate-pdf-invoices' ); ?>">
								<svg width="26" height="20" viewBox="0 0 26 20" fill="none" stroke="currentColor" stroke-width="1.5">
									<rect x="1" y="1" width="24" height="18" rx="2"/>
								</svg>
							</button>
						</div>
					</div>
					
					<div class="setting-group">
						<label><?php esc_html_e( 'Margins (mm)', 'checkmate-pdf-invoices' ); ?></label>
						<div class="margins-grid">
							<div class="margin-input">
								<input type="number" id="margin-top" value="<?php echo esc_attr( $page_settings['marginTop'] ); ?>" min="0" max="100">
								<span>Top</span>
							</div>
							<div class="margin-input">
								<input type="number" id="margin-right" value="<?php echo esc_attr( $page_settings['marginRight'] ); ?>" min="0" max="100">
								<span>Right</span>
							</div>
							<div class="margin-input">
								<input type="number" id="margin-bottom" value="<?php echo esc_attr( $page_settings['marginBottom'] ); ?>" min="0" max="100">
								<span>Bottom</span>
							</div>
							<div class="margin-input">
								<input type="number" id="margin-left" value="<?php echo esc_attr( $page_settings['marginLeft'] ); ?>" min="0" max="100">
								<span>Left</span>
							</div>
						</div>
					</div>
					
					<div class="setting-group">
						<label for="font-family"><?php esc_html_e( 'Font Family', 'checkmate-pdf-invoices' ); ?></label>
						<select id="font-family" class="setting-select">
							<?php foreach ( $fonts as $font => $label ) : ?>
								<option value="<?php echo esc_attr( $font ); ?>" <?php selected( $page_settings['fontFamily'], $font ); ?>>
									<?php echo esc_html( $label ); ?>
								</option>
							<?php endforeach; ?>
						</select>
					</div>
					
					<div class="setting-group">
						<label for="base-font-size"><?php esc_html_e( 'Base Font Size (pt)', 'checkmate-pdf-invoices' ); ?></label>
						<input type="number" id="base-font-size" value="<?php echo esc_attr( $page_settings['baseFontSize'] ); ?>" min="6" max="18">
					</div>
					
					<div class="setting-group">
						<label for="text-color"><?php esc_html_e( 'Text Color', 'checkmate-pdf-invoices' ); ?></label>
						<div class="color-input-wrap">
							<input type="color" id="text-color" value="<?php echo esc_attr( $page_settings['textColor'] ); ?>">
							<input type="text" id="text-color-hex" value="<?php echo esc_attr( $page_settings['textColor'] ); ?>" maxlength="7">
						</div>
					</div>
					
					<div class="setting-group">
						<label for="bg-color"><?php esc_html_e( 'Background Color', 'checkmate-pdf-invoices' ); ?></label>
						<div class="color-input-wrap">
							<input type="color" id="bg-color" value="<?php echo esc_attr( $page_settings['backgroundColor'] ); ?>">
							<input type="text" id="bg-color-hex" value="<?php echo esc_attr( $page_settings['backgroundColor'] ); ?>" maxlength="7">
						</div>
					</div>
					
					<div class="setting-group">
						<label><?php esc_html_e( 'Background Image', 'checkmate-pdf-invoices' ); ?></label>
						<div class="image-upload-wrap">
							<input type="hidden" id="page-bg-image" value="<?php echo esc_attr( $page_settings['backgroundImage'] ?? '' ); ?>">
							<div id="page-bg-image-preview" class="image-preview" style="<?php echo esc_attr( ! empty( $page_settings['backgroundImage'] ) ? '' : 'display: none;' ); ?>">
								<img src="<?php echo esc_url( $page_settings['backgroundImage'] ?? '' ); ?>" alt="">
								<button type="button" class="remove-image" data-target="page-bg-image">&times;</button>
							</div>
							<button type="button" class="button upload-image-btn" data-target="page-bg-image" data-preview="page-bg-image-preview"><?php esc_html_e( 'Select Image', 'checkmate-pdf-invoices' ); ?></button>
						</div>
					</div>
					
					<div class="setting-group" id="page-bg-repeat-group" style="<?php echo esc_attr( ! empty( $page_settings['backgroundImage'] ) ? '' : 'display: none;' ); ?>">
						<label for="page-bg-repeat"><?php esc_html_e( 'Background Repeat', 'checkmate-pdf-invoices' ); ?></label>
						<select id="page-bg-repeat" class="setting-select">
							<option value="no-repeat" <?php selected( $page_settings['backgroundRepeat'] ?? 'no-repeat', 'no-repeat' ); ?>><?php esc_html_e( 'No Repeat', 'checkmate-pdf-invoices' ); ?></option>
							<option value="repeat" <?php selected( $page_settings['backgroundRepeat'] ?? 'no-repeat', 'repeat' ); ?>><?php esc_html_e( 'Repeat', 'checkmate-pdf-invoices' ); ?></option>
							<option value="repeat-x" <?php selected( $page_settings['backgroundRepeat'] ?? 'no-repeat', 'repeat-x' ); ?>><?php esc_html_e( 'Repeat X', 'checkmate-pdf-invoices' ); ?></option>
							<option value="repeat-y" <?php selected( $page_settings['backgroundRepeat'] ?? 'no-repeat', 'repeat-y' ); ?>><?php esc_html_e( 'Repeat Y', 'checkmate-pdf-invoices' ); ?></option>
						</select>
					</div>
					
					<div class="setting-group" id="page-bg-position-group" style="<?php echo esc_attr( ! empty( $page_settings['backgroundImage'] ) ? '' : 'display: none;' ); ?>">
						<label for="page-bg-position"><?php esc_html_e( 'Background Position', 'checkmate-pdf-invoices' ); ?></label>
						<select id="page-bg-position" class="setting-select">
							<option value="top left" <?php selected( $page_settings['backgroundPosition'] ?? 'top left', 'top left' ); ?>><?php esc_html_e( 'Top Left', 'checkmate-pdf-invoices' ); ?></option>
							<option value="top center" <?php selected( $page_settings['backgroundPosition'] ?? 'top left', 'top center' ); ?>><?php esc_html_e( 'Top Center', 'checkmate-pdf-invoices' ); ?></option>
							<option value="top right" <?php selected( $page_settings['backgroundPosition'] ?? 'top left', 'top right' ); ?>><?php esc_html_e( 'Top Right', 'checkmate-pdf-invoices' ); ?></option>
							<option value="center left" <?php selected( $page_settings['backgroundPosition'] ?? 'top left', 'center left' ); ?>><?php esc_html_e( 'Center Left', 'checkmate-pdf-invoices' ); ?></option>
							<option value="center center" <?php selected( $page_settings['backgroundPosition'] ?? 'top left', 'center center' ); ?>><?php esc_html_e( 'Center', 'checkmate-pdf-invoices' ); ?></option>
							<option value="center right" <?php selected( $page_settings['backgroundPosition'] ?? 'top left', 'center right' ); ?>><?php esc_html_e( 'Center Right', 'checkmate-pdf-invoices' ); ?></option>
							<option value="bottom left" <?php selected( $page_settings['backgroundPosition'] ?? 'top left', 'bottom left' ); ?>><?php esc_html_e( 'Bottom Left', 'checkmate-pdf-invoices' ); ?></option>
							<option value="bottom center" <?php selected( $page_settings['backgroundPosition'] ?? 'top left', 'bottom center' ); ?>><?php esc_html_e( 'Bottom Center', 'checkmate-pdf-invoices' ); ?></option>
							<option value="bottom right" <?php selected( $page_settings['backgroundPosition'] ?? 'top left', 'bottom right' ); ?>><?php esc_html_e( 'Bottom Right', 'checkmate-pdf-invoices' ); ?></option>
						</select>
					</div>
					
					<div class="setting-group" id="page-bg-size-group" style="<?php echo esc_attr( ! empty( $page_settings['backgroundImage'] ) ? '' : 'display: none;' ); ?>">
						<label for="page-bg-size"><?php esc_html_e( 'Background Size', 'checkmate-pdf-invoices' ); ?></label>
						<select id="page-bg-size" class="setting-select">
							<option value="auto" <?php selected( $page_settings['backgroundSize'] ?? 'auto', 'auto' ); ?>><?php esc_html_e( 'Auto', 'checkmate-pdf-invoices' ); ?></option>
							<option value="cover" <?php selected( $page_settings['backgroundSize'] ?? 'auto', 'cover' ); ?>><?php esc_html_e( 'Cover', 'checkmate-pdf-invoices' ); ?></option>
							<option value="contain" <?php selected( $page_settings['backgroundSize'] ?? 'auto', 'contain' ); ?>><?php esc_html_e( 'Contain', 'checkmate-pdf-invoices' ); ?></option>
							<option value="100% 100%" <?php selected( $page_settings['backgroundSize'] ?? 'auto', '100% 100%' ); ?>><?php esc_html_e( '100%', 'checkmate-pdf-invoices' ); ?></option>
						</select>
					</div>
				</div>
			</div>
		</aside>
	</div>

	<!-- Block Hover Toolbar (appears on block hover) -->
	<div class="block-toolbar" id="block-toolbar" style="display: none;">
		<button type="button" class="toolbar-btn" data-action="move-up" title="<?php esc_attr_e( 'Move Up', 'checkmate-pdf-invoices' ); ?>">
			<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
				<polyline points="18 15 12 9 6 15"/>
			</svg>
		</button>
		<button type="button" class="toolbar-btn" data-action="move-down" title="<?php esc_attr_e( 'Move Down', 'checkmate-pdf-invoices' ); ?>">
			<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
				<polyline points="6 9 12 15 18 9"/>
			</svg>
		</button>
		<span class="toolbar-divider"></span>
		<!-- Row-specific: Add/Remove columns -->
		<button type="button" class="toolbar-btn row-only" data-action="add-column" title="<?php esc_attr_e( 'Add Column', 'checkmate-pdf-invoices' ); ?>">
			<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
				<rect x="3" y="3" width="18" height="18" rx="2"/>
				<line x1="12" y1="8" x2="12" y2="16"/>
				<line x1="8" y1="12" x2="16" y2="12"/>
			</svg>
		</button>
		<span class="toolbar-divider row-only"></span>
		<!-- Column-specific: Move left/right + remove column -->
		<button type="button" class="toolbar-btn column-only" data-action="move-left" title="<?php esc_attr_e( 'Move Column Left', 'checkmate-pdf-invoices' ); ?>">
			<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
				<polyline points="15 18 9 12 15 6"/>
			</svg>
		</button>
		<button type="button" class="toolbar-btn column-only" data-action="move-right" title="<?php esc_attr_e( 'Move Column Right', 'checkmate-pdf-invoices' ); ?>">
			<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
				<polyline points="9 18 15 12 9 6"/>
			</svg>
		</button>
		<button type="button" class="toolbar-btn column-only danger" data-action="remove-column" title="<?php esc_attr_e( 'Remove Column', 'checkmate-pdf-invoices' ); ?>">
			<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
				<rect x="3" y="3" width="18" height="18" rx="2"/>
				<line x1="8" y1="12" x2="16" y2="12"/>
			</svg>
		</button>
		<span class="toolbar-divider column-only"></span>
		<button type="button" class="toolbar-btn" data-action="duplicate" title="<?php esc_attr_e( 'Duplicate', 'checkmate-pdf-invoices' ); ?>">
			<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
				<rect x="9" y="9" width="13" height="13" rx="2" ry="2"/>
				<path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/>
			</svg>
		</button>
		<button type="button" class="toolbar-btn danger" data-action="delete" title="<?php esc_attr_e( 'Delete', 'checkmate-pdf-invoices' ); ?>">
			<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
				<polyline points="3 6 5 6 21 6"/>
				<path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
			</svg>
		</button>
	</div>

	<!-- Drop Indicator -->
	<div class="drop-indicator" id="drop-indicator"></div>
</div>

<!-- Hidden data for JavaScript -->
<script type="application/json" id="editor-data">
<?php
echo wp_json_encode( [
	'template'       => $template->to_array(),
	'defaultPageSettings' => Template::get_default_page_settings(),
	'blocks'         => $registry->get_blocks(),
	'categories'     => $registry->get_categories(),
	'documentTypes'  => $document_types,
	'siteName'       => get_bloginfo( 'name' ),
	'siteLogoUrl'    => ( function() {
		$logo_id = get_theme_mod( 'custom_logo' );
		return $logo_id ? wp_get_attachment_image_url( $logo_id, 'full' ) : '';
	} )(),
	'dynamicTags'    => [
		[ 'group' => __( 'Shop', 'checkmate-pdf-invoices' ), 'label' => __( 'Shop name', 'checkmate-pdf-invoices' ), 'token' => '{shop_name}' ],
		[ 'group' => __( 'Shop', 'checkmate-pdf-invoices' ), 'label' => __( 'Shop email', 'checkmate-pdf-invoices' ), 'token' => '{shop_email}' ],
		[ 'group' => __( 'Shop', 'checkmate-pdf-invoices' ), 'label' => __( 'Shop phone', 'checkmate-pdf-invoices' ), 'token' => '{shop_phone}' ],
		[ 'group' => __( 'Shop', 'checkmate-pdf-invoices' ), 'label' => __( 'Site URL', 'checkmate-pdf-invoices' ), 'token' => '{site_url}' ],
		[ 'group' => __( 'Date', 'checkmate-pdf-invoices' ), 'label' => __( 'Current date', 'checkmate-pdf-invoices' ), 'token' => '{current_date}' ],
		[ 'group' => __( 'Date', 'checkmate-pdf-invoices' ), 'label' => __( 'Current year', 'checkmate-pdf-invoices' ), 'token' => '{current_year}' ],
		[ 'group' => __( 'Order', 'checkmate-pdf-invoices' ), 'label' => __( 'Order number', 'checkmate-pdf-invoices' ), 'token' => '{order_number}' ],
		[ 'group' => __( 'Order', 'checkmate-pdf-invoices' ), 'label' => __( 'Order date', 'checkmate-pdf-invoices' ), 'token' => '{order_date}' ],
		[ 'group' => __( 'Order', 'checkmate-pdf-invoices' ), 'label' => __( 'Order total', 'checkmate-pdf-invoices' ), 'token' => '{order_total}' ],
		[ 'group' => __( 'Order', 'checkmate-pdf-invoices' ), 'label' => __( 'Payment method', 'checkmate-pdf-invoices' ), 'token' => '{payment_method}' ],
		[ 'group' => __( 'Order', 'checkmate-pdf-invoices' ), 'label' => __( 'Shipping method', 'checkmate-pdf-invoices' ), 'token' => '{shipping_method}' ],
		[ 'group' => __( 'Customer', 'checkmate-pdf-invoices' ), 'label' => __( 'Customer name', 'checkmate-pdf-invoices' ), 'token' => '{customer_name}' ],
		[ 'group' => __( 'Customer', 'checkmate-pdf-invoices' ), 'label' => __( 'Customer address', 'checkmate-pdf-invoices' ), 'token' => '{customer_address}' ],
		[ 'group' => __( 'Customer', 'checkmate-pdf-invoices' ), 'label' => __( 'Customer email', 'checkmate-pdf-invoices' ), 'token' => '{customer_email}' ],
		[ 'group' => __( 'Customer', 'checkmate-pdf-invoices' ), 'label' => __( 'Customer phone', 'checkmate-pdf-invoices' ), 'token' => '{customer_phone}' ],
		[ 'group' => __( 'Billing', 'checkmate-pdf-invoices' ), 'label' => __( 'Billing name', 'checkmate-pdf-invoices' ), 'token' => '{billing_name}' ],
		[ 'group' => __( 'Billing', 'checkmate-pdf-invoices' ), 'label' => __( 'Billing address', 'checkmate-pdf-invoices' ), 'token' => '{billing_address}' ],
		[ 'group' => __( 'Billing', 'checkmate-pdf-invoices' ), 'label' => __( 'Billing email', 'checkmate-pdf-invoices' ), 'token' => '{billing_email}' ],
		[ 'group' => __( 'Billing', 'checkmate-pdf-invoices' ), 'label' => __( 'Billing phone', 'checkmate-pdf-invoices' ), 'token' => '{billing_phone}' ],
		[ 'group' => __( 'Shipping', 'checkmate-pdf-invoices' ), 'label' => __( 'Shipping name', 'checkmate-pdf-invoices' ), 'token' => '{shipping_name}' ],
		[ 'group' => __( 'Shipping', 'checkmate-pdf-invoices' ), 'label' => __( 'Shipping address', 'checkmate-pdf-invoices' ), 'token' => '{shipping_address}' ],
		[ 'group' => __( 'Shipping', 'checkmate-pdf-invoices' ), 'label' => __( 'Shipping email', 'checkmate-pdf-invoices' ), 'token' => '{shipping_email}' ],
		[ 'group' => __( 'Shipping', 'checkmate-pdf-invoices' ), 'label' => __( 'Shipping phone', 'checkmate-pdf-invoices' ), 'token' => '{shipping_phone}' ],
	],
	'paperSizes'     => $paper_sizes,
	'fonts'          => $fonts,
	'nonce'          => wp_create_nonce( 'checkmate_admin_nonce' ),
	'ajaxUrl'        => admin_url( 'admin-ajax.php' ),
	'previewUrl'     => admin_url( 'admin-ajax.php?action=checkmate_preview_pdf' ),
	'i18n'           => [
		'confirmDelete'      => __( 'Are you sure you want to delete this block?', 'checkmate-pdf-invoices' ),
		'saved'              => __( 'Template saved successfully!', 'checkmate-pdf-invoices' ),
		'saveError'          => __( 'Error saving template. Please try again.', 'checkmate-pdf-invoices' ),
		'unsavedChanges'     => __( 'You have unsaved changes. Are you sure you want to leave?', 'checkmate-pdf-invoices' ),
		'dropHere'           => __( 'Drop block here', 'checkmate-pdf-invoices' ),
		'addBlockInside'     => __( 'Add block inside', 'checkmate-pdf-invoices' ),
	],
] );
?>
</script>

<?php
/**
 * Helper function to get block icon SVG (inline version for editor footer).
 *
 * @param string $icon Icon name.
 * @return string SVG markup.
 */
if ( ! function_exists( 'checkmate_get_block_icon_inline' ) ) {
function checkmate_get_block_icon_inline( string $icon ): string {
	$icons = [
		'columns'        => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><line x1="9" y1="3" x2="9" y2="21"/><line x1="15" y1="3" x2="15" y2="21"/></svg>',
		'square'         => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/></svg>',
		'move-vertical'  => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="8 18 12 22 16 18"/><polyline points="8 6 12 2 16 6"/><line x1="12" y1="2" x2="12" y2="22"/></svg>',
		'minus'          => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="5" y1="12" x2="19" y2="12"/></svg>',
		'image'          => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>',
		'type'           => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="4 7 4 4 20 4 20 7"/><line x1="9" y1="20" x2="15" y2="20"/><line x1="12" y1="4" x2="12" y2="20"/></svg>',
		'heading'        => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 4v16M18 4v16M6 12h12"/></svg>',
		'file-text'      => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>',
		'hash'           => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="4" y1="9" x2="20" y2="9"/><line x1="4" y1="15" x2="20" y2="15"/><line x1="10" y1="3" x2="8" y2="21"/><line x1="16" y1="3" x2="14" y2="21"/></svg>',
		'calendar'       => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>',
		'building'       => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>',
		'map-pin'        => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>',
		'truck'          => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="1" y="3" width="15" height="13"/><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg>',
		'shopping-bag'   => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>',
		'credit-card'    => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>',
		'package'        => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="16.5" y1="9.4" x2="7.5" y2="4.21"/><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><polyline points="3.27 6.96 12 12.01 20.73 6.96"/><line x1="12" y1="22.08" x2="12" y2="12"/></svg>',
		'message-square' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>',
		'table'          => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 3H5a2 2 0 0 0-2 2v4m6-6h10a2 2 0 0 1 2 2v4M9 3v18m0 0h10a2 2 0 0 0 2-2V9M9 21H5a2 2 0 0 1-2-2V9m0 0h18"/></svg>',
		'calculator'     => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="4" y="2" width="16" height="20" rx="2"/><line x1="8" y1="6" x2="16" y2="6"/><line x1="8" y1="10" x2="8" y2="10.01"/><line x1="12" y1="10" x2="12" y2="10.01"/><line x1="16" y1="10" x2="16" y2="10.01"/><line x1="8" y1="14" x2="8" y2="14.01"/><line x1="12" y1="14" x2="12" y2="14.01"/><line x1="16" y1="14" x2="16" y2="14.01"/><line x1="8" y1="18" x2="8" y2="18.01"/><line x1="12" y1="18" x2="12" y2="18.01"/><line x1="16" y1="18" x2="16" y2="18.01"/></svg>',
		'align-bottom'   => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="4" y1="22" x2="20" y2="22"/><rect x="6" y="2" width="4" height="16"/><rect x="14" y="6" width="4" height="12"/></svg>',
	];

	return $icons[ $icon ] ?? $icons['square'];
}
} // end function_exists check
?>
