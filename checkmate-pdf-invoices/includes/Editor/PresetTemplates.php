<?php
/**
 * Preset Templates - Pre-built templates for quick start
 *
 * @package Checkmate\PdfInvoices\Editor
 */

namespace Checkmate\PdfInvoices\Editor;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * PresetTemplates class
 */
class PresetTemplates {

	/**
	 * Get preset templates catalog (metadata only; no blocks generation).
	 *
	 * @return array
	 */
	public static function get_catalog(): array {
		return [
			'modern-invoice' => [
				'id'            => 'modern-invoice',
				'name'          => __( 'Modern Invoice', 'checkmate-pdf-invoices' ),
				'description'   => __( 'Clean, contemporary design with bold header', 'checkmate-pdf-invoices' ),
				'document_type' => 'invoice',
				'preview_image' => 'modern-invoice.png',
				'popular'       => true,
			],
			'classic-invoice' => [
				'id'            => 'classic-invoice',
				'name'          => __( 'Classic Invoice', 'checkmate-pdf-invoices' ),
				'description'   => __( 'Traditional professional design', 'checkmate-pdf-invoices' ),
				'document_type' => 'invoice',
				'preview_image' => 'classic-invoice.png',
				'popular'       => false,
			],
			'minimal-invoice' => [
				'id'            => 'minimal-invoice',
				'name'          => __( 'Minimal Invoice', 'checkmate-pdf-invoices' ),
				'description'   => __( 'Simple, clean design with minimal elements', 'checkmate-pdf-invoices' ),
				'document_type' => 'invoice',
				'preview_image' => 'minimal-invoice.png',
				'popular'       => true,
			],
			'modern-packing' => [
				'id'            => 'modern-packing',
				'name'          => __( 'Modern Packing Slip', 'checkmate-pdf-invoices' ),
				'description'   => __( 'Clean packing slip without prices', 'checkmate-pdf-invoices' ),
				'document_type' => 'packing-slip',
				'preview_image' => 'modern-packing.png',
				'popular'       => false,
			],
			'classic-packing' => [
				'id'            => 'classic-packing',
				'name'          => __( 'Classic Packing Slip', 'checkmate-pdf-invoices' ),
				'description'   => __( 'Traditional packing slip layout', 'checkmate-pdf-invoices' ),
				'document_type' => 'packing-slip',
				'preview_image' => 'classic-packing.png',
				'popular'       => false,
			],
			'minimal-packing' => [
				'id'            => 'minimal-packing',
				'name'          => __( 'Minimal Packing Slip', 'checkmate-pdf-invoices' ),
				'description'   => __( 'Bare essentials packing slip', 'checkmate-pdf-invoices' ),
				'document_type' => 'packing-slip',
				'preview_image' => 'minimal-packing.png',
				'popular'       => false,
			],
			'modern-credit-note' => [
				'id'            => 'modern-credit-note',
				'name'          => __( 'Modern Credit Note', 'checkmate-pdf-invoices' ),
				'description'   => __( 'Modern credit note layout based on the modern invoice', 'checkmate-pdf-invoices' ),
				'document_type' => 'credit-note',
				'preview_image' => 'modern-credit-note.png',
				'popular'       => false,
			],
			'classic-credit-note' => [
				'id'            => 'classic-credit-note',
				'name'          => __( 'Classic Credit Note', 'checkmate-pdf-invoices' ),
				'description'   => __( 'Traditional credit note with clear structure', 'checkmate-pdf-invoices' ),
				'document_type' => 'credit-note',
				'preview_image' => 'classic-credit-note.png',
				'popular'       => false,
			],
			'minimal-credit-note' => [
				'id'            => 'minimal-credit-note',
				'name'          => __( 'Minimal Credit Note', 'checkmate-pdf-invoices' ),
				'description'   => __( 'Minimal credit note focused on the essentials', 'checkmate-pdf-invoices' ),
				'document_type' => 'credit-note',
				'preview_image' => 'minimal-credit-note.png',
				'popular'       => false,
			],
			'modern-delivery-note' => [
				'id'            => 'modern-delivery-note',
				'name'          => __( 'Modern Delivery Note', 'checkmate-pdf-invoices' ),
				'description'   => __( 'Modern delivery note optimized for fulfillment', 'checkmate-pdf-invoices' ),
				'document_type' => 'delivery-note',
				'preview_image' => 'modern-delivery-note.png',
				'popular'       => false,
			],
			'classic-delivery-note' => [
				'id'            => 'classic-delivery-note',
				'name'          => __( 'Classic Delivery Note', 'checkmate-pdf-invoices' ),
				'description'   => __( 'Traditional delivery note layout', 'checkmate-pdf-invoices' ),
				'document_type' => 'delivery-note',
				'preview_image' => 'classic-delivery-note.png',
				'popular'       => false,
			],
			'minimal-delivery-note' => [
				'id'            => 'minimal-delivery-note',
				'name'          => __( 'Minimal Delivery Note', 'checkmate-pdf-invoices' ),
				'description'   => __( 'Minimal delivery note for quick printing', 'checkmate-pdf-invoices' ),
				'document_type' => 'delivery-note',
				'preview_image' => 'minimal-delivery-note.png',
				'popular'       => false,
			],
		];
	}

	/**
	 * Get all preset templates
	 *
	 * @return array
	 */
	public static function get_all(): array {
		return [
			'modern-invoice'    => self::modern_invoice(),
			'classic-invoice'   => self::classic_invoice(),
			'minimal-invoice'   => self::minimal_invoice(),
			'compact-invoice'   => self::compact_invoice(),
			'bold-invoice'      => self::bold_invoice(),
			'modern-packing'    => self::modern_packing_slip(),
			'classic-packing'   => self::classic_packing_slip(),
			'minimal-packing'   => self::minimal_packing_slip(),
			'compact-packing'   => self::compact_packing_slip(),
			'warehouse-packing' => self::warehouse_packing_slip(),
			'modern-credit-note'  => self::modern_credit_note(),
			'classic-credit-note' => self::classic_credit_note(),
			'minimal-credit-note' => self::minimal_credit_note(),
			'modern-delivery-note'  => self::modern_delivery_note(),
			'classic-delivery-note' => self::classic_delivery_note(),
			'minimal-delivery-note' => self::minimal_delivery_note(),
		];
	}

