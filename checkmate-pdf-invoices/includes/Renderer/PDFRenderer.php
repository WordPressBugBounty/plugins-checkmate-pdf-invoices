<?php
/**
 * PDF Renderer - Converts template blocks to Dompdf-compatible HTML
 *
 * CRITICAL: Uses table-based layouts ONLY (no flexbox/grid for Dompdf)
 *
 * @package Checkmate\PdfInvoices
 */

namespace Checkmate\PdfInvoices\Renderer;

use Checkmate\PdfInvoices\Editor\Template;
use Checkmate\Vendor\Dompdf\Dompdf;
use Checkmate\Vendor\Dompdf\Options;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * PDF Renderer class
 */
class PDFRenderer {

	/**
	 * Template being rendered
	 *
	 * @var Template
	 */
	private Template $template;

	/**
	 * Order data for dynamic content
	 *
	 * @var array|null
	 */
	private ?array $order_data = null;

	/**
	 * Shop data
	 *
	 * @var array
	 */
	private array $shop_data = [];

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->shop_data = $this->get_shop_data();
	}

	/**
	 * Render template to PDF
	 *
	 * @param Template   $template   Template object.
	 * @param array|null $order_data Optional order data for dynamic content.
	 * @return string PDF content as string.
	 */
	public function render( Template $template, ?array $order_data = null ): string {
		$this->template   = $template;
		$this->order_data = $order_data;

		// Generate HTML
		$html = $this->generate_html();

		// Initialize Dompdf
		$options = new Options();
		$options->set( 'isRemoteEnabled', true );
		$options->set( 'isHtml5ParserEnabled', true );
		$options->set( 'isFontSubsettingEnabled', true );
		$options->set( 'defaultFont', $this->get_font_family() );

		$dompdf = new Dompdf( $options );
		$dompdf->loadHtml( $html );

		// Set paper size and orientation
		$dimensions = $template->get_paper_dimensions();
		$dompdf->setPaper(
			[ 0, 0, $dimensions['width'] * 2.83465, $dimensions['height'] * 2.83465 ], // mm to points
			$template->get_page_settings()['orientation'] ?? 'portrait'
		);

		// Render PDF
		$dompdf->render();

		return $dompdf->output();
	}

	/**
	 * Render template to HTML (for preview)
	 *
	 * @param Template   $template   Template object.
	 * @param array|null $order_data Optional order data.
	 * @return string HTML content.
	 */
	public function render_html( Template $template, ?array $order_data = null ): string {
		$this->template   = $template;
		$this->order_data = $order_data;

		return $this->generate_preview_html();
	}

	/**
	 * Generate HTML document for PDF (uses @page margins)
	 *
	 * @return string HTML content.
	 */
	private function generate_html(): string {
		$ps = $this->template->get_page_settings();

		$html = '<!DOCTYPE html>';
		$html .= '<html><head>';
		$html .= '<meta charset="UTF-8">';
		$html .= '<title>' . esc_html( $this->template->get_name() ) . '</title>';
		$html .= $this->generate_css();
		$html .= '</head><body>';
		$html .= '<div class="document">';

		// Render all blocks
		foreach ( $this->template->get_blocks() as $block ) {
			$html .= $this->render_block( $block );
		}

		$html .= '</div></body></html>';

		return $html;
	}

	/**
	 * Generate HTML document for browser preview (uses padding instead of @page)
	 *
	 * @return string HTML content.
	 */
	private function generate_preview_html(): string {
		$ps = $this->template->get_page_settings();
		$paper = $this->template->get_paper_dimensions();
		$font_family = $this->get_font_family();

		$html = '<!DOCTYPE html>';
		$html .= '<html><head>';
		$html .= '<meta charset="UTF-8">';
		$html .= '<title>' . esc_html( $this->template->get_name() ) . '</title>';
		// This <style> tag is part of the HTML document fed to Dompdf for PDF generation — it is NOT output to the browser.
		// phpcs:ignore WordPress.WP.EnqueuedResources.NonEnqueuedStylesheet
		$html .= '<style>';

		// Base styles for preview
		$html .= '* { box-sizing: border-box; }';
		$html .= 'html, body { margin: 0; padding: 0; background: #f5f5f5; }';
		
		// Page container (simulates the PDF page)
		$html .= '.page {';
		$html .= 'width: ' . $paper['width'] . 'mm;';
		$html .= 'min-height: ' . $paper['height'] . 'mm;';
		$html .= 'margin: 0 auto;';
		$html .= 'background: ' . $ps['backgroundColor'] . ';';
		$html .= 'box-shadow: 0 2px 10px rgba(0,0,0,0.1);';
		$html .= 'padding: ' . $ps['marginTop'] . 'mm ' . $ps['marginRight'] . 'mm ' . $ps['marginBottom'] . 'mm ' . $ps['marginLeft'] . 'mm;';
		$html .= '}';

		// Document styles
		$html .= '.document {';
		$html .= 'font-family: ' . $font_family . ', sans-serif;';
		$html .= 'font-size: ' . $ps['baseFontSize'] . 'pt;';
		$html .= 'color: ' . $ps['textColor'] . ';';
		$html .= 'line-height: 1.4;';
		$html .= '}';

		// Block base styles
		$html .= '.block { margin-bottom: 5mm; }';
		$html .= '.block:last-child { margin-bottom: 0; }';

		// Table reset
		$html .= 'table { border-collapse: collapse; width: 100%; }';
		$html .= 'td, th { vertical-align: top; }';

		// Typography
		$html .= 'h1, h2, h3, h4 { margin: 0 0 3mm 0; }';
		$html .= 'h1 { font-size: 18pt; }';
		$html .= 'h2 { font-size: 14pt; }';
		$html .= 'h3 { font-size: 12pt; }';
		$html .= 'h4 { font-size: 10pt; }';
		$html .= 'p { margin: 0 0 2mm 0; }';
		$html .= 'p:last-child { margin-bottom: 0; }';

		// Divider
		$html .= '.divider { border: none; margin: 3mm 0; }';

		// Items table
		$html .= '.items-table { width: 100%; }';
		$html .= '.items-table th { text-align: left; padding: 2mm 3mm; }';
		$html .= '.items-table td { padding: 2mm 3mm; }';

		// Totals table
		$html .= '.totals-table { width: auto; }';
		$html .= '.totals-table td { padding: 1mm 3mm; text-align: right; }';
		$html .= '.totals-table .total-row { font-weight: bold; border-top: 0.5mm solid #000; }';

		$html .= '</style>';
		$html .= '</head><body>';
		$html .= '<div class="page">';
		$html .= '<div class="document">';

		// Render all blocks
		foreach ( $this->template->get_blocks() as $block ) {
			$html .= $this->render_block( $block );
		}

		$html .= '</div></div></body></html>';

		return $html;
	}

	/**
	 * Generate CSS for PDF
	 *
	 * @return string CSS in style tag.
	 */
	private function generate_css(): string {
		$ps = $this->template->get_page_settings();
		$font_family = $this->get_font_family();
		$content = $this->template->get_content_dimensions();

		// This <style> tag is part of the HTML document fed to Dompdf for PDF generation — it is NOT output to the browser.
		// phpcs:ignore WordPress.WP.EnqueuedResources.NonEnqueuedStylesheet
		$css = '<style>';

		// Page setup
		$css .= '@page {';
		$css .= 'margin: ' . $ps['marginTop'] . 'mm ' . $ps['marginRight'] . 'mm ' . $ps['marginBottom'] . 'mm ' . $ps['marginLeft'] . 'mm;';
		$css .= '}';

		// Base styles
		$css .= 'body {';
		$css .= 'font-family: ' . $font_family . ', sans-serif;';
		$css .= 'font-size: ' . $ps['baseFontSize'] . 'pt;';
		$css .= 'color: ' . $ps['textColor'] . ';';
		$css .= 'background-color: ' . $ps['backgroundColor'] . ';';
		$css .= 'margin: 0;';
		$css .= 'padding: 0;';
		$css .= 'line-height: 1.4;';
		$css .= '}';

		// Document container
		$css .= '.document {';
		$css .= 'width: 100%;';
		$css .= '}';

		// Block base styles
		$css .= '.block { margin-bottom: 5mm; }';
		$css .= '.block:last-child { margin-bottom: 0; }';

		// Table reset
		$css .= 'table { border-collapse: collapse; width: 100%; }';
		$css .= 'td, th { vertical-align: top; }';

		// Typography
		$css .= 'h1, h2, h3, h4 { margin: 0 0 3mm 0; }';
		$css .= 'h1 { font-size: 18pt; }';
		$css .= 'h2 { font-size: 14pt; }';
		$css .= 'h3 { font-size: 12pt; }';
		$css .= 'h4 { font-size: 10pt; }';
		$css .= 'p { margin: 0 0 2mm 0; }';
		$css .= 'p:last-child { margin-bottom: 0; }';

		// Divider
		$css .= '.divider { border: none; margin: 3mm 0; }';

		// Items table
		$css .= '.items-table { width: 100%; }';
		$css .= '.items-table th { text-align: left; padding: 2mm 3mm; }';
		$css .= '.items-table td { padding: 2mm 3mm; }';

		// Totals table
		$css .= '.totals-table { width: auto; }';
		$css .= '.totals-table td { padding: 1mm 3mm; text-align: right; }';
		$css .= '.totals-table .total-row { font-weight: bold; border-top: 0.5mm solid #000; }';

		$css .= '</style>';

		return $css;
	}

	/**
	 * Render a single block
	 *
	 * @param array $block Block data.
	 * @return string HTML for block.
	 */
	private function render_block( array $block ): string {
		$type = $block['type'] ?? '';
		$attrs = $block['attributes'] ?? [];

		// Get base style string for the block
		$base_style = $this->get_base_block_styles( $attrs );

		switch ( $type ) {
			case 'row':
				return $this->render_row( $block );

			case 'spacer':
				return $this->render_spacer( $attrs, $base_style );

			case 'divider':
				return $this->render_divider( $attrs, $base_style );

			case 'logo':
				return $this->render_logo( $attrs, $base_style );

			case 'text':
				return $this->render_text( $attrs, $base_style );

			case 'heading':
				return $this->render_heading( $attrs, $base_style );

			case 'image':
				return $this->render_image( $attrs, $base_style );

			case 'document-title':
				return $this->render_document_title( $attrs, $base_style );

			case 'document-number':
				return $this->render_document_number( $attrs, $base_style );

			case 'document-date':
				return $this->render_document_date( $attrs, $base_style );

			case 'shop-info':
				return $this->render_shop_info( $attrs, $base_style );

			case 'billing-address':
				return $this->render_address( 'billing', $attrs, $base_style );

			case 'shipping-address':
				return $this->render_address( 'shipping', $attrs, $base_style );

			case 'order-number':
				return $this->render_order_number( $attrs, $base_style );

			case 'order-date':
				return $this->render_order_date( $attrs, $base_style );

			case 'payment-method':
				return $this->render_payment_method( $attrs, $base_style );

			case 'shipping-method':
				return $this->render_shipping_method( $attrs, $base_style );

			case 'customer-note':
				return $this->render_customer_note( $attrs, $base_style );

			case 'items-table':
				return $this->render_items_table( $attrs, $base_style );

			case 'totals-table':
				return $this->render_totals_table( $attrs, $base_style );

			case 'notes':
				return $this->render_notes( $attrs, $base_style );

			case 'footer':
				return $this->render_footer( $attrs, $base_style );

			default:
				return '<!-- Unknown block: ' . esc_html( $type ) . ' -->';
		}
	}

	/**
	 * Get base block style string from attributes
	 *
	 * @param array $attrs Block attributes.
	 * @return string CSS style string.
	 */
	private function get_base_block_styles( array $attrs ): string {
		$styles = [];

		// Respect explicit 0 values and apply defaults when attributes are missing.
		if ( array_key_exists( 'paddingTop', $attrs ) ) {
			$styles[] = 'padding-top: ' . intval( $attrs['paddingTop'] ) . 'px';
		}
		if ( array_key_exists( 'paddingRight', $attrs ) ) {
			$styles[] = 'padding-right: ' . intval( $attrs['paddingRight'] ) . 'px';
		}
		if ( array_key_exists( 'paddingBottom', $attrs ) ) {
			$styles[] = 'padding-bottom: ' . intval( $attrs['paddingBottom'] ) . 'px';
		}
		if ( array_key_exists( 'paddingLeft', $attrs ) ) {
			$styles[] = 'padding-left: ' . intval( $attrs['paddingLeft'] ) . 'px';
		}
		if ( array_key_exists( 'marginTop', $attrs ) ) {
			$styles[] = 'margin-top: ' . intval( $attrs['marginTop'] ) . 'px';
		}
		if ( array_key_exists( 'marginRight', $attrs ) ) {
			$styles[] = 'margin-right: ' . intval( $attrs['marginRight'] ) . 'px';
		}
		// Default marginBottom is 5px (matches editor defaults).
		$margin_bottom = array_key_exists( 'marginBottom', $attrs ) ? intval( $attrs['marginBottom'] ) : 5;
		$styles[] = 'margin-bottom: ' . $margin_bottom . 'px';
		if ( array_key_exists( 'marginLeft', $attrs ) ) {
			$styles[] = 'margin-left: ' . intval( $attrs['marginLeft'] ) . 'px';
		}
		if ( ! empty( $attrs['backgroundColor'] ) ) {
			$styles[] = 'background-color: ' . esc_attr( $attrs['backgroundColor'] );
		}
		if ( ! empty( $attrs['backgroundImage'] ) ) {
			$styles[] = 'background-image: url(\'' . esc_url( $attrs['backgroundImage'] ) . '\')';
			$styles[] = 'background-repeat: ' . esc_attr( $attrs['backgroundRepeat'] ?? 'no-repeat' );
			$styles[] = 'background-position: ' . esc_attr( $attrs['backgroundPosition'] ?? 'top left' );
			$styles[] = 'background-size: ' . esc_attr( $attrs['backgroundSize'] ?? 'auto' );
		}

		// Typography (applies to wrapper)
		$text_color = '';
		if ( ! empty( $attrs['textColor'] ) ) {
			$text_color = $attrs['textColor'];
		} elseif ( ! empty( $attrs['color'] ) ) {
			// Back-compat for older templates that stored text color as `color`.
			$text_color = $attrs['color'];
		}
		if ( ! empty( $text_color ) ) {
			$styles[] = 'color: ' . esc_attr( $text_color );
		}

		$font_weight = $attrs['fontWeight'] ?? '';
		if ( in_array( $font_weight, [ 'normal', 'bold' ], true ) && 'normal' !== $font_weight ) {
			$styles[] = 'font-weight: ' . esc_attr( $font_weight );
		}
		$font_style = $attrs['fontStyle'] ?? '';
		if ( in_array( $font_style, [ 'normal', 'italic' ], true ) && 'normal' !== $font_style ) {
			$styles[] = 'font-style: ' . esc_attr( $font_style );
		}
		$text_decoration = $attrs['textDecoration'] ?? '';
		if ( in_array( $text_decoration, [ 'none', 'underline', 'overline', 'line-through' ], true ) && 'none' !== $text_decoration ) {
			$styles[] = 'text-decoration: ' . esc_attr( $text_decoration );
		}
		$text_transform = $attrs['textTransform'] ?? '';
		if ( in_array( $text_transform, [ 'none', 'uppercase', 'lowercase', 'capitalize' ], true ) && 'none' !== $text_transform ) {
			$styles[] = 'text-transform: ' . esc_attr( $text_transform );
		}

		return implode( '; ', $styles );
	}

	/**
	 * Render row (table layout)
	 */
	private function render_row( array $block ): string {
		$attrs = $block['attributes'] ?? [];
		$children = $block['children'] ?? [];
		$gap = $attrs['gap'] ?? 10;
		$valign = $attrs['verticalAlign'] ?? 'top';

		// Get base styles for row
		$base_style = $this->get_base_block_styles( $attrs );
		$style_attr = ! empty( $base_style ) ? ' style="' . esc_attr( $base_style ) . '"' : '';

		$html = '<div class="block"' . $style_attr . '><table style="width: 100%; table-layout: fixed;"><tr>';

		foreach ( $children as $index => $column ) {
			$col_attrs = $column['attributes'] ?? [];
			$width = $col_attrs['width'] ?? ( 100 / count( $children ) );
			$padding = $index > 0 ? 'padding-left: ' . ( $gap / 2 ) . 'mm;' : '';
			$padding .= $index < count( $children ) - 1 ? 'padding-right: ' . ( $gap / 2 ) . 'mm;' : '';

			$html .= '<td style="width: ' . $width . '%; vertical-align: ' . $valign . '; ' . $padding . '">';

			// Render column children
			$col_children = $column['children'] ?? [];
			foreach ( $col_children as $child_block ) {
				$html .= $this->render_block( $child_block );
			}

			$html .= '</td>';
		}

		$html .= '</tr></table></div>';

		return $html;
	}

	/**
	 * Render spacer
	 */
	private function render_spacer( array $attrs, string $base_style = '' ): string {
		$height = $attrs['height'] ?? 20;
		$style = 'height: ' . $height . 'px;';
		if ( ! empty( $base_style ) ) {
			$style .= ' ' . $base_style;
		}
		return '<div style="' . esc_attr( $style ) . '"></div>';
	}

	/**
	 * Render divider
	 */
	private function render_divider( array $attrs, string $base_style = '' ): string {
		$thickness = $attrs['thickness'] ?? 1;
		$line_style = $attrs['style'] ?? 'solid';
		$color = $attrs['color'] ?? '#cccccc';

		$style = 'border-top: ' . $thickness . 'px ' . $line_style . ' ' . $color . ';';
		if ( ! empty( $base_style ) ) {
			$style .= ' ' . $base_style;
		}
		return '<hr class="divider" style="' . esc_attr( $style ) . '">';
	}

	/**
	 * Render logo
	 */
	private function render_logo( array $attrs, string $base_style = '' ): string {
		$source = $attrs['source'] ?? 'site';
		if ( $source === 'custom' ) {
			$logo_url = trim( (string) ( $attrs['customUrl'] ?? '' ) );
			if ( empty( $logo_url ) ) {
				// Back-compat: older versions stored a custom logo in a global option.
				$logo_url = $this->get_logo_url( 'custom' );
			}
		} else {
			$logo_url = $this->get_logo_url( 'site' );
		}
		$max_height = $attrs['maxHeight'] ?? 80;
		$align = $this->normalize_text_align( (string) ( $attrs['align'] ?? 'left' ) );

		$style = 'text-align: ' . $align . ';';
		if ( ! empty( $base_style ) ) {
			$style .= ' ' . $base_style;
		}

		if ( empty( $logo_url ) ) {
			return '<div class="block" style="' . esc_attr( $style ) . '">' . esc_html( get_bloginfo( 'name' ) ) . '</div>';
		}
		return '<div class="block" style="' . esc_attr( $style ) . '">' .
			'<img src="' . esc_url( $logo_url ) . '" style="max-height: ' . $max_height . 'px; height: auto;">' .
			'</div>';
	}

	/**
	 * Render text block
	 */
	private function render_text( array $attrs, string $base_style = '' ): string {
		$content = $attrs['content'] ?? '';
		$font_size = $attrs['fontSize'] ?? 9;
		$align = $this->normalize_text_align( (string) ( $attrs['align'] ?? 'left' ) );

		// Process content for dynamic tags
		$content = $this->process_dynamic_content( $content );

		$style = 'font-size: ' . $font_size . 'pt; text-align: ' . $align . ';';
		if ( ! empty( $base_style ) ) {
			$style .= ' ' . $base_style;
		}

		return '<div class="block" style="' . esc_attr( $style ) . '">' .
			wp_kses_post( $content ) .
			'</div>';
	}

	/**
	 * Render heading
	 */
	private function render_heading( array $attrs, string $base_style = '' ): string {
		$content = $attrs['content'] ?? 'Heading';
		$level = $attrs['level'] ?? 'h2';
		$align = $this->normalize_text_align( (string) ( $attrs['align'] ?? 'left' ) );

		// Process content for dynamic tags
		$content = $this->process_dynamic_content( $content );

		$tag = in_array( $level, [ 'h1', 'h2', 'h3', 'h4' ], true ) ? $level : 'h2';

		$style = 'text-align: ' . $align . ';';
		if ( ! empty( $base_style ) ) {
			$style .= ' ' . $base_style;
		}
		return '<' . $tag . ' class="block" style="' . esc_attr( $style ) . '">' .
			esc_html( $content ) .
			'</' . $tag . '>';
	}

	/**
	 * Render image
	 */
	private function render_image( array $attrs, string $base_style = '' ): string {
		$url = $attrs['url'] ?? '';
		$alt = $attrs['alt'] ?? '';
		$width = $attrs['width'] ?? 100;
		$max_height = $attrs['maxHeight'] ?? 200;
		$align = $this->normalize_text_align( (string) ( $attrs['align'] ?? 'left' ) );

		$style = 'text-align: ' . $align . ';';
		if ( ! empty( $base_style ) ) {
			$style .= ' ' . $base_style;
		}

		if ( empty( $url ) ) {
			return '<div class="block" style="' . esc_attr( $style . ' padding: 20px; border: 1px dashed #ccc; color: #999;' ) . '">Image placeholder</div>';
		}

		return '<div class="block" style="' . esc_attr( $style ) . '">' .
			'<img src="' . esc_url( $url ) . '" alt="' . esc_attr( $alt ) . '" style="max-width: ' . $width . '%; max-height: ' . $max_height . 'px; height: auto;" />' .
			'</div>';
	}

	/**
	 * Render document title
	 */
	private function render_document_title( array $attrs, string $base_style = '' ): string {
		$font_size = $attrs['fontSize'] ?? 16;
		$align = $this->normalize_text_align( (string) ( $attrs['align'] ?? 'left' ) );

		$title = $this->get_document_title();

		$style = 'font-size: ' . $font_size . 'pt; text-align: ' . $align . ';';
		if ( ! empty( $base_style ) ) {
			$style .= ' ' . $base_style;
		}
		return '<div class="block" style="' . esc_attr( $style ) . '">' .
			esc_html( $title ) .
			'</div>';
	}

	/**
	 * Render document number
	 */
	private function render_document_number( array $attrs, string $base_style = '' ): string {
		$number = $this->get_document_number();
		return $this->render_label_value( $attrs, $number, 'Invoice #:', $base_style );
	}

	/**
	 * Render document date
	 */
	private function render_document_date( array $attrs, string $base_style = '' ): string {
		$format = $attrs['format'] ?? 'F j, Y';
		$date = $this->get_document_date( $format );
		return $this->render_label_value( $attrs, $date, 'Date:', $base_style );
	}

	/**
	 * Render shop info
	 */
	private function render_shop_info( array $attrs, string $base_style = '' ): string {
		$font_size = $attrs['fontSize'] ?? 9;
		$align = $this->normalize_text_align( (string) ( $attrs['align'] ?? 'left' ) );

		$style = 'font-size: ' . $font_size . 'pt; text-align: ' . $align . ';';
		if ( ! empty( $base_style ) ) {
			$style .= ' ' . $base_style;
		}
		$html = '<div class="block" style="' . esc_attr( $style ) . '">';

		if ( ! empty( $attrs['showName'] ) ) {
			$html .= '<strong>' . esc_html( $this->shop_data['name'] ) . '</strong><br>';
		}
		if ( ! empty( $attrs['showAddress'] ) ) {
			$html .= nl2br( esc_html( $this->shop_data['address'] ) ) . '<br>';
		}
		if ( ! empty( $attrs['showPhone'] ) && ! empty( $this->shop_data['phone'] ) ) {
			$html .= 'Phone: ' . esc_html( $this->shop_data['phone'] ) . '<br>';
		}
		if ( ! empty( $attrs['showEmail'] ) && ! empty( $this->shop_data['email'] ) ) {
			$html .= 'Email: ' . esc_html( $this->shop_data['email'] );
		}

		$html .= '</div>';

		return $html;
	}

	/**
	 * Render address block
	 */
	private function render_address( string $type, array $attrs, string $base_style = '' ): string {
		$font_size = $attrs['fontSize'] ?? 9;
		$address = $this->get_address( $type );
		$text_align = $this->normalize_text_align( (string) ( $attrs['align'] ?? 'left' ) );

		$style = 'font-size: ' . $font_size . 'pt; text-align: ' . $text_align . ';';
		if ( ! empty( $base_style ) ) {
			$style .= ' ' . $base_style;
		}
		$html = '<div class="block" style="' . esc_attr( $style ) . '">';

		if ( ! empty( $attrs['showTitle'] ) ) {
			$title = $attrs['title'] ?? ( $type === 'billing' ? 'Bill To:' : 'Ship To:' );
			$title = $this->process_dynamic_content( $title );
			$title_style = $this->build_title_style_css( $attrs );
			$html .= '<span' . ( ! empty( $title_style ) ? ' style="' . esc_attr( $title_style ) . '"' : '' ) . '>' . esc_html( $title ) . '</span><br>';
		}

		$html .= esc_html( $address['name'] ) . '<br>';
		$html .= nl2br( esc_html( $address['address'] ) ) . '<br>';

		if ( ! empty( $attrs['showEmail'] ) && ! empty( $address['email'] ) ) {
			$html .= esc_html( $address['email'] ) . '<br>';
		}
		if ( ! empty( $attrs['showPhone'] ) && ! empty( $address['phone'] ) ) {
			$html .= esc_html( $address['phone'] );
		}

		$html .= '</div>';

		return $html;
	}

	/**
	 * Render order number
	 */
	private function render_order_number( array $attrs, string $base_style = '' ): string {
		$number = $this->order_data['order_number'] ?? '#WC-12345';
		return $this->render_label_value( $attrs, $number, 'Order #:', $base_style );
	}

	/**
	 * Render order date
	 */
	private function render_order_date( array $attrs, string $base_style = '' ): string {
		$format = $attrs['format'] ?? 'F j, Y';
		$date = $this->order_data['order_date'] ?? '';
		if ( $date !== '' ) {
			$ts = strtotime( (string) $date );
			if ( $ts !== false ) {
				$date = gmdate( (string) $format, $ts );
			}
		} else {
			$date = gmdate( (string) $format );
		}
		return $this->render_label_value( $attrs, $date, 'Order Date:', $base_style );
	}

	/**
	 * Render payment method
	 */
	private function render_payment_method( array $attrs, string $base_style = '' ): string {
		$method = $this->order_data['payment_method'] ?? 'Credit Card (Stripe)';
		return $this->render_label_value( $attrs, $method, 'Payment:', $base_style );
	}

	/**
	 * Render shipping method
	 */
	private function render_shipping_method( array $attrs, string $base_style = '' ): string {
		$method = $this->order_data['shipping_method'] ?? 'Flat Rate Shipping';
		return $this->render_label_value( $attrs, $method, 'Shipping:', $base_style );
	}

	/**
	 * Render customer note
	 */
	private function render_customer_note( array $attrs, string $base_style = '' ): string {
		$note = $this->order_data['customer_note'] ?? '';
		$font_size = $attrs['fontSize'] ?? 9;
		$text_align = $this->normalize_text_align( (string) ( $attrs['align'] ?? 'left' ) );

		if ( empty( $note ) && ! empty( $attrs['hideIfEmpty'] ) ) {
			return '';
		}

		$style = 'font-size: ' . $font_size . 'pt; text-align: ' . $text_align . ';';
		if ( ! empty( $base_style ) ) {
			$style .= ' ' . $base_style;
		}
		$html = '<div class="block" style="' . esc_attr( $style ) . '">';

		if ( ! empty( $attrs['showTitle'] ) ) {
			$title = $attrs['title'] ?? 'Customer Note:';
			$title = $this->process_dynamic_content( $title );
			$title_style = $this->build_title_style_css( $attrs );
			$html .= '<span' . ( ! empty( $title_style ) ? ' style="' . esc_attr( $title_style ) . '"' : '' ) . '>' . esc_html( $title ) . '</span> ';
		}

		$html .= esc_html( $note ?: 'No customer note' );
		$html .= '</div>';

		return $html;
	}

	/**
	 * Render items table
	 */
	private function render_items_table( array $attrs, string $base_style = '' ): string {
		$columns = $attrs['columns'] ?? [ 'product', 'quantity', 'price', 'total' ];
		$header_bg = $attrs['headerBackground'] ?? '#000000';
		$header_color = $attrs['headerColor'] ?? '#ffffff';
		$border_color = $attrs['borderColor'] ?? '#e0e0e0';
		$font_size = $attrs['fontSize'] ?? 9;
		$text_align = $this->normalize_text_align( (string) ( $attrs['textAlign'] ?? 'left' ) );

		$column_labels = [
			'product'  => $attrs['headerProductLabel'] ?? 'Product',
			'sku'      => $attrs['headerSkuLabel'] ?? 'SKU',
			'quantity' => $attrs['headerQuantityLabel'] ?? 'Qty',
			'price'    => $attrs['headerPriceLabel'] ?? 'Price',
			'total'    => $attrs['headerTotalLabel'] ?? 'Total',
			'weight'   => $attrs['headerWeightLabel'] ?? 'Weight',
			'tax'      => $attrs['headerTaxLabel'] ?? 'Tax',
		];

		$items = $this->get_order_items();

		$style = '';
		if ( ! empty( $base_style ) ) {
			$style = $base_style;
		}
		$html = '<div class="block"' . ( ! empty( $style ) ? ' style="' . esc_attr( $style ) . '"' : '' ) . '>';
		$html .= '<table class="items-table" style="font-size: ' . esc_attr( $font_size ) . 'pt;">';

		// Header
		if ( ! empty( $attrs['showHeader'] ) ) {
			$html .= '<thead><tr>';
			foreach ( $columns as $col ) {
				$label = $this->process_dynamic_content( (string) ( $column_labels[ $col ] ?? $col ) );
				$html .= '<th style="background: ' . esc_attr( $header_bg ) . '; color: ' . esc_attr( $header_color ) . '; text-align: ' . esc_attr( $text_align ) . ';">' .
					esc_html( $label ) . '</th>';
			}
			$html .= '</tr></thead>';
		}

		// Body
		$html .= '<tbody>';
		foreach ( $items as $item ) {
			$html .= '<tr>';
			foreach ( $columns as $col ) {
				$value = $item[ $col ] ?? '';

				// Add SKU if enabled
				if ( $col === 'product' && ! empty( $attrs['showSku'] ) && ! empty( $item['sku'] ) ) {
					$value .= '<br><small style="color: #666;">SKU: ' . esc_html( $item['sku'] ) . '</small>';
				}

				$html .= '<td style="border-bottom: 1px solid ' . esc_attr( $border_color ) . '; text-align: ' . esc_attr( $text_align ) . ';">' . $value . '</td>';
			}
			$html .= '</tr>';
		}
		$html .= '</tbody>';

		$html .= '</table></div>';

		return $html;
	}

	/**
	 * Render totals table
	 */
	private function render_totals_table( array $attrs, string $base_style = '' ): string {
		$align = $attrs['align'] ?? 'right';
		$width = $attrs['width'] ?? 40;
		$font_size = $attrs['fontSize'] ?? 9;
		$totals = $this->get_order_totals();
		$text_align = $this->normalize_text_align( (string) ( $attrs['textAlign'] ?? 'right' ) );
		$label_position = (string) ( $attrs['labelPosition'] ?? 'left' );

		$margin_side = $align === 'right' ? 'margin-left' : 'margin-right';

		$style = '';
		if ( ! empty( $base_style ) ) {
			$style = $base_style;
		}
		$html = '<div class="block"' . ( ! empty( $style ) ? ' style="' . esc_attr( $style ) . '"' : '' ) . '>';
		$html .= '<table class="totals-table" style="width: ' . esc_attr( $width ) . '%; ' . esc_attr( $margin_side ) . ': auto; font-size: ' . esc_attr( $font_size ) . 'pt;">';
		$label_style = $this->build_label_style_css( $attrs );

		$mk_cell = function( string $tag, string $content, string $extra_style = '' ) use ( $text_align ) : string {
			$style = trim( 'text-align: ' . $text_align . ';' . ( $extra_style !== '' ? ' ' . $extra_style : '' ) );
			return '<' . $tag . ( $style !== '' ? ' style="' . esc_attr( $style ) . '"' : '' ) . '>' . $content . '</' . $tag . '>';
		};
		$mk_row = function( string $label, string $value_html, string $label_cell_style = '', string $value_cell_style = '' ) use ( $mk_cell, $label_style, $label_position ) : string {
			$label_html = $mk_cell( 'td', esc_html( $label ), trim( $label_style . ' ' . $label_cell_style ) );
			$value_html = $mk_cell( 'td', $value_html, $value_cell_style );
			return $label_position === 'right' ? '<tr>' . $value_html . $label_html . '</tr>' : '<tr>' . $label_html . $value_html . '</tr>';
		};

		if ( ! empty( $attrs['showSubtotal'] ) ) {
			$label = $this->process_dynamic_content( (string) ( $attrs['labelSubtotal'] ?? 'Subtotal:' ) );
			$html .= $mk_row( $label, esc_html( $totals['subtotal'] ) );
		}
		if ( ! empty( $attrs['showShipping'] ) ) {
			$label = $this->process_dynamic_content( (string) ( $attrs['labelShipping'] ?? 'Shipping:' ) );
			$html .= $mk_row( $label, esc_html( $totals['shipping'] ) );
		}
		if ( ! empty( $attrs['showDiscount'] ) && ! empty( $totals['discount'] ) ) {
			$label = $this->process_dynamic_content( (string) ( $attrs['labelDiscount'] ?? 'Discount:' ) );
			$discount_color = sanitize_hex_color( (string) ( $attrs['discountTextColor'] ?? '' ) ) ?: '#c00';
			$html .= $mk_row( $label, esc_html( $totals['discount'] ), '', 'color: ' . $discount_color . ';' );
		}
		if ( ! empty( $attrs['showTax'] ) ) {
			$label = $this->process_dynamic_content( (string) ( $attrs['labelTax'] ?? 'Tax:' ) );
			$html .= $mk_row( $label, esc_html( $totals['tax'] ) );
		}
		if ( ! empty( $attrs['showTotal'] ) ) {
			$row_style = ! empty( $attrs['totalBold'] ) ? 'font-weight: bold;' : '';
			$label = $this->process_dynamic_content( (string) ( $attrs['labelTotal'] ?? 'Total:' ) );
			$label_cell_extra = trim( 'border-top: 0.5mm solid #000; ' . $row_style );
			$value_cell_extra = trim( 'border-top: 0.5mm solid #000; ' . $row_style );
			$html .= $mk_row( $label, esc_html( $totals['total'] ), $label_cell_extra, $value_cell_extra );
		}

		$html .= '</table></div>';

		return $html;
	}

	/**
	 * Render notes block
	 */
	private function render_notes( array $attrs, string $base_style = '' ): string {
		$content = $attrs['content'] ?? 'Thank you for your business!';
		$font_size = $attrs['fontSize'] ?? 8;
		$text_align = $this->normalize_text_align( (string) ( $attrs['align'] ?? 'left' ) );

		$style = 'font-size: ' . $font_size . 'pt; text-align: ' . $text_align . ';';
		if ( ! empty( $base_style ) ) {
			$style .= ' ' . $base_style;
		}
		$html = '<div class="block" style="' . esc_attr( $style ) . '">';

		if ( ! empty( $attrs['showTitle'] ) ) {
			$title = $attrs['title'] ?? 'Notes:';
			$title = $this->process_dynamic_content( $title );
			$title_style = $this->build_title_style_css( $attrs );
			$html .= '<span' . ( ! empty( $title_style ) ? ' style="' . esc_attr( $title_style ) . '"' : '' ) . '>' . esc_html( $title ) . '</span><br>';
		}

		$html .= wp_kses_post( $this->process_dynamic_content( $content ) );
		$html .= '</div>';

		return $html;
	}

	/**
	 * Render footer
	 */
	private function render_footer( array $attrs, string $base_style = '' ): string {
		$content = $attrs['content'] ?? '';
		$font_size = $attrs['fontSize'] ?? 7;
		$align = $this->normalize_text_align( (string) ( $attrs['align'] ?? 'center' ) );

		$style = 'font-size: ' . $font_size . 'pt; text-align: ' . $align . ';';
		if ( ! empty( $base_style ) ) {
			$style .= ' ' . $base_style;
		}
		return '<div class="block" style="' . esc_attr( $style ) . '">' .
			wp_kses_post( $this->process_dynamic_content( $content ) ) .
			'</div>';
	}

	/**
	 * Render label: value format
	 */
	private function render_label_value( array $attrs, string $value, string $default_label, string $base_style = '' ): string {
		$font_size = $attrs['fontSize'] ?? 9;
		$show_label = $attrs['showLabel'] ?? true;
		$label = $attrs['label'] ?? $default_label;
		$label_position = (string) ( $attrs['labelPosition'] ?? 'left' );
		$text_align = $this->normalize_text_align( (string) ( $attrs['align'] ?? 'left' ) );

		$style = 'font-size: ' . $font_size . 'pt; text-align: ' . $text_align . ';';
		if ( ! empty( $base_style ) ) {
			$style .= ' ' . $base_style;
		}
		$html = '<div class="block" style="' . esc_attr( $style ) . '">';

		if ( $show_label ) {
			$label = $this->process_dynamic_content( $label );
			$label_style = $this->build_label_style_css( $attrs );
			$label_html = '<span' . ( ! empty( $label_style ) ? ' style="' . esc_attr( $label_style ) . '"' : '' ) . '>' . esc_html( $label ) . '</span>';
			if ( $label_position === 'right' ) {
				$html .= esc_html( $value ) . ' ' . $label_html;
			} else {
				$html .= $label_html . ' ' . esc_html( $value );
			}
		} else {
			$html .= esc_html( $value );
		}
		$html .= '</div>';

		return $html;
	}

	private function normalize_text_align( string $align ): string {
		$align = strtolower( trim( $align ) );
		if ( $align === 'start' ) {
			return 'left';
		}
		if ( $align === 'end' ) {
			return 'right';
		}
		if ( in_array( $align, [ 'left', 'center', 'right' ], true ) ) {
			return $align;
		}
		return 'left';
	}

	/**
	 * Build inline CSS for label (separate typography)
	 */
	private function build_label_style_css( array $attrs ): string {
		$rules = [];
		$size = (int) ( $attrs['labelFontSize'] ?? 0 );
		if ( $size > 0 ) {
			$rules[] = 'font-size: ' . $size . 'pt;';
		}
		$weight = (string) ( $attrs['labelFontWeight'] ?? 'bold' );
		if ( $weight !== '' ) {
			$rules[] = 'font-weight: ' . $weight . ';';
		}
		$style = (string) ( $attrs['labelFontStyle'] ?? 'normal' );
		if ( $style !== '' ) {
			$rules[] = 'font-style: ' . $style . ';';
		}
		$decoration = (string) ( $attrs['labelTextDecoration'] ?? 'none' );
		if ( $decoration !== '' ) {
			$rules[] = 'text-decoration: ' . $decoration . ';';
		}
		$transform = (string) ( $attrs['labelTextTransform'] ?? 'none' );
		if ( $transform !== '' ) {
			$rules[] = 'text-transform: ' . $transform . ';';
		}
		$color = sanitize_hex_color( trim( (string) ( $attrs['labelTextColor'] ?? '' ) ) );
		if ( $color !== '' && $color !== null ) {
			$rules[] = 'color: ' . $color . ';';
		}
		return implode( ' ', $rules );
	}

	/**
	 * Build inline CSS for title (separate typography)
	 */
	private function build_title_style_css( array $attrs ): string {
		$rules = [];
		$size = (int) ( $attrs['titleFontSize'] ?? 0 );
		if ( $size > 0 ) {
			$rules[] = 'font-size: ' . $size . 'pt;';
		}
		$weight = (string) ( $attrs['titleFontWeight'] ?? 'bold' );
		if ( $weight !== '' ) {
			$rules[] = 'font-weight: ' . $weight . ';';
		}
		$style = (string) ( $attrs['titleFontStyle'] ?? 'normal' );
		if ( $style !== '' ) {
			$rules[] = 'font-style: ' . $style . ';';
		}
		$decoration = (string) ( $attrs['titleTextDecoration'] ?? 'none' );
		if ( $decoration !== '' ) {
			$rules[] = 'text-decoration: ' . $decoration . ';';
		}
		$transform = (string) ( $attrs['titleTextTransform'] ?? 'none' );
		if ( $transform !== '' ) {
			$rules[] = 'text-transform: ' . $transform . ';';
		}
		$color = sanitize_hex_color( trim( (string) ( $attrs['titleTextColor'] ?? '' ) ) );
		if ( $color !== '' && $color !== null ) {
			$rules[] = 'color: ' . $color . ';';
		}
		return implode( ' ', $rules );
	}

	// ========================================
	// Data Helpers
	// ========================================

	/**
	 * Get shop data
	 */
	private function get_shop_data(): array {
		// Try WooCommerce settings first
		if ( function_exists( 'wc_get_base_location' ) ) {
			$base = wc_get_base_location();
			$address = get_option( 'woocommerce_store_address', '' );
			$address2 = get_option( 'woocommerce_store_address_2', '' );
			$city = get_option( 'woocommerce_store_city', '' );
			$postcode = get_option( 'woocommerce_store_postcode', '' );

			$full_address = $address;
			if ( $address2 ) {
				$full_address .= "\n" . $address2;
			}
			$full_address .= "\n" . $city . ', ' . ( $base['state'] ?? '' ) . ' ' . $postcode;

			return [
				'name'    => get_bloginfo( 'name' ),
				'address' => $full_address,
				'phone'   => get_option( 'woocommerce_store_phone', '' ),
				'email'   => get_option( 'woocommerce_email_from_address', get_option( 'admin_email' ) ),
			];
		}

		// Fallback
		return [
			'name'    => get_bloginfo( 'name' ),
			'address' => get_option( 'blogdescription', '' ),
			'phone'   => '',
			'email'   => get_option( 'admin_email' ),
		];
	}

	/**
	 * Get logo URL
	 */
	private function get_logo_url( string $source = 'site' ): string {
		if ( $source === 'custom' ) {
			// Custom logo would be stored in settings
			return get_option( 'checkmate_pdf_custom_logo', '' );
		}

		// Site logo
		$logo_id = get_theme_mod( 'custom_logo' );
		if ( $logo_id ) {
			return wp_get_attachment_image_url( $logo_id, 'full' );
		}

		return '';
	}

	/**
	 * Get font family for CSS
	 */
	private function get_font_family(): string {
		$ps = $this->template->get_page_settings();
		$fonts = [
			'dejavu-sans' => 'DejaVu Sans',
			'helvetica'   => 'Helvetica',
			'times'       => 'Times',
			'courier'     => 'Courier',
		];
		return $fonts[ $ps['fontFamily'] ?? 'dejavu-sans' ] ?? 'DejaVu Sans';
	}

	/**
	 * Get document title based on type
	 */
	private function get_document_title(): string {
		$type = $this->template->get_document_type();
		$titles = [
			'invoice'       => __( 'Invoice', 'checkmate-pdf-invoices' ),
			'packing-slip'  => __( 'Packing Slip', 'checkmate-pdf-invoices' ),
			'credit-note'   => __( 'Credit Note', 'checkmate-pdf-invoices' ),
			'delivery-note' => __( 'Delivery Note', 'checkmate-pdf-invoices' ),
		];
		return $titles[ $type ] ?? __( 'Invoice', 'checkmate-pdf-invoices' );
	}

	/**
	 * Get document number
	 */
	private function get_document_number(): string {
		if ( $this->order_data && ! empty( $this->order_data['document_number'] ) ) {
			return $this->order_data['document_number'];
		}

		// Generate sample number for preview
		return '#INV-' . gmdate( 'Y' ) . '-001';
	}

	/**
	 * Get document date
	 */
	private function get_document_date( string $format = 'F j, Y' ): string {
		if ( $this->order_data && ! empty( $this->order_data['document_date'] ) ) {
			return gmdate( $format, strtotime( $this->order_data['document_date'] ) );
		}

		return gmdate( $format );
	}

	/**
	 * Get address data
	 */
	private function get_address( string $type ): array {
		if ( $this->order_data && ! empty( $this->order_data[ $type . '_address' ] ) ) {
			return $this->order_data[ $type . '_address' ];
		}

		// Sample data for preview
		return [
			'name'    => 'John Doe',
			'address' => "456 Customer Lane\nAnytown, ST 54321",
			'email'   => 'john@example.com',
			'phone'   => '(555) 987-6543',
		];
	}

	/**
	 * Get order items
	 */
	private function get_order_items(): array {
		if ( $this->order_data && ! empty( $this->order_data['items'] ) ) {
			return $this->order_data['items'];
		}

		// Sample data for preview
		return [
			[
				'product'  => 'Premium Widget',
				'sku'      => 'WDG-001',
				'quantity' => '2',
				'price'    => '$29.99',
				'total'    => '$59.98',
				'weight'   => '0.5 kg',
				'tax'      => '$6.00',
			],
			[
				'product'  => 'Super Gadget Pro',
				'sku'      => 'GDT-002',
				'quantity' => '1',
				'price'    => '$149.99',
				'total'    => '$149.99',
				'weight'   => '1.2 kg',
				'tax'      => '$15.00',
			],
		];
	}

	/**
	 * Get order totals
	 */
	private function get_order_totals(): array {
		if ( $this->order_data && ! empty( $this->order_data['totals'] ) ) {
			return $this->order_data['totals'];
		}

		// Sample data for preview
		return [
			'subtotal' => '$209.97',
			'shipping' => '$9.99',
			'discount' => '-$20.00',
			'tax'      => '$21.00',
			'total'    => '$220.96',
		];
	}

	/**
	 * Process dynamic content tags
	 */
	private function process_dynamic_content( string $content ): string {
		$billing  = $this->get_address( 'billing' );
		$shipping = $this->get_address( 'shipping' );
		$now      = gmdate( 'F j, Y' );

		$replacements = [
			'{shop_name}'    => $this->shop_data['name'] ?? '',
			'{shop_email}'   => $this->shop_data['email'] ?? '',
			'{shop_phone}'   => $this->shop_data['phone'] ?? '',
			'{current_date}' => $now,
			'{current_year}' => gmdate( 'Y' ),
			'{site_url}'     => home_url(),

			// Order (sample defaults; overridden when real order data exists)
			'{order_number}'     => '#WC-12345',
			'{order_date}'       => $now,
			'{order_total}'      => '$220.96',
			'{payment_method}'   => 'Credit Card (Stripe)',
			'{shipping_method}'  => 'Flat Rate Shipping',

			// Customer (aliases billing address)
			'{customer_name}'    => $billing['name'] ?? '',
			'{customer_address}' => $billing['address'] ?? '',
			'{customer_email}'   => $billing['email'] ?? '',
			'{customer_phone}'   => $billing['phone'] ?? '',

			// Billing address
			'{billing_name}'    => $billing['name'] ?? '',
			'{billing_address}' => $billing['address'] ?? '',
			'{billing_email}'   => $billing['email'] ?? '',
			'{billing_phone}'   => $billing['phone'] ?? '',

			// Shipping address
			'{shipping_name}'    => $shipping['name'] ?? '',
			'{shipping_address}' => $shipping['address'] ?? '',
			'{shipping_email}'   => $shipping['email'] ?? '',
			'{shipping_phone}'   => $shipping['phone'] ?? '',
		];

		if ( $this->order_data ) {
			$replacements['{order_number}'] = $this->order_data['order_number'] ?? $replacements['{order_number}'];
			if ( ! empty( $this->order_data['order_date'] ) ) {
				$ts = strtotime( (string) $this->order_data['order_date'] );
				$replacements['{order_date}'] = $ts !== false ? gmdate( 'F j, Y', $ts ) : (string) $this->order_data['order_date'];
			}
			$replacements['{order_total}']  = $this->order_data['totals']['total'] ?? $replacements['{order_total}'];
			$replacements['{payment_method}']  = $this->order_data['payment_method'] ?? $replacements['{payment_method}'];
			$replacements['{shipping_method}'] = $this->order_data['shipping_method'] ?? $replacements['{shipping_method}'];
		}

		return strtr( $content, $replacements );
	}
}