	/**
	 * Get a specific preset
	 *
	 * @param string $preset_id Preset ID.
	 * @return array|null
	 */
	public static function get( string $preset_id ): ?array {
		$presets = self::get_all();
		return $presets[ $preset_id ] ?? null;
	}

	/**
	 * Get presets by document type
	 *
	 * @param string $document_type Document type.
	 * @return array
	 */
	public static function get_by_type( string $document_type ): array {
		$all     = self::get_all();
		$matched = [];

		foreach ( $all as $id => $preset ) {
			if ( $preset['document_type'] === $document_type ) {
				$matched[ $id ] = $preset;
			}
		}

		return $matched;
	}

	/**
	 * Create a Template instance from a preset
			'compact-invoice' => [
				'id'            => 'compact-invoice',
				'name'          => __( 'Compact Invoice', 'checkmate-pdf-invoices' ),
				'description'   => __( 'Space-efficient invoice with tighter spacing', 'checkmate-pdf-invoices' ),
				'document_type' => 'invoice',
				'preview_image' => 'compact-invoice.png',
				'popular'       => false,
			],
			'bold-invoice' => [
				'id'            => 'bold-invoice',
				'name'          => __( 'Bold Invoice', 'checkmate-pdf-invoices' ),
				'description'   => __( 'High-contrast invoice with bold headers', 'checkmate-pdf-invoices' ),
				'document_type' => 'invoice',
				'preview_image' => 'bold-invoice.png',
				'popular'       => false,
			],
	 *
	 * @param string $preset_id Preset ID.
	 * @param string $name      Template name.
	 * @return Template|null
	 */
	public static function create_template( string $preset_id, string $name = '' ): ?Template {
		$preset = self::get( $preset_id );
		if ( ! $preset ) {
			return null;
		}

		return new Template( [
			'name'          => $name ?: $preset['name'],
			'document_type' => $preset['document_type'],
			'blocks'        => $preset['blocks'],
			'page_settings' => $preset['page_settings'],
		] );
	}

	/**
			'compact-packing' => [
				'id'            => 'compact-packing',
				'name'          => __( 'Compact Packing Slip', 'checkmate-pdf-invoices' ),
				'description'   => __( 'Compact packing slip with tighter spacing', 'checkmate-pdf-invoices' ),
				'document_type' => 'packing-slip',
				'preview_image' => 'compact-packing.png',
				'popular'       => false,
			],
			'warehouse-packing' => [
				'id'            => 'warehouse-packing',
				'name'          => __( 'Warehouse Packing Slip', 'checkmate-pdf-invoices' ),
				'description'   => __( 'Fulfillment-friendly packing slip with SKU focus', 'checkmate-pdf-invoices' ),
				'document_type' => 'packing-slip',
				'preview_image' => 'warehouse-packing.png',
				'popular'       => false,
			],
	 * Recursively merge attributes into blocks of a given type.
	 *
	 * @param array  $blocks Blocks array.
	 * @param string $type   Block type.
	 * @param array  $attrs  Attributes to merge.
	 * @return array
	 */
	private static function merge_attrs_for_block_type( array $blocks, string $type, array $attrs ): array {
		foreach ( $blocks as $i => $block ) {
			if ( is_array( $block ) && ( $block['type'] ?? '' ) === $type ) {
				$existing = isset( $block['attributes'] ) && is_array( $block['attributes'] ) ? $block['attributes'] : [];
				$block['attributes'] = array_merge( $existing, $attrs );
			}

			if ( ! empty( $block['children'] ) && is_array( $block['children'] ) ) {
				$block['children'] = self::merge_attrs_for_block_type( $block['children'], $type, $attrs );
			}

			$blocks[ $i ] = $block;
		}

		return $blocks;
	}

	/**
	 * Modern Invoice Template
	 *
	 * @return array
	 */
	private static function modern_invoice(): array {
		$registry = BlockRegistry::instance();

		return [
			'id'            => 'modern-invoice',
			'name'          => __( 'Modern Invoice', 'checkmate-pdf-invoices' ),
			'description'   => __( 'Clean, contemporary design with bold header', 'checkmate-pdf-invoices' ),
			'document_type' => 'invoice',
			'preview_image' => 'modern-invoice.png',
			'popular'       => true,
			'page_settings' => [
				'paperSize'       => 'a4',
				'orientation'     => 'portrait',
				'marginTop'       => 15,
				'marginRight'     => 15,
				'marginBottom'    => 15,
				'marginLeft'      => 15,
				'fontFamily'      => 'DejaVu Sans',
				'baseFontSize'    => 9,
				'textColor'       => '#333333',
				'backgroundColor' => '#ffffff',
			],
			'blocks'        => [
				// Document Info Row: Shop Info | Dates & Numbers
				[
					'id'         => wp_generate_uuid4(),
					'type'       => 'row',
					'attributes' => [ 'gap' => 20, 'verticalAlign' => 'top' ],
					'children'   => [
						[
							'id'         => wp_generate_uuid4(),
							'type'       => 'column',
							'attributes' => [ 'width' => 50 ],
							'children'   => [
								$registry->create_block( 'logo', [
									'maxHeight' => 60,
									'align'     => 'left',
								] ),
								$registry->create_block( 'spacer', [ 'height' => 10 ] ),
								$registry->create_block( 'shop-info', [
									'showName'    => true,
									'showAddress' => true,
									'showPhone'   => true,
									'showEmail'   => true,
									'fontSize'    => 9,
								] ),
							],
						],
						[
							'id'         => wp_generate_uuid4(),
							'type'       => 'column',
							'attributes' => [ 'width' => 50 ],
							'children'   => [
								$registry->create_block( 'document-title', [
									'fontSize'      => 24,
									'fontWeight'    => 'bold',
									'textTransform' => 'uppercase',
									'align'         => 'right',
								] ),
								$registry->create_block( 'spacer', [ 'height' => 10 ] ),
								$registry->create_block( 'document-number', [
									'label'     => __( 'Invoice #', 'checkmate-pdf-invoices' ),
									'showLabel' => true,
									'fontSize'  => 10,
								] ),
								$registry->create_block( 'document-date', [
									'label'     => __( 'Date:', 'checkmate-pdf-invoices' ),
									'showLabel' => true,
									'format'    => 'F j, Y',
									'fontSize'  => 9,
								] ),
								$registry->create_block( 'order-number', [
									'label'     => __( 'Order #', 'checkmate-pdf-invoices' ),
									'showLabel' => true,
									'fontSize'  => 9,
								] ),
							],
						],
					],
				],
				$registry->create_block( 'spacer', [ 'height' => 25 ] ),
				$registry->create_block( 'divider', [
					'color'     => '#000000',
					'thickness' => 2,
					'style'     => 'solid',
				] ),
				$registry->create_block( 'spacer', [ 'height' => 25 ] ),
				// Addresses Row
				[
					'id'         => wp_generate_uuid4(),
					'type'       => 'row',
					'attributes' => [ 'gap' => 20, 'verticalAlign' => 'top' ],
					'children'   => [
						[
							'id'         => wp_generate_uuid4(),
							'type'       => 'column',
							'attributes' => [ 'width' => 50 ],
							'children'   => [
								$registry->create_block( 'billing-address', [
									'title'     => __( 'Bill To:', 'checkmate-pdf-invoices' ),
									'showTitle' => true,
									'showEmail' => true,
									'showPhone' => true,
									'fontSize'  => 9,
								] ),
							],
						],
						[
							'id'         => wp_generate_uuid4(),
							'type'       => 'column',
							'attributes' => [ 'width' => 50 ],
							'children'   => [
								$registry->create_block( 'shipping-address', [
									'title'     => __( 'Ship To:', 'checkmate-pdf-invoices' ),
									'showTitle' => true,
									'showPhone' => false,
									'fontSize'  => 9,
								] ),
							],
						],
					],
				],
				$registry->create_block( 'spacer', [ 'height' => 25 ] ),
				// Items Table
				$registry->create_block( 'items-table', [
					'columns'          => [ 'product', 'sku', 'quantity', 'price', 'total' ],
					'showHeader'       => true,
					'headerBackground' => '#000000',
					'headerColor'      => '#ffffff',
					'showSku'          => true,
					'showMeta'         => true,
					'borderColor'      => '#e0e0e0',
					'fontSize'         => 9,
				] ),
				$registry->create_block( 'spacer', [ 'height' => 20 ] ),
				// Totals
				$registry->create_block( 'totals-table', [
					'showSubtotal' => true,
					'showShipping' => true,
					'showTax'      => true,
					'showDiscount' => true,
					'showTotal'    => true,
					'totalBold'    => true,
					'align'        => 'right',
					'width'        => 40,
					'fontSize'     => 9,
				] ),
				$registry->create_block( 'spacer', [ 'height' => 30 ] ),
				// Payment Info Row
				[
					'id'         => wp_generate_uuid4(),
					'type'       => 'row',
					'attributes' => [ 'gap' => 20, 'verticalAlign' => 'top' ],
					'children'   => [
						[
							'id'         => wp_generate_uuid4(),
							'type'       => 'column',
							'attributes' => [ 'width' => 50 ],
							'children'   => [
								$registry->create_block( 'payment-method', [
									'label'     => __( 'Payment Method:', 'checkmate-pdf-invoices' ),
									'showLabel' => true,
									'fontSize'  => 9,
								] ),
								$registry->create_block( 'shipping-method', [
									'label'     => __( 'Shipping Method:', 'checkmate-pdf-invoices' ),
									'showLabel' => true,
									'fontSize'  => 9,
								] ),
							],
						],
						[
							'id'         => wp_generate_uuid4(),
							'type'       => 'column',
							'attributes' => [ 'width' => 50 ],
							'children'   => [
								$registry->create_block( 'customer-note', [
									'title'       => __( 'Customer Note:', 'checkmate-pdf-invoices' ),
									'showTitle'   => true,
									'hideIfEmpty' => true,
									'fontSize'    => 9,
								] ),
							],
						],
					],
				],
				$registry->create_block( 'spacer', [ 'height' => 30 ] ),
				// Notes
				$registry->create_block( 'notes', [
					'title'     => __( 'Notes:', 'checkmate-pdf-invoices' ),
					'showTitle' => true,
					'content'   => __( 'Thank you for your business!', 'checkmate-pdf-invoices' ),
					'fontSize'  => 8,
				] ),
				$registry->create_block( 'spacer', [ 'height' => 20 ] ),
				// Footer
				$registry->create_block( 'footer', [
					'content'  => '',
					'fontSize' => 7,
					'color'    => '#666666',
					'align'    => 'center',
				] ),
			],
		];
	}

	/**
	 * Compact Invoice Template (derived from Minimal Invoice)
	 */
	private static function compact_invoice(): array {
		$preset = self::minimal_invoice();
		$preset['id'] = 'compact-invoice';
		$preset['name'] = __( 'Compact Invoice', 'checkmate-pdf-invoices' );
		$preset['description'] = __( 'Space-efficient invoice with tighter spacing', 'checkmate-pdf-invoices' );
		$preset['document_type'] = 'invoice';
		$preset['preview_image'] = 'compact-invoice.png';
		$preset['popular'] = false;

		$preset['page_settings']['marginTop'] = 12;
		$preset['page_settings']['marginRight'] = 12;
		$preset['page_settings']['marginBottom'] = 12;
		$preset['page_settings']['marginLeft'] = 12;
		$preset['page_settings']['baseFontSize'] = 8;

		$preset['blocks'] = self::merge_attrs_for_block_type( $preset['blocks'], 'document-title', [
			'fontSize' => 14,
			'align'    => 'left',
		] );
		$preset['blocks'] = self::merge_attrs_for_block_type( $preset['blocks'], 'items-table', [
			'fontSize'         => 8,
			'borderColor'      => '#e6e6e6',
			'headerBackground' => '#ffffff',
			'headerColor'      => '#666666',
		] );
		$preset['blocks'] = self::merge_attrs_for_block_type( $preset['blocks'], 'totals-table', [
			'fontSize'      => 8,
			'width'         => 45,
			'align'         => 'right',
			'labelPosition' => 'left',
		] );

		return $preset;
	}

	/**
	 * Bold Invoice Template (derived from Modern Invoice)
	 */
	private static function bold_invoice(): array {
		$preset = self::modern_invoice();
		$preset['id'] = 'bold-invoice';
		$preset['name'] = __( 'Bold Invoice', 'checkmate-pdf-invoices' );
		$preset['description'] = __( 'High-contrast invoice with bold headers', 'checkmate-pdf-invoices' );
		$preset['document_type'] = 'invoice';
		$preset['preview_image'] = 'bold-invoice.png';
		$preset['popular'] = false;

		$preset['page_settings']['fontFamily'] = 'Helvetica';
		$preset['page_settings']['baseFontSize'] = 9;
		$preset['page_settings']['textColor'] = '#111111';

		$preset['blocks'] = self::merge_attrs_for_block_type( $preset['blocks'], 'document-title', [
			'fontSize'      => 28,
			'fontWeight'    => 'bold',
			'textTransform' => 'uppercase',
			'align'         => 'right',
		] );
		$preset['blocks'] = self::merge_attrs_for_block_type( $preset['blocks'], 'divider', [
			'color'     => '#111111',
			'thickness' => 3,
		] );
		$preset['blocks'] = self::merge_attrs_for_block_type( $preset['blocks'], 'items-table', [
			'headerBackground' => '#111111',
			'headerColor'      => '#ffffff',
			'borderColor'      => '#dddddd',
			'fontSize'         => 9,
		] );
		$preset['blocks'] = self::merge_attrs_for_block_type( $preset['blocks'], 'totals-table', [
			'width'         => 42,
			'align'         => 'right',
			'labelPosition' => 'left',
		] );

		return $preset;
	}

	/**
	 * Compact Packing Slip (derived from Minimal Packing Slip)
	 */
	private static function compact_packing_slip(): array {
		$preset = self::minimal_packing_slip();
		$preset['id'] = 'compact-packing';
		$preset['name'] = __( 'Compact Packing Slip', 'checkmate-pdf-invoices' );
		$preset['description'] = __( 'Compact packing slip with tighter spacing', 'checkmate-pdf-invoices' );
		$preset['document_type'] = 'packing-slip';
		$preset['preview_image'] = 'compact-packing.png';
		$preset['popular'] = false;

		$preset['page_settings']['marginTop'] = 16;
		$preset['page_settings']['marginRight'] = 16;
		$preset['page_settings']['marginBottom'] = 16;
		$preset['page_settings']['marginLeft'] = 16;
		$preset['page_settings']['baseFontSize'] = 8;

		$preset['blocks'] = self::merge_attrs_for_block_type( $preset['blocks'], 'items-table', [
			'fontSize'         => 8,
			'borderColor'      => '#e6e6e6',
			'headerColor'      => '#666666',
			'headerBackground' => '#ffffff',
		] );
		$preset['blocks'] = self::merge_attrs_for_block_type( $preset['blocks'], 'shop-info', [
			'fontSize' => 8,
		] );

		return $preset;
	}

	/**
	 * Warehouse Packing Slip (derived from Classic Packing Slip)
	 */
	private static function warehouse_packing_slip(): array {
		$preset = self::classic_packing_slip();
		$preset['id'] = 'warehouse-packing';
		$preset['name'] = __( 'Warehouse Packing Slip', 'checkmate-pdf-invoices' );
		$preset['description'] = __( 'Fulfillment-friendly packing slip with SKU focus', 'checkmate-pdf-invoices' );
		$preset['document_type'] = 'packing-slip';
		$preset['preview_image'] = 'warehouse-packing.png';
		$preset['popular'] = false;

		$preset['page_settings']['fontFamily'] = 'Courier';
		$preset['page_settings']['baseFontSize'] = 9;

		$preset['blocks'] = self::merge_attrs_for_block_type( $preset['blocks'], 'items-table', [
			'columns'     => [ 'product', 'sku', 'quantity' ],
			'showSku'     => true,
			'showMeta'    => false,
			'borderColor' => '#cfcfcf',
			'fontSize'    => 9,
			'textAlign'   => 'left',
		] );
		$preset['blocks'] = self::merge_attrs_for_block_type( $preset['blocks'], 'shipping-address', [
			'showPhone' => true,
		] );

		return $preset;
	}

	/**
	 * Classic Invoice Template
	 *
	 * @return array
	 */
	private static function classic_invoice(): array {
		$registry = BlockRegistry::instance();

		return [
			'id'            => 'classic-invoice',
			'name'          => __( 'Classic Invoice', 'checkmate-pdf-invoices' ),
			'description'   => __( 'Traditional professional design', 'checkmate-pdf-invoices' ),
			'document_type' => 'invoice',
			'preview_image' => 'classic-invoice.png',
			'popular'       => false,
			'page_settings' => [
				'paperSize'       => 'a4',
				'orientation'     => 'portrait',
				'marginTop'       => 20,
				'marginRight'     => 20,
				'marginBottom'    => 20,
				'marginLeft'      => 20,
				'fontFamily'      => 'Times',
				'baseFontSize'    => 10,
				'textColor'       => '#000000',
				'backgroundColor' => '#ffffff',
			],
			'blocks'        => [
				// Centered Header
				$registry->create_block( 'logo', [
					'maxHeight' => 80,
					'align'     => 'center',
				] ),
				$registry->create_block( 'spacer', [ 'height' => 10 ] ),
				$registry->create_block( 'shop-info', [
					'showName'    => true,
					'showAddress' => true,
					'showPhone'   => true,
					'showEmail'   => true,
					'fontSize'    => 9,
					'align'       => 'center',
				] ),
				$registry->create_block( 'spacer', [ 'height' => 20 ] ),
				$registry->create_block( 'divider', [
					'color'     => '#333333',
					'thickness' => 1,
					'style'     => 'solid',
				] ),
				$registry->create_block( 'spacer', [ 'height' => 20 ] ),
				// Document Title centered
				$registry->create_block( 'document-title', [
					'fontSize'      => 18,
					'fontWeight'    => 'bold',
					'textTransform' => 'uppercase',
					'align'         => 'center',
				] ),
				$registry->create_block( 'spacer', [ 'height' => 15 ] ),
				// Document Info Row
				[
					'id'         => wp_generate_uuid4(),
					'type'       => 'row',
					'attributes' => [ 'gap' => 20, 'verticalAlign' => 'top' ],
					'children'   => [
						[
							'id'         => wp_generate_uuid4(),
							'type'       => 'column',
							'attributes' => [ 'width' => 50 ],
							'children'   => [
								$registry->create_block( 'document-number', [
									'label'     => __( 'Invoice Number:', 'checkmate-pdf-invoices' ),
									'showLabel' => true,
									'fontSize'  => 10,
								] ),
								$registry->create_block( 'order-number', [
									'label'     => __( 'Order Number:', 'checkmate-pdf-invoices' ),
									'showLabel' => true,
									'fontSize'  => 10,
								] ),
							],
						],
						[
							'id'         => wp_generate_uuid4(),
							'type'       => 'column',
							'attributes' => [ 'width' => 50 ],
							'children'   => [
								$registry->create_block( 'document-date', [
									'label'     => __( 'Invoice Date:', 'checkmate-pdf-invoices' ),
									'showLabel' => true,
									'format'    => 'F j, Y',
									'fontSize'  => 10,
								] ),
								$registry->create_block( 'order-date', [
									'label'     => __( 'Order Date:', 'checkmate-pdf-invoices' ),
									'showLabel' => true,
									'format'    => 'F j, Y',
									'fontSize'  => 10,
								] ),
							],
						],
					],
				],
				$registry->create_block( 'spacer', [ 'height' => 20 ] ),
				// Addresses
				[
					'id'         => wp_generate_uuid4(),
					'type'       => 'row',
					'attributes' => [ 'gap' => 30, 'verticalAlign' => 'top' ],
					'children'   => [
						[
							'id'         => wp_generate_uuid4(),
							'type'       => 'column',
							'attributes' => [ 'width' => 50 ],
							'children'   => [
								$registry->create_block( 'billing-address', [
									'title'     => __( 'BILLING ADDRESS', 'checkmate-pdf-invoices' ),
									'showTitle' => true,
									'showEmail' => true,
									'showPhone' => true,
									'fontSize'  => 9,
								] ),
							],
						],
						[
							'id'         => wp_generate_uuid4(),
							'type'       => 'column',
							'attributes' => [ 'width' => 50 ],
							'children'   => [
								$registry->create_block( 'shipping-address', [
									'title'     => __( 'SHIPPING ADDRESS', 'checkmate-pdf-invoices' ),
									'showTitle' => true,
									'showPhone' => false,
									'fontSize'  => 9,
								] ),
							],
						],
					],
				],
				$registry->create_block( 'spacer', [ 'height' => 25 ] ),
				// Items Table
				$registry->create_block( 'items-table', [
					'columns'          => [ 'product', 'quantity', 'price', 'total' ],
					'showHeader'       => true,
					'headerBackground' => '#f5f5f5',
					'headerColor'      => '#000000',
					'showSku'          => true,
					'showMeta'         => true,
					'borderColor'      => '#cccccc',
					'fontSize'         => 9,
				] ),
				$registry->create_block( 'spacer', [ 'height' => 15 ] ),
				// Totals
				$registry->create_block( 'totals-table', [
					'showSubtotal' => true,
					'showShipping' => true,
					'showTax'      => true,
					'showDiscount' => true,
					'showTotal'    => true,
					'totalBold'    => true,
					'align'        => 'right',
					'width'        => 35,
					'fontSize'     => 9,
				] ),
				$registry->create_block( 'spacer', [ 'height' => 25 ] ),
				$registry->create_block( 'divider', [
					'color'     => '#cccccc',
					'thickness' => 1,
					'style'     => 'solid',
				] ),
				$registry->create_block( 'spacer', [ 'height' => 15 ] ),
				// Notes
				$registry->create_block( 'notes', [
					'title'     => '',
					'showTitle' => false,
					'content'   => __( 'Thank you for your business. Payment is due within 30 days.', 'checkmate-pdf-invoices' ),
					'fontSize'  => 9,
				] ),
			],
		];
	}

	/**
	 * Minimal Invoice Template
	 *
	 * @return array
	 */
	private static function minimal_invoice(): array {
		$registry = BlockRegistry::instance();

		return [
			'id'            => 'minimal-invoice',
			'name'          => __( 'Minimal Invoice', 'checkmate-pdf-invoices' ),
			'description'   => __( 'Simple, clean design with minimal elements', 'checkmate-pdf-invoices' ),
			'document_type' => 'invoice',
			'preview_image' => 'minimal-invoice.png',
			'popular'       => true,
			'page_settings' => [
				'paperSize'       => 'a4',
				'orientation'     => 'portrait',
				'marginTop'       => 25,
				'marginRight'     => 25,
				'marginBottom'    => 25,
				'marginLeft'      => 25,
				'fontFamily'      => 'Helvetica',
				'baseFontSize'    => 9,
				'textColor'       => '#2c2c2c',
				'backgroundColor' => '#ffffff',
			],
			'blocks'        => [
				// Simple header
				[
					'id'         => wp_generate_uuid4(),
					'type'       => 'row',
					'attributes' => [ 'gap' => 20, 'verticalAlign' => 'top' ],
					'children'   => [
						[
							'id'         => wp_generate_uuid4(),
							'type'       => 'column',
							'attributes' => [ 'width' => 60 ],
							'children'   => [
								$registry->create_block( 'logo', [
									'maxHeight' => 50,
									'align'     => 'left',
								] ),
							],
						],
						[
							'id'         => wp_generate_uuid4(),
							'type'       => 'column',
							'attributes' => [ 'width' => 40 ],
							'children'   => [
								$registry->create_block( 'document-title', [
									'fontSize'      => 14,
									'fontWeight'    => 'normal',
									'textTransform' => 'uppercase',
									'color'         => '#888888',
									'align'         => 'right',
								] ),
							],
						],
					],
				],
				$registry->create_block( 'spacer', [ 'height' => 40 ] ),
				// Minimal info
				[
					'id'         => wp_generate_uuid4(),
					'type'       => 'row',
					'attributes' => [ 'gap' => 40, 'verticalAlign' => 'top' ],
					'children'   => [
						[
							'id'         => wp_generate_uuid4(),
							'type'       => 'column',
							'attributes' => [ 'width' => 50 ],
							'children'   => [
								$registry->create_block( 'billing-address', [
									'title'     => __( 'To', 'checkmate-pdf-invoices' ),
									'showTitle' => true,
									'showEmail' => true,
									'showPhone' => false,
									'fontSize'  => 9,
								] ),
							],
						],
						[
							'id'         => wp_generate_uuid4(),
							'type'       => 'column',
							'attributes' => [ 'width' => 50 ],
							'children'   => [
								$registry->create_block( 'document-number', [
									'label'     => __( 'Invoice', 'checkmate-pdf-invoices' ),
									'showLabel' => true,
									'fontSize'  => 9,
								] ),
								$registry->create_block( 'document-date', [
									'label'     => __( 'Date', 'checkmate-pdf-invoices' ),
									'showLabel' => true,
									'format'    => 'M j, Y',
									'fontSize'  => 9,
								] ),
							],
						],
					],
				],
				$registry->create_block( 'spacer', [ 'height' => 40 ] ),
				// Items Table - minimal style
				$registry->create_block( 'items-table', [
					'columns'          => [ 'product', 'quantity', 'total' ],
					'showHeader'       => true,
					'headerBackground' => '#ffffff',
					'headerColor'      => '#888888',
					'showSku'          => false,
					'showMeta'         => false,
					'borderColor'      => '#eeeeee',
					'fontSize'         => 9,
				] ),
				$registry->create_block( 'spacer', [ 'height' => 20 ] ),
				// Totals
				$registry->create_block( 'totals-table', [
					'showSubtotal' => false,
					'showShipping' => true,
					'showTax'      => true,
					'showDiscount' => true,
					'showTotal'    => true,
					'totalBold'    => true,
					'align'        => 'right',
					'width'        => 30,
					'fontSize'     => 9,
				] ),
				$registry->create_block( 'spacer', [ 'height' => 50 ] ),
				// Simple footer
				$registry->create_block( 'shop-info', [
					'showName'    => true,
					'showAddress' => true,
					'showPhone'   => true,
					'showEmail'   => true,
					'fontSize'    => 8,
					'align'       => 'center',
				] ),
			],
		];
	}

	/**
	 * Modern Packing Slip Template
	 *
	 * @return array
	 */
	private static function modern_packing_slip(): array {
		$registry = BlockRegistry::instance();

		return [
			'id'            => 'modern-packing',
			'name'          => __( 'Modern Packing Slip', 'checkmate-pdf-invoices' ),
			'description'   => __( 'Clean packing slip without prices', 'checkmate-pdf-invoices' ),
			'document_type' => 'packing-slip',
			'preview_image' => 'modern-packing.png',
			'popular'       => false,
			'page_settings' => [
				'paperSize'       => 'a4',
				'orientation'     => 'portrait',
				'marginTop'       => 15,
				'marginRight'     => 15,
				'marginBottom'    => 15,
				'marginLeft'      => 15,
				'fontFamily'      => 'DejaVu Sans',
				'baseFontSize'    => 9,
				'textColor'       => '#333333',
				'backgroundColor' => '#ffffff',
			],
			'blocks'        => [
				// Header
				[
					'id'         => wp_generate_uuid4(),
					'type'       => 'row',
					'attributes' => [ 'gap' => 20, 'verticalAlign' => 'middle' ],
					'children'   => [
						[
							'id'         => wp_generate_uuid4(),
							'type'       => 'column',
							'attributes' => [ 'width' => 50 ],
							'children'   => [
								$registry->create_block( 'logo', [
									'maxHeight' => 60,
									'align'     => 'left',
								] ),
							],
						],
						[
							'id'         => wp_generate_uuid4(),
							'type'       => 'column',
							'attributes' => [ 'width' => 50 ],
							'children'   => [
								$registry->create_block( 'document-title', [
									'fontSize'      => 20,
									'fontWeight'    => 'bold',
									'textTransform' => 'uppercase',
									'align'         => 'right',
								] ),
							],
						],
					],
				],
				$registry->create_block( 'spacer', [ 'height' => 20 ] ),
				$registry->create_block( 'divider', [
					'color'     => '#000000',
					'thickness' => 2,
					'style'     => 'solid',
				] ),
				$registry->create_block( 'spacer', [ 'height' => 20 ] ),
				// Order info
				[
					'id'         => wp_generate_uuid4(),
					'type'       => 'row',
					'attributes' => [ 'gap' => 20, 'verticalAlign' => 'top' ],
					'children'   => [
						[
							'id'         => wp_generate_uuid4(),
							'type'       => 'column',
							'attributes' => [ 'width' => 50 ],
							'children'   => [
								$registry->create_block( 'order-number', [
									'label'     => __( 'Order #', 'checkmate-pdf-invoices' ),
									'showLabel' => true,
									'fontSize'  => 10,
								] ),
								$registry->create_block( 'order-date', [
									'label'     => __( 'Order Date:', 'checkmate-pdf-invoices' ),
									'showLabel' => true,
									'format'    => 'F j, Y',
									'fontSize'  => 9,
								] ),
							],
						],
						[
							'id'         => wp_generate_uuid4(),
							'type'       => 'column',
							'attributes' => [ 'width' => 50 ],
							'children'   => [
								$registry->create_block( 'shipping-method', [
									'label'     => __( 'Shipping:', 'checkmate-pdf-invoices' ),
									'showLabel' => true,
									'fontSize'  => 9,
								] ),
							],
						],
					],
				],
				$registry->create_block( 'spacer', [ 'height' => 25 ] ),
				// Shipping address prominently
				$registry->create_block( 'heading', [
					'content'  => __( 'Ship To:', 'checkmate-pdf-invoices' ),
					'level'    => 'h3',
					'color'    => '#000000',
					'align'    => 'left',
				] ),
				$registry->create_block( 'spacer', [ 'height' => 5 ] ),
				$registry->create_block( 'shipping-address', [
					'title'     => '',
					'showTitle' => false,
					'showPhone' => true,
					'fontSize'  => 10,
				] ),
				$registry->create_block( 'spacer', [ 'height' => 25 ] ),
				// Items table - no prices
				$registry->create_block( 'items-table', [
					'columns'          => [ 'product', 'sku', 'quantity' ],
					'showHeader'       => true,
					'headerBackground' => '#f0f0f0',
					'headerColor'      => '#333333',
					'showSku'          => true,
					'showMeta'         => true,
					'borderColor'      => '#e0e0e0',
					'fontSize'         => 9,
				] ),
				$registry->create_block( 'spacer', [ 'height' => 30 ] ),
				// Customer note
				$registry->create_block( 'customer-note', [
					'title'       => __( 'Customer Note:', 'checkmate-pdf-invoices' ),
					'showTitle'   => true,
					'hideIfEmpty' => true,
					'fontSize'    => 9,
				] ),
				$registry->create_block( 'spacer', [ 'height' => 30 ] ),
				// Notes section
				$registry->create_block( 'notes', [
					'title'     => __( 'Notes:', 'checkmate-pdf-invoices' ),
					'showTitle' => true,
					'content'   => __( 'Please check the contents against this packing slip.', 'checkmate-pdf-invoices' ),
					'fontSize'  => 8,
				] ),
			],
		];
	}

	/**
	 * Classic Packing Slip Template
	 *
	 * @return array
	 */
	private static function classic_packing_slip(): array {
		$registry = BlockRegistry::instance();

		return [
			'id'            => 'classic-packing',
			'name'          => __( 'Classic Packing Slip', 'checkmate-pdf-invoices' ),
			'description'   => __( 'Traditional packing slip layout', 'checkmate-pdf-invoices' ),
			'document_type' => 'packing-slip',
			'preview_image' => 'classic-packing.png',
			'popular'       => false,
			'page_settings' => [
				'paperSize'       => 'a4',
				'orientation'     => 'portrait',
				'marginTop'       => 20,
				'marginRight'     => 20,
				'marginBottom'    => 20,
				'marginLeft'      => 20,
				'fontFamily'      => 'Times',
				'baseFontSize'    => 10,
				'textColor'       => '#000000',
				'backgroundColor' => '#ffffff',
			],
			'blocks'        => [
				// Centered logo and shop info
				$registry->create_block( 'logo', [
					'maxHeight' => 70,
					'align'     => 'center',
				] ),
				$registry->create_block( 'spacer', [ 'height' => 10 ] ),
				$registry->create_block( 'shop-info', [
					'showName'    => true,
					'showAddress' => true,
					'showPhone'   => true,
					'showEmail'   => false,
					'fontSize'    => 9,
					'align'       => 'center',
				] ),
				$registry->create_block( 'spacer', [ 'height' => 20 ] ),
				$registry->create_block( 'document-title', [
					'fontSize'      => 16,
					'fontWeight'    => 'bold',
					'textTransform' => 'uppercase',
					'align'         => 'center',
				] ),
				$registry->create_block( 'spacer', [ 'height' => 20 ] ),
				// Order and shipping info
				[
					'id'         => wp_generate_uuid4(),
					'type'       => 'row',
					'attributes' => [ 'gap' => 30, 'verticalAlign' => 'top' ],
					'children'   => [
						[
							'id'         => wp_generate_uuid4(),
							'type'       => 'column',
							'attributes' => [ 'width' => 50 ],
							'children'   => [
								$registry->create_block( 'order-number', [
									'label'     => __( 'Order:', 'checkmate-pdf-invoices' ),
									'showLabel' => true,
									'fontSize'  => 10,
								] ),
								$registry->create_block( 'order-date', [
									'label'     => __( 'Date:', 'checkmate-pdf-invoices' ),
									'showLabel' => true,
									'format'    => 'F j, Y',
									'fontSize'  => 10,
								] ),
							],
						],
						[
							'id'         => wp_generate_uuid4(),
							'type'       => 'column',
							'attributes' => [ 'width' => 50 ],
							'children'   => [
								$registry->create_block( 'shipping-address', [
									'title'     => __( 'SHIP TO:', 'checkmate-pdf-invoices' ),
									'showTitle' => true,
									'showPhone' => true,
									'fontSize'  => 9,
								] ),
							],
						],
					],
				],
				$registry->create_block( 'spacer', [ 'height' => 25 ] ),
				$registry->create_block( 'divider', [
					'color'     => '#000000',
					'thickness' => 1,
					'style'     => 'solid',
				] ),
				$registry->create_block( 'spacer', [ 'height' => 15 ] ),
				// Items table
				$registry->create_block( 'items-table', [
					'columns'          => [ 'product', 'sku', 'quantity' ],
					'showHeader'       => true,
					'headerBackground' => '#e8e8e8',
					'headerColor'      => '#000000',
					'showSku'          => true,
					'showMeta'         => true,
					'borderColor'      => '#cccccc',
					'fontSize'         => 9,
				] ),
				$registry->create_block( 'spacer', [ 'height' => 25 ] ),
				$registry->create_block( 'divider', [
					'color'     => '#cccccc',
					'thickness' => 1,
					'style'     => 'solid',
				] ),
				$registry->create_block( 'spacer', [ 'height' => 15 ] ),
				// Notes
				$registry->create_block( 'customer-note', [
					'title'       => __( 'Customer Note:', 'checkmate-pdf-invoices' ),
					'showTitle'   => true,
					'hideIfEmpty' => true,
					'fontSize'    => 9,
				] ),
			],
		];
	}

	/**
	 * Minimal Packing Slip Template
	 *
	 * @return array
	 */
	private static function minimal_packing_slip(): array {
		$registry = BlockRegistry::instance();

		return [
			'id'            => 'minimal-packing',
			'name'          => __( 'Minimal Packing Slip', 'checkmate-pdf-invoices' ),
			'description'   => __( 'Bare essentials packing slip', 'checkmate-pdf-invoices' ),
			'document_type' => 'packing-slip',
			'preview_image' => 'minimal-packing.png',
			'popular'       => false,
			'page_settings' => [
				'paperSize'       => 'a4',
				'orientation'     => 'portrait',
				'marginTop'       => 25,
				'marginRight'     => 25,
				'marginBottom'    => 25,
				'marginLeft'      => 25,
				'fontFamily'      => 'Helvetica',
				'baseFontSize'    => 9,
				'textColor'       => '#333333',
				'backgroundColor' => '#ffffff',
			],
			'blocks'        => [
				// Minimal header
				$registry->create_block( 'document-title', [
					'fontSize'      => 12,
					'fontWeight'    => 'normal',
					'textTransform' => 'uppercase',
					'color'         => '#888888',
					'align'         => 'left',
				] ),
				$registry->create_block( 'spacer', [ 'height' => 5 ] ),
				$registry->create_block( 'order-number', [
					'label'     => '',
					'showLabel' => false,
					'fontSize'  => 14,
				] ),
				$registry->create_block( 'spacer', [ 'height' => 30 ] ),
				// Shipping address
				$registry->create_block( 'text', [
					'content'    => __( 'Deliver to:', 'checkmate-pdf-invoices' ),
					'fontSize'   => 8,
					'fontWeight' => 'normal',
					'color'      => '#888888',
					'align'      => 'left',
				] ),
				$registry->create_block( 'spacer', [ 'height' => 5 ] ),
				$registry->create_block( 'shipping-address', [
					'title'     => '',
					'showTitle' => false,
					'showPhone' => true,
					'fontSize'  => 10,
				] ),
				$registry->create_block( 'spacer', [ 'height' => 35 ] ),
				// Simple items table
				$registry->create_block( 'items-table', [
					'columns'          => [ 'product', 'quantity' ],
					'showHeader'       => true,
					'headerBackground' => '#ffffff',
					'headerColor'      => '#888888',
					'showSku'          => false,
					'showMeta'         => true,
					'borderColor'      => '#eeeeee',
					'fontSize'         => 9,
				] ),
				$registry->create_block( 'spacer', [ 'height' => 40 ] ),
				// Simple footer
				$registry->create_block( 'shop-info', [
					'showName'    => true,
					'showAddress' => false,
					'showPhone'   => true,
					'showEmail'   => true,
					'fontSize'    => 8,
					'align'       => 'center',
				] ),
			],
		];
	}

	/**
	 * Modern Credit Note Template (derived from Modern Invoice)
	 */
	private static function modern_credit_note(): array {
		$preset = self::modern_invoice();
		$preset['id'] = 'modern-credit-note';
		$preset['name'] = __( 'Modern Credit Note', 'checkmate-pdf-invoices' );
		$preset['description'] = __( 'Modern credit note layout based on the modern invoice', 'checkmate-pdf-invoices' );
		$preset['document_type'] = 'credit-note';
		$preset['preview_image'] = 'modern-credit-note.png';
		$preset['popular'] = false;
		$preset['blocks'] = self::merge_attrs_for_block_type( $preset['blocks'], 'document-number', [
			'label' => __( 'Credit Note #', 'checkmate-pdf-invoices' ),
		] );
		return $preset;
	}

	/**
	 * Classic Credit Note Template (derived from Classic Invoice)
	 */
	private static function classic_credit_note(): array {
		$preset = self::classic_invoice();
		$preset['id'] = 'classic-credit-note';
		$preset['name'] = __( 'Classic Credit Note', 'checkmate-pdf-invoices' );
		$preset['description'] = __( 'Traditional credit note with clear structure', 'checkmate-pdf-invoices' );
		$preset['document_type'] = 'credit-note';
		$preset['preview_image'] = 'classic-credit-note.png';
		$preset['popular'] = false;
		$preset['blocks'] = self::merge_attrs_for_block_type( $preset['blocks'], 'document-number', [
			'label' => __( 'Credit Note #', 'checkmate-pdf-invoices' ),
		] );
		return $preset;
	}

	/**
	 * Minimal Credit Note Template (derived from Minimal Invoice)
	 */
	private static function minimal_credit_note(): array {
		$preset = self::minimal_invoice();
		$preset['id'] = 'minimal-credit-note';
		$preset['name'] = __( 'Minimal Credit Note', 'checkmate-pdf-invoices' );
		$preset['description'] = __( 'Minimal credit note focused on the essentials', 'checkmate-pdf-invoices' );
		$preset['document_type'] = 'credit-note';
		$preset['preview_image'] = 'minimal-credit-note.png';
		$preset['popular'] = false;
		$preset['blocks'] = self::merge_attrs_for_block_type( $preset['blocks'], 'document-number', [
			'label' => __( 'Credit Note #', 'checkmate-pdf-invoices' ),
		] );
		return $preset;
	}

	/**
	 * Modern Delivery Note Template (derived from Modern Packing Slip)
	 */
	private static function modern_delivery_note(): array {
		$preset = self::modern_packing_slip();
		$preset['id'] = 'modern-delivery-note';
		$preset['name'] = __( 'Modern Delivery Note', 'checkmate-pdf-invoices' );
		$preset['description'] = __( 'Modern delivery note optimized for fulfillment', 'checkmate-pdf-invoices' );
		$preset['document_type'] = 'delivery-note';
		$preset['preview_image'] = 'modern-delivery-note.png';
		$preset['popular'] = false;
		$preset['blocks'] = self::merge_attrs_for_block_type( $preset['blocks'], 'document-number', [
			'label' => __( 'Delivery Note #', 'checkmate-pdf-invoices' ),
		] );
		return $preset;
	}

	/**
	 * Classic Delivery Note Template (derived from Classic Packing Slip)
	 */
	private static function classic_delivery_note(): array {
		$preset = self::classic_packing_slip();
		$preset['id'] = 'classic-delivery-note';
		$preset['name'] = __( 'Classic Delivery Note', 'checkmate-pdf-invoices' );
		$preset['description'] = __( 'Traditional delivery note layout', 'checkmate-pdf-invoices' );
		$preset['document_type'] = 'delivery-note';
		$preset['preview_image'] = 'classic-delivery-note.png';
		$preset['popular'] = false;
		$preset['blocks'] = self::merge_attrs_for_block_type( $preset['blocks'], 'document-number', [
			'label' => __( 'Delivery Note #', 'checkmate-pdf-invoices' ),
		] );
		return $preset;
	}

	/**
	 * Minimal Delivery Note Template (derived from Minimal Packing Slip)
	 */
	private static function minimal_delivery_note(): array {
		$preset = self::minimal_packing_slip();
		$preset['id'] = 'minimal-delivery-note';
		$preset['name'] = __( 'Minimal Delivery Note', 'checkmate-pdf-invoices' );
		$preset['description'] = __( 'Minimal delivery note for quick printing', 'checkmate-pdf-invoices' );
		$preset['document_type'] = 'delivery-note';
		$preset['preview_image'] = 'minimal-delivery-note.png';
		$preset['popular'] = false;
		$preset['blocks'] = self::merge_attrs_for_block_type( $preset['blocks'], 'document-number', [
			'label' => __( 'Delivery Note #', 'checkmate-pdf-invoices' ),
		] );
		return $preset;
	}
}
