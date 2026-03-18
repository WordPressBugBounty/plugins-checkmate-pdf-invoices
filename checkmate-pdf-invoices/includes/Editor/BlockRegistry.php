<?php
/**
 * Block Registry - Defines all available blocks for the PDF template editor
 *
 * @package Checkmate\PdfInvoices\Editor
 */

namespace Checkmate\PdfInvoices\Editor;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Block Registry class
 */
class BlockRegistry {

	/**
	 * Registered blocks
	 *
	 * @var array
	 */
	private array $blocks = [];

	/**
	 * Block categories
	 *
	 * @var array
	 */
	private array $categories = [];

	/**
	 * Singleton instance
	 *
	 * @var BlockRegistry|null
	 */
	private static ?BlockRegistry $instance = null;

	/**
	 * Get singleton instance
	 *
	 * @return BlockRegistry
	 */
	public static function instance(): BlockRegistry {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor
	 */
	private function __construct() {
		$this->register_categories();
		$this->register_blocks();
	}

	/**
	 * Register block categories
	 */
	private function register_categories(): void {
		$this->categories = [
			'layout' => [
				'id'    => 'layout',
				'title' => __( 'Layout', 'checkmate-pdf-invoices' ),
				'icon'  => 'layout',
			],
			'content' => [
				'id'    => 'content',
				'title' => __( 'Content', 'checkmate-pdf-invoices' ),
				'icon'  => 'text',
			],
			'document' => [
				'id'    => 'document',
				'title' => __( 'Document', 'checkmate-pdf-invoices' ),
				'icon'  => 'file-text',
			],
			'order' => [
				'id'    => 'order',
				'title' => __( 'Order Data', 'checkmate-pdf-invoices' ),
				'icon'  => 'shopping-cart',
			],
			'tables' => [
				'id'    => 'tables',
				'title' => __( 'Tables', 'checkmate-pdf-invoices' ),
				'icon'  => 'table',
			],
		];
	}

	/**
	 * Register all blocks
	 */
	private function register_blocks(): void {
		// Layout blocks
		$this->register_block( 'row', [
			'title'       => __( 'Row', 'checkmate-pdf-invoices' ),
			'description' => __( 'Horizontal container for columns', 'checkmate-pdf-invoices' ),
			'category'    => 'layout',
			'icon'        => 'columns',
			'supports'    => [ 'children' ],
			'allowed_children' => [ 'column' ],
			'attributes'  => [
				'gap' => [
					'type'    => 'number',
					'default' => 10,
					'label'   => __( 'Gap (px)', 'checkmate-pdf-invoices' ),
				],
				'verticalAlign' => [
					'type'    => 'select',
					'default' => 'top',
					'label'   => __( 'Vertical Align', 'checkmate-pdf-invoices' ),
					'options' => [
						'top'    => __( 'Top', 'checkmate-pdf-invoices' ),
						'middle' => __( 'Middle', 'checkmate-pdf-invoices' ),
						'bottom' => __( 'Bottom', 'checkmate-pdf-invoices' ),
					],
				],
			],
			'default_children' => [
				[ 'type' => 'column', 'attributes' => [ 'width' => 50 ] ],
				[ 'type' => 'column', 'attributes' => [ 'width' => 50 ] ],
			],
		] );

		$this->register_block( 'column', [
			'title'       => __( 'Column', 'checkmate-pdf-invoices' ),
			'description' => __( 'Vertical section inside a row', 'checkmate-pdf-invoices' ),
			'category'    => 'layout',
			'icon'        => 'square',
			'supports'    => [ 'children' ],
			'parent'      => [ 'row' ],
			'attributes'  => [
				'width' => [
					'type'    => 'number',
					'default' => 50,
					'label'   => __( 'Width (%)', 'checkmate-pdf-invoices' ),
					'min'     => 10,
					'max'     => 100,
				],
			],
		] );

		$this->register_block( 'spacer', [
			'title'       => __( 'Spacer', 'checkmate-pdf-invoices' ),
			'description' => __( 'Vertical space between elements', 'checkmate-pdf-invoices' ),
			'category'    => 'layout',
			'icon'        => 'move-vertical',
			'attributes'  => [
				'height' => [
					'type'    => 'number',
					'default' => 20,
					'label'   => __( 'Height (px)', 'checkmate-pdf-invoices' ),
					'min'     => 5,
					'max'     => 200,
				],
			],
		] );

		$this->register_block( 'divider', [
			'title'       => __( 'Divider', 'checkmate-pdf-invoices' ),
			'description' => __( 'Horizontal line separator', 'checkmate-pdf-invoices' ),
			'category'    => 'layout',
			'icon'        => 'minus',
			'attributes'  => [
				'color' => [
					'type'    => 'color',
					'default' => '#cccccc',
					'label'   => __( 'Color', 'checkmate-pdf-invoices' ),
				],
				'thickness' => [
					'type'    => 'number',
					'default' => 1,
					'label'   => __( 'Thickness (px)', 'checkmate-pdf-invoices' ),
					'min'     => 1,
					'max'     => 10,
				],
				'style' => [
					'type'    => 'select',
					'default' => 'solid',
					'label'   => __( 'Style', 'checkmate-pdf-invoices' ),
					'options' => [
						'solid'  => __( 'Solid', 'checkmate-pdf-invoices' ),
						'dashed' => __( 'Dashed', 'checkmate-pdf-invoices' ),
						'dotted' => __( 'Dotted', 'checkmate-pdf-invoices' ),
					],
				],
			],
		] );

		// Content blocks
		$this->register_block( 'logo', [
			'title'       => __( 'Logo', 'checkmate-pdf-invoices' ),
			'description' => __( 'Company logo image', 'checkmate-pdf-invoices' ),
			'category'    => 'content',
			'icon'        => 'image',
			'attributes'  => [
				'source' => [
					'type'    => 'select',
					'default' => 'setting',
					'label'   => __( 'Source', 'checkmate-pdf-invoices' ),
					'options' => [
						'setting' => __( 'From Settings', 'checkmate-pdf-invoices' ),
						'custom'  => __( 'Custom Upload', 'checkmate-pdf-invoices' ),
					],
				],
				'customUrl' => [
					'type'    => 'image',
					'default' => '',
					'label'   => __( 'Custom Logo', 'checkmate-pdf-invoices' ),
					'condition' => [ 'source' => 'custom' ],
				],
				'maxHeight' => [
					'type'    => 'number',
					'default' => 80,
					'label'   => __( 'Max Height (px)', 'checkmate-pdf-invoices' ),
					'min'     => 20,
					'max'     => 200,
				],
				'align' => [
					'type'    => 'select',
					'default' => 'left',
					'label'   => __( 'Alignment', 'checkmate-pdf-invoices' ),
					'options' => [
						'start'  => __( 'Start', 'checkmate-pdf-invoices' ),
						'left'   => __( 'Left', 'checkmate-pdf-invoices' ),
						'center' => __( 'Center', 'checkmate-pdf-invoices' ),
						'right'  => __( 'Right', 'checkmate-pdf-invoices' ),
						'end'    => __( 'End', 'checkmate-pdf-invoices' ),
					],
				],
			],
		] );

		$this->register_block( 'text', [
			'title'       => __( 'Text', 'checkmate-pdf-invoices' ),
			'description' => __( 'Rich text content', 'checkmate-pdf-invoices' ),
			'category'    => 'content',
			'icon'        => 'type',
			'attributes'  => [
				'content' => [
					'type'    => 'richtext',
					'default' => __( 'Enter your text here...', 'checkmate-pdf-invoices' ),
					'label'   => __( 'Content', 'checkmate-pdf-invoices' ),
				],
				'fontSize' => [
					'type'    => 'number',
					'default' => 9,
					'label'   => __( 'Font Size (pt)', 'checkmate-pdf-invoices' ),
					'min'     => 6,
					'max'     => 24,
				],
				'fontWeight' => [
					'type'    => 'select',
					'default' => 'normal',
					'label'   => __( 'Font Weight', 'checkmate-pdf-invoices' ),
					'options' => [
						'normal' => __( 'Normal', 'checkmate-pdf-invoices' ),
						'bold'   => __( 'Bold', 'checkmate-pdf-invoices' ),
					],
				],
				'fontStyle' => [
					'type'    => 'select',
					'default' => 'normal',
					'label'   => __( 'Font Style', 'checkmate-pdf-invoices' ),
					'options' => [
						'normal' => __( 'Normal', 'checkmate-pdf-invoices' ),
						'italic' => __( 'Italic', 'checkmate-pdf-invoices' ),
					],
				],
				'textDecoration' => [
					'type'    => 'select',
					'default' => 'none',
					'label'   => __( 'Text Decoration', 'checkmate-pdf-invoices' ),
					'options' => [
						'none'      => __( 'None', 'checkmate-pdf-invoices' ),
						'underline' => __( 'Underline', 'checkmate-pdf-invoices' ),
						'overline' => __( 'Overline', 'checkmate-pdf-invoices' ),
						'line-through' => __( 'Line-through', 'checkmate-pdf-invoices' ),
					],
				],
				'textColor' => [
					'type'    => 'color',
					'default' => '',
					'label'   => __( 'Text Color', 'checkmate-pdf-invoices' ),
				],
				'align' => [
					'type'    => 'select',
					'default' => 'left',
					'label'   => __( 'Alignment', 'checkmate-pdf-invoices' ),
					'options' => [
						'start'  => __( 'Start', 'checkmate-pdf-invoices' ),
						'left'   => __( 'Left', 'checkmate-pdf-invoices' ),
						'center' => __( 'Center', 'checkmate-pdf-invoices' ),
						'right'  => __( 'Right', 'checkmate-pdf-invoices' ),
						'end'    => __( 'End', 'checkmate-pdf-invoices' ),
					],
				],
			],
		] );

		$this->register_block( 'heading', [
			'title'       => __( 'Heading', 'checkmate-pdf-invoices' ),
			'description' => __( 'Title or heading text', 'checkmate-pdf-invoices' ),
			'category'    => 'content',
			'icon'        => 'heading',
			'attributes'  => [
				'content' => [
					'type'    => 'text',
					'default' => __( 'Heading', 'checkmate-pdf-invoices' ),
					'label'   => __( 'Content', 'checkmate-pdf-invoices' ),
				],
				'level' => [
					'type'    => 'select',
					'default' => 'h1',
					'label'   => __( 'Level', 'checkmate-pdf-invoices' ),
					'options' => [
						'h1' => 'H1',
						'h2' => 'H2',
						'h3' => 'H3',
						'h4' => 'H4',
					],
				],
				'fontWeight' => [
					'type'    => 'select',
					'default' => 'bold',
					'label'   => __( 'Font Weight', 'checkmate-pdf-invoices' ),
					'options' => [
						'normal' => __( 'Normal', 'checkmate-pdf-invoices' ),
						'bold'   => __( 'Bold', 'checkmate-pdf-invoices' ),
					],
				],
				'fontStyle' => [
					'type'    => 'select',
					'default' => 'normal',
					'label'   => __( 'Font Style', 'checkmate-pdf-invoices' ),
					'options' => [
						'normal' => __( 'Normal', 'checkmate-pdf-invoices' ),
						'italic' => __( 'Italic', 'checkmate-pdf-invoices' ),
					],
				],
				'textDecoration' => [
					'type'    => 'select',
					'default' => 'none',
					'label'   => __( 'Text Decoration', 'checkmate-pdf-invoices' ),
					'options' => [
						'none'      => __( 'None', 'checkmate-pdf-invoices' ),
						'underline' => __( 'Underline', 'checkmate-pdf-invoices' ),
						'overline' => __( 'Overline', 'checkmate-pdf-invoices' ),
						'line-through' => __( 'Line-through', 'checkmate-pdf-invoices' ),
					],
				],
				'textColor' => [
					'type'    => 'color',
					'default' => '',
					'label'   => __( 'Text Color', 'checkmate-pdf-invoices' ),
				],
				'align' => [
					'type'    => 'select',
					'default' => 'left',
					'label'   => __( 'Alignment', 'checkmate-pdf-invoices' ),
					'options' => [
						'start'  => __( 'Start', 'checkmate-pdf-invoices' ),
						'left'   => __( 'Left', 'checkmate-pdf-invoices' ),
						'center' => __( 'Center', 'checkmate-pdf-invoices' ),
						'right'  => __( 'Right', 'checkmate-pdf-invoices' ),
						'end'    => __( 'End', 'checkmate-pdf-invoices' ),
					],
				],
			],
		] );

		$this->register_block( 'image', [
			'title'       => __( 'Image', 'checkmate-pdf-invoices' ),
			'description' => __( 'Custom image from URL or upload', 'checkmate-pdf-invoices' ),
			'category'    => 'content',
			'icon'        => 'image',
			'attributes'  => [
				'url' => [
					'type'    => 'image',
					'default' => '',
					'label'   => __( 'Image', 'checkmate-pdf-invoices' ),
				],
				'alt' => [
					'type'    => 'text',
					'default' => '',
					'label'   => __( 'Alt Text', 'checkmate-pdf-invoices' ),
				],
				'width' => [
					'type'    => 'number',
					'default' => 100,
					'label'   => __( 'Width (%)', 'checkmate-pdf-invoices' ),
					'min'     => 10,
					'max'     => 100,
				],
				'maxHeight' => [
					'type'    => 'number',
					'default' => 200,
					'label'   => __( 'Max Height (px)', 'checkmate-pdf-invoices' ),
					'min'     => 20,
					'max'     => 500,
				],
				'align' => [
					'type'    => 'select',
					'default' => 'left',
					'label'   => __( 'Alignment', 'checkmate-pdf-invoices' ),
					'options' => [
						'start'  => __( 'Start', 'checkmate-pdf-invoices' ),
						'left'   => __( 'Left', 'checkmate-pdf-invoices' ),
						'center' => __( 'Center', 'checkmate-pdf-invoices' ),
						'right'  => __( 'Right', 'checkmate-pdf-invoices' ),
						'end'    => __( 'End', 'checkmate-pdf-invoices' ),
					],
				],
			],
		] );

		// Document blocks
		$this->register_block( 'document-title', [
			'title'       => __( 'Document Title', 'checkmate-pdf-invoices' ),
			'description' => __( 'Dynamic document type title', 'checkmate-pdf-invoices' ),
			'category'    => 'document',
			'icon'        => 'file-text',
			'attributes'  => [
				'fontSize' => [
					'type'    => 'number',
					'default' => 16,
					'label'   => __( 'Font Size (pt)', 'checkmate-pdf-invoices' ),
				],
				'fontWeight' => [
					'type'    => 'select',
					'default' => 'bold',
					'label'   => __( 'Font Weight', 'checkmate-pdf-invoices' ),
					'options' => [
						'normal' => __( 'Normal', 'checkmate-pdf-invoices' ),
						'bold'   => __( 'Bold', 'checkmate-pdf-invoices' ),
					],
				],
				'textTransform' => [
					'type'    => 'select',
					'default' => 'uppercase',
					'label'   => __( 'Text Transform', 'checkmate-pdf-invoices' ),
					'options' => [
						'none'      => __( 'None', 'checkmate-pdf-invoices' ),
						'uppercase' => __( 'Uppercase', 'checkmate-pdf-invoices' ),
					],
				],
				'textColor' => [
					'type'    => 'color',
					'default' => '',
					'label'   => __( 'Text Color', 'checkmate-pdf-invoices' ),
				],
				'align' => [
					'type'    => 'select',
					'default' => 'left',
					'label'   => __( 'Alignment', 'checkmate-pdf-invoices' ),
					'options' => [
						'start'  => __( 'Start', 'checkmate-pdf-invoices' ),
						'left'   => __( 'Left', 'checkmate-pdf-invoices' ),
						'center' => __( 'Center', 'checkmate-pdf-invoices' ),
						'right'  => __( 'Right', 'checkmate-pdf-invoices' ),
						'end'    => __( 'End', 'checkmate-pdf-invoices' ),
					],
				],
			],
		] );

		$this->register_block( 'document-number', [
			'title'       => __( 'Document Number', 'checkmate-pdf-invoices' ),
			'description' => __( 'Invoice/document number', 'checkmate-pdf-invoices' ),
			'category'    => 'document',
			'icon'        => 'hash',
			'attributes'  => [
				'label' => [
					'type'    => 'text',
					'default' => __( 'Invoice #:', 'checkmate-pdf-invoices' ),
					'label'   => __( 'Label', 'checkmate-pdf-invoices' ),
				],
				'showLabel' => [
					'type'    => 'toggle',
					'default' => true,
					'label'   => __( 'Show Label', 'checkmate-pdf-invoices' ),
				],
				'labelPosition' => [
					'type'    => 'select',
					'default' => 'left',
					'label'   => __( 'Label Position', 'checkmate-pdf-invoices' ),
					'options' => [
						'left'  => __( 'Left of value', 'checkmate-pdf-invoices' ),
						'right' => __( 'Right of value', 'checkmate-pdf-invoices' ),
					],
				],
				'align' => [
					'type'    => 'select',
					'default' => 'left',
					'label'   => __( 'Text Align', 'checkmate-pdf-invoices' ),
					'options' => [
						'start'  => __( 'Start', 'checkmate-pdf-invoices' ),
						'left'   => __( 'Left', 'checkmate-pdf-invoices' ),
						'center' => __( 'Center', 'checkmate-pdf-invoices' ),
						'right'  => __( 'Right', 'checkmate-pdf-invoices' ),
						'end'    => __( 'End', 'checkmate-pdf-invoices' ),
					],
				],
				'labelFontSize' => [
					'type'    => 'number',
					'default' => 0,
					'label'   => __( 'Label Font Size (pt)', 'checkmate-pdf-invoices' ),
					'min'     => 0,
					'max'     => 48,
				],
				'labelFontWeight' => [
					'type'    => 'select',
					'default' => 'bold',
					'label'   => __( 'Label Font Weight', 'checkmate-pdf-invoices' ),
					'options' => [
						'normal' => __( 'Normal', 'checkmate-pdf-invoices' ),
						'bold'   => __( 'Bold', 'checkmate-pdf-invoices' ),
					],
				],
				'labelFontStyle' => [
					'type'    => 'select',
					'default' => 'normal',
					'label'   => __( 'Label Font Style', 'checkmate-pdf-invoices' ),
					'options' => [
						'normal' => __( 'Normal', 'checkmate-pdf-invoices' ),
						'italic' => __( 'Italic', 'checkmate-pdf-invoices' ),
					],
				],
				'labelTextDecoration' => [
					'type'    => 'select',
					'default' => 'none',
					'label'   => __( 'Label Text Decoration', 'checkmate-pdf-invoices' ),
					'options' => [
						'none'         => __( 'None', 'checkmate-pdf-invoices' ),
						'underline'    => __( 'Underline', 'checkmate-pdf-invoices' ),
						'overline'     => __( 'Overline', 'checkmate-pdf-invoices' ),
						'line-through' => __( 'Line-through', 'checkmate-pdf-invoices' ),
					],
				],
				'labelTextTransform' => [
					'type'    => 'select',
					'default' => 'none',
					'label'   => __( 'Label Text Transform', 'checkmate-pdf-invoices' ),
					'options' => [
						'none'      => __( 'None', 'checkmate-pdf-invoices' ),
						'uppercase' => __( 'Uppercase', 'checkmate-pdf-invoices' ),
						'lowercase' => __( 'Lowercase', 'checkmate-pdf-invoices' ),
						'capitalize' => __( 'Capitalize', 'checkmate-pdf-invoices' ),
					],
				],
				'labelTextColor' => [
					'type'    => 'color',
					'default' => '',
					'label'   => __( 'Label Text Color', 'checkmate-pdf-invoices' ),
				],
				'fontSize' => [
					'type'    => 'number',
					'default' => 9,
					'label'   => __( 'Font Size (pt)', 'checkmate-pdf-invoices' ),
				],
			],
		] );

		$this->register_block( 'document-date', [
			'title'       => __( 'Document Date', 'checkmate-pdf-invoices' ),
			'description' => __( 'Invoice/document date', 'checkmate-pdf-invoices' ),
			'category'    => 'document',
			'icon'        => 'calendar',
			'attributes'  => [
				'label' => [
					'type'    => 'text',
					'default' => __( 'Date:', 'checkmate-pdf-invoices' ),
					'label'   => __( 'Label', 'checkmate-pdf-invoices' ),
				],
				'showLabel' => [
					'type'    => 'toggle',
					'default' => true,
					'label'   => __( 'Show Label', 'checkmate-pdf-invoices' ),
				],
				'labelPosition' => [
					'type'    => 'select',
					'default' => 'left',
					'label'   => __( 'Label Position', 'checkmate-pdf-invoices' ),
					'options' => [
						'left'  => __( 'Left of value', 'checkmate-pdf-invoices' ),
						'right' => __( 'Right of value', 'checkmate-pdf-invoices' ),
					],
				],
				'align' => [
					'type'    => 'select',
					'default' => 'left',
					'label'   => __( 'Text Align', 'checkmate-pdf-invoices' ),
					'options' => [
						'start'  => __( 'Start', 'checkmate-pdf-invoices' ),
						'left'   => __( 'Left', 'checkmate-pdf-invoices' ),
						'center' => __( 'Center', 'checkmate-pdf-invoices' ),
						'right'  => __( 'Right', 'checkmate-pdf-invoices' ),
						'end'    => __( 'End', 'checkmate-pdf-invoices' ),
					],
				],
				'labelFontSize' => [
					'type'    => 'number',
					'default' => 0,
					'label'   => __( 'Label Font Size (pt)', 'checkmate-pdf-invoices' ),
					'min'     => 0,
					'max'     => 48,
				],
				'labelFontWeight' => [
					'type'    => 'select',
					'default' => 'bold',
					'label'   => __( 'Label Font Weight', 'checkmate-pdf-invoices' ),
					'options' => [
						'normal' => __( 'Normal', 'checkmate-pdf-invoices' ),
						'bold'   => __( 'Bold', 'checkmate-pdf-invoices' ),
					],
				],
				'labelFontStyle' => [
					'type'    => 'select',
					'default' => 'normal',
					'label'   => __( 'Label Font Style', 'checkmate-pdf-invoices' ),
					'options' => [
						'normal' => __( 'Normal', 'checkmate-pdf-invoices' ),
						'italic' => __( 'Italic', 'checkmate-pdf-invoices' ),
					],
				],
				'labelTextDecoration' => [
					'type'    => 'select',
					'default' => 'none',
					'label'   => __( 'Label Text Decoration', 'checkmate-pdf-invoices' ),
					'options' => [
						'none'         => __( 'None', 'checkmate-pdf-invoices' ),
						'underline'    => __( 'Underline', 'checkmate-pdf-invoices' ),
						'overline'     => __( 'Overline', 'checkmate-pdf-invoices' ),
						'line-through' => __( 'Line-through', 'checkmate-pdf-invoices' ),
					],
				],
				'labelTextTransform' => [
					'type'    => 'select',
					'default' => 'none',
					'label'   => __( 'Label Text Transform', 'checkmate-pdf-invoices' ),
					'options' => [
						'none'      => __( 'None', 'checkmate-pdf-invoices' ),
						'uppercase' => __( 'Uppercase', 'checkmate-pdf-invoices' ),
						'lowercase' => __( 'Lowercase', 'checkmate-pdf-invoices' ),
						'capitalize' => __( 'Capitalize', 'checkmate-pdf-invoices' ),
					],
				],
				'labelTextColor' => [
					'type'    => 'color',
					'default' => '',
					'label'   => __( 'Label Text Color', 'checkmate-pdf-invoices' ),
				],
				'format' => [
					'type'    => 'text',
					'default' => 'F j, Y',
					'label'   => __( 'Date Format', 'checkmate-pdf-invoices' ),
				],
				'fontSize' => [
					'type'    => 'number',
					'default' => 9,
					'label'   => __( 'Font Size (pt)', 'checkmate-pdf-invoices' ),
				],
			],
		] );

		$this->register_block( 'shop-info', [
			'title'       => __( 'Shop Info', 'checkmate-pdf-invoices' ),
			'description' => __( 'Company name, address, contact', 'checkmate-pdf-invoices' ),
			'category'    => 'document',
			'icon'        => 'building',
			'attributes'  => [
				'showName' => [
					'type'    => 'toggle',
					'default' => true,
					'label'   => __( 'Show Name', 'checkmate-pdf-invoices' ),
				],
				'showAddress' => [
					'type'    => 'toggle',
					'default' => true,
					'label'   => __( 'Show Address', 'checkmate-pdf-invoices' ),
				],
				'showPhone' => [
					'type'    => 'toggle',
					'default' => true,
					'label'   => __( 'Show Phone', 'checkmate-pdf-invoices' ),
				],
				'showEmail' => [
					'type'    => 'toggle',
					'default' => true,
					'label'   => __( 'Show Email', 'checkmate-pdf-invoices' ),
				],
				'fontSize' => [
					'type'    => 'number',
					'default' => 9,
					'label'   => __( 'Font Size (pt)', 'checkmate-pdf-invoices' ),
				],
				'align' => [
					'type'    => 'select',
					'default' => 'left',
					'label'   => __( 'Alignment', 'checkmate-pdf-invoices' ),
					'options' => [
						'start'  => __( 'Start', 'checkmate-pdf-invoices' ),
						'left'   => __( 'Left', 'checkmate-pdf-invoices' ),
						'center' => __( 'Center', 'checkmate-pdf-invoices' ),
						'right'  => __( 'Right', 'checkmate-pdf-invoices' ),
						'end'    => __( 'End', 'checkmate-pdf-invoices' ),
					],
				],
			],
		] );

		// Order data blocks
		$this->register_block( 'billing-address', [
			'title'       => __( 'Billing Address', 'checkmate-pdf-invoices' ),
			'description' => __( 'Customer billing address', 'checkmate-pdf-invoices' ),
			'category'    => 'order',
			'icon'        => 'map-pin',
			'attributes'  => [
				'title' => [
					'type'    => 'text',
					'default' => __( 'Bill To:', 'checkmate-pdf-invoices' ),
					'label'   => __( 'Title', 'checkmate-pdf-invoices' ),
				],
				'showTitle' => [
					'type'    => 'toggle',
					'default' => true,
					'label'   => __( 'Show Title', 'checkmate-pdf-invoices' ),
				],
				'titleFontSize' => [
					'type'    => 'number',
					'default' => 0,
					'label'   => __( 'Title Font Size (pt)', 'checkmate-pdf-invoices' ),
					'min'     => 0,
					'max'     => 48,
				],
				'titleFontWeight' => [
					'type'    => 'select',
					'default' => 'bold',
					'label'   => __( 'Title Font Weight', 'checkmate-pdf-invoices' ),
					'options' => [
						'normal' => __( 'Normal', 'checkmate-pdf-invoices' ),
						'bold'   => __( 'Bold', 'checkmate-pdf-invoices' ),
					],
				],
				'titleFontStyle' => [
					'type'    => 'select',
					'default' => 'normal',
					'label'   => __( 'Title Font Style', 'checkmate-pdf-invoices' ),
					'options' => [
						'normal' => __( 'Normal', 'checkmate-pdf-invoices' ),
						'italic' => __( 'Italic', 'checkmate-pdf-invoices' ),
					],
				],
				'titleTextDecoration' => [
					'type'    => 'select',
					'default' => 'none',
					'label'   => __( 'Title Text Decoration', 'checkmate-pdf-invoices' ),
					'options' => [
						'none'         => __( 'None', 'checkmate-pdf-invoices' ),
						'underline'    => __( 'Underline', 'checkmate-pdf-invoices' ),
						'overline'     => __( 'Overline', 'checkmate-pdf-invoices' ),
						'line-through' => __( 'Line-through', 'checkmate-pdf-invoices' ),
					],
				],
				'titleTextTransform' => [
					'type'    => 'select',
					'default' => 'none',
					'label'   => __( 'Title Text Transform', 'checkmate-pdf-invoices' ),
					'options' => [
						'none'      => __( 'None', 'checkmate-pdf-invoices' ),
						'uppercase' => __( 'Uppercase', 'checkmate-pdf-invoices' ),
						'lowercase' => __( 'Lowercase', 'checkmate-pdf-invoices' ),
						'capitalize' => __( 'Capitalize', 'checkmate-pdf-invoices' ),
					],
				],
				'titleTextColor' => [
					'type'    => 'color',
					'default' => '',
					'label'   => __( 'Title Text Color', 'checkmate-pdf-invoices' ),
				],
				'showEmail' => [
					'type'    => 'toggle',
					'default' => false,
					'label'   => __( 'Show Email', 'checkmate-pdf-invoices' ),
				],
				'showPhone' => [
					'type'    => 'toggle',
					'default' => false,
					'label'   => __( 'Show Phone', 'checkmate-pdf-invoices' ),
				],
				'fontSize' => [
					'type'    => 'number',
					'default' => 9,
					'label'   => __( 'Font Size (pt)', 'checkmate-pdf-invoices' ),
				],
				'align' => [
					'type'    => 'select',
					'default' => 'left',
					'label'   => __( 'Text Align', 'checkmate-pdf-invoices' ),
					'options' => [
						'start'  => __( 'Start', 'checkmate-pdf-invoices' ),
						'left'   => __( 'Left', 'checkmate-pdf-invoices' ),
						'center' => __( 'Center', 'checkmate-pdf-invoices' ),
						'right'  => __( 'Right', 'checkmate-pdf-invoices' ),
						'end'    => __( 'End', 'checkmate-pdf-invoices' ),
					],
				],
			],
		] );

		$this->register_block( 'shipping-address', [
			'title'       => __( 'Shipping Address', 'checkmate-pdf-invoices' ),
			'description' => __( 'Customer shipping address', 'checkmate-pdf-invoices' ),
			'category'    => 'order',
			'icon'        => 'truck',
			'attributes'  => [
				'title' => [
					'type'    => 'text',
					'default' => __( 'Ship To:', 'checkmate-pdf-invoices' ),
					'label'   => __( 'Title', 'checkmate-pdf-invoices' ),
				],
				'showTitle' => [
					'type'    => 'toggle',
					'default' => true,
					'label'   => __( 'Show Title', 'checkmate-pdf-invoices' ),
				],
				'titleFontSize' => [
					'type'    => 'number',
					'default' => 0,
					'label'   => __( 'Title Font Size (pt)', 'checkmate-pdf-invoices' ),
					'min'     => 0,
					'max'     => 48,
				],
				'titleFontWeight' => [
					'type'    => 'select',
					'default' => 'bold',
					'label'   => __( 'Title Font Weight', 'checkmate-pdf-invoices' ),
					'options' => [
						'normal' => __( 'Normal', 'checkmate-pdf-invoices' ),
						'bold'   => __( 'Bold', 'checkmate-pdf-invoices' ),
					],
				],
				'titleFontStyle' => [
					'type'    => 'select',
					'default' => 'normal',
					'label'   => __( 'Title Font Style', 'checkmate-pdf-invoices' ),
					'options' => [
						'normal' => __( 'Normal', 'checkmate-pdf-invoices' ),
						'italic' => __( 'Italic', 'checkmate-pdf-invoices' ),
					],
				],
				'titleTextDecoration' => [
					'type'    => 'select',
					'default' => 'none',
					'label'   => __( 'Title Text Decoration', 'checkmate-pdf-invoices' ),
					'options' => [
						'none'         => __( 'None', 'checkmate-pdf-invoices' ),
						'underline'    => __( 'Underline', 'checkmate-pdf-invoices' ),
						'overline'     => __( 'Overline', 'checkmate-pdf-invoices' ),
						'line-through' => __( 'Line-through', 'checkmate-pdf-invoices' ),
					],
				],
				'titleTextTransform' => [
					'type'    => 'select',
					'default' => 'none',
					'label'   => __( 'Title Text Transform', 'checkmate-pdf-invoices' ),
					'options' => [
						'none'      => __( 'None', 'checkmate-pdf-invoices' ),
						'uppercase' => __( 'Uppercase', 'checkmate-pdf-invoices' ),
						'lowercase' => __( 'Lowercase', 'checkmate-pdf-invoices' ),
						'capitalize' => __( 'Capitalize', 'checkmate-pdf-invoices' ),
					],
				],
				'titleTextColor' => [
					'type'    => 'color',
					'default' => '',
					'label'   => __( 'Title Text Color', 'checkmate-pdf-invoices' ),
				],
				'showPhone' => [
					'type'    => 'toggle',
					'default' => false,
					'label'   => __( 'Show Phone', 'checkmate-pdf-invoices' ),
				],
				'fontSize' => [
					'type'    => 'number',
					'default' => 9,
					'label'   => __( 'Font Size (pt)', 'checkmate-pdf-invoices' ),
				],
				'align' => [
					'type'    => 'select',
					'default' => 'left',
					'label'   => __( 'Text Align', 'checkmate-pdf-invoices' ),
					'options' => [
						'start'  => __( 'Start', 'checkmate-pdf-invoices' ),
						'left'   => __( 'Left', 'checkmate-pdf-invoices' ),
						'center' => __( 'Center', 'checkmate-pdf-invoices' ),
						'right'  => __( 'Right', 'checkmate-pdf-invoices' ),
						'end'    => __( 'End', 'checkmate-pdf-invoices' ),
					],
				],
			],
		] );

		$this->register_block( 'order-number', [
			'title'       => __( 'Order Number', 'checkmate-pdf-invoices' ),
			'description' => __( 'WooCommerce order number', 'checkmate-pdf-invoices' ),
			'category'    => 'order',
			'icon'        => 'shopping-bag',
			'attributes'  => [
				'label' => [
					'type'    => 'text',
					'default' => __( 'Order #:', 'checkmate-pdf-invoices' ),
					'label'   => __( 'Label', 'checkmate-pdf-invoices' ),
				],
				'showLabel' => [
					'type'    => 'toggle',
					'default' => true,
					'label'   => __( 'Show Label', 'checkmate-pdf-invoices' ),
				],
				'labelPosition' => [
					'type'    => 'select',
					'default' => 'left',
					'label'   => __( 'Label Position', 'checkmate-pdf-invoices' ),
					'options' => [
						'left'  => __( 'Left of value', 'checkmate-pdf-invoices' ),
						'right' => __( 'Right of value', 'checkmate-pdf-invoices' ),
					],
				],
				'align' => [
					'type'    => 'select',
					'default' => 'left',
					'label'   => __( 'Text Align', 'checkmate-pdf-invoices' ),
					'options' => [
						'start'  => __( 'Start', 'checkmate-pdf-invoices' ),
						'left'   => __( 'Left', 'checkmate-pdf-invoices' ),
						'center' => __( 'Center', 'checkmate-pdf-invoices' ),
						'right'  => __( 'Right', 'checkmate-pdf-invoices' ),
						'end'    => __( 'End', 'checkmate-pdf-invoices' ),
					],
				],
				'labelFontSize' => [
					'type'    => 'number',
					'default' => 0,
					'label'   => __( 'Label Font Size (pt)', 'checkmate-pdf-invoices' ),
					'min'     => 0,
					'max'     => 48,
				],
				'labelFontWeight' => [
					'type'    => 'select',
					'default' => 'bold',
					'label'   => __( 'Label Font Weight', 'checkmate-pdf-invoices' ),
					'options' => [
						'normal' => __( 'Normal', 'checkmate-pdf-invoices' ),
						'bold'   => __( 'Bold', 'checkmate-pdf-invoices' ),
					],
				],
				'labelFontStyle' => [
					'type'    => 'select',
					'default' => 'normal',
					'label'   => __( 'Label Font Style', 'checkmate-pdf-invoices' ),
					'options' => [
						'normal' => __( 'Normal', 'checkmate-pdf-invoices' ),
						'italic' => __( 'Italic', 'checkmate-pdf-invoices' ),
					],
				],
				'labelTextDecoration' => [
					'type'    => 'select',
					'default' => 'none',
					'label'   => __( 'Label Text Decoration', 'checkmate-pdf-invoices' ),
					'options' => [
						'none'         => __( 'None', 'checkmate-pdf-invoices' ),
						'underline'    => __( 'Underline', 'checkmate-pdf-invoices' ),
						'overline'     => __( 'Overline', 'checkmate-pdf-invoices' ),
						'line-through' => __( 'Line-through', 'checkmate-pdf-invoices' ),
					],
				],
				'labelTextTransform' => [
					'type'    => 'select',
					'default' => 'none',
					'label'   => __( 'Label Text Transform', 'checkmate-pdf-invoices' ),
					'options' => [
						'none'      => __( 'None', 'checkmate-pdf-invoices' ),
						'uppercase' => __( 'Uppercase', 'checkmate-pdf-invoices' ),
						'lowercase' => __( 'Lowercase', 'checkmate-pdf-invoices' ),
						'capitalize' => __( 'Capitalize', 'checkmate-pdf-invoices' ),
					],
				],
				'labelTextColor' => [
					'type'    => 'color',
					'default' => '',
					'label'   => __( 'Label Text Color', 'checkmate-pdf-invoices' ),
				],
				'fontSize' => [
					'type'    => 'number',
					'default' => 9,
					'label'   => __( 'Font Size (pt)', 'checkmate-pdf-invoices' ),
				],
			],
		] );

		$this->register_block( 'order-date', [
			'title'       => __( 'Order Date', 'checkmate-pdf-invoices' ),
			'description' => __( 'WooCommerce order date', 'checkmate-pdf-invoices' ),
			'category'    => 'order',
			'icon'        => 'calendar',
			'attributes'  => [
				'label' => [
					'type'    => 'text',
					'default' => __( 'Order Date:', 'checkmate-pdf-invoices' ),
					'label'   => __( 'Label', 'checkmate-pdf-invoices' ),
				],
				'showLabel' => [
					'type'    => 'toggle',
					'default' => true,
					'label'   => __( 'Show Label', 'checkmate-pdf-invoices' ),
				],
				'labelPosition' => [
					'type'    => 'select',
					'default' => 'left',
					'label'   => __( 'Label Position', 'checkmate-pdf-invoices' ),
					'options' => [
						'left'  => __( 'Left of value', 'checkmate-pdf-invoices' ),
						'right' => __( 'Right of value', 'checkmate-pdf-invoices' ),
					],
				],
				'align' => [
					'type'    => 'select',
					'default' => 'left',
					'label'   => __( 'Text Align', 'checkmate-pdf-invoices' ),
					'options' => [
						'start'  => __( 'Start', 'checkmate-pdf-invoices' ),
						'left'   => __( 'Left', 'checkmate-pdf-invoices' ),
						'center' => __( 'Center', 'checkmate-pdf-invoices' ),
						'right'  => __( 'Right', 'checkmate-pdf-invoices' ),
						'end'    => __( 'End', 'checkmate-pdf-invoices' ),
					],
				],
				'labelFontSize' => [
					'type'    => 'number',
					'default' => 0,
					'label'   => __( 'Label Font Size (pt)', 'checkmate-pdf-invoices' ),
					'min'     => 0,
					'max'     => 48,
				],
				'labelFontWeight' => [
					'type'    => 'select',
					'default' => 'bold',
					'label'   => __( 'Label Font Weight', 'checkmate-pdf-invoices' ),
					'options' => [
						'normal' => __( 'Normal', 'checkmate-pdf-invoices' ),
						'bold'   => __( 'Bold', 'checkmate-pdf-invoices' ),
					],
				],
				'labelFontStyle' => [
					'type'    => 'select',
					'default' => 'normal',
					'label'   => __( 'Label Font Style', 'checkmate-pdf-invoices' ),
					'options' => [
						'normal' => __( 'Normal', 'checkmate-pdf-invoices' ),
						'italic' => __( 'Italic', 'checkmate-pdf-invoices' ),
					],
				],
				'labelTextDecoration' => [
					'type'    => 'select',
					'default' => 'none',
					'label'   => __( 'Label Text Decoration', 'checkmate-pdf-invoices' ),
					'options' => [
						'none'         => __( 'None', 'checkmate-pdf-invoices' ),
						'underline'    => __( 'Underline', 'checkmate-pdf-invoices' ),
						'overline'     => __( 'Overline', 'checkmate-pdf-invoices' ),
						'line-through' => __( 'Line-through', 'checkmate-pdf-invoices' ),
					],
				],
				'labelTextTransform' => [
					'type'    => 'select',
					'default' => 'none',
					'label'   => __( 'Label Text Transform', 'checkmate-pdf-invoices' ),
					'options' => [
						'none'      => __( 'None', 'checkmate-pdf-invoices' ),
						'uppercase' => __( 'Uppercase', 'checkmate-pdf-invoices' ),
						'lowercase' => __( 'Lowercase', 'checkmate-pdf-invoices' ),
						'capitalize' => __( 'Capitalize', 'checkmate-pdf-invoices' ),
					],
				],
				'labelTextColor' => [
					'type'    => 'color',
					'default' => '',
					'label'   => __( 'Label Text Color', 'checkmate-pdf-invoices' ),
				],
				'format' => [
					'type'    => 'text',
					'default' => 'F j, Y',
					'label'   => __( 'Date Format', 'checkmate-pdf-invoices' ),
				],
				'fontSize' => [
					'type'    => 'number',
					'default' => 9,
					'label'   => __( 'Font Size (pt)', 'checkmate-pdf-invoices' ),
				],
			],
		] );

		$this->register_block( 'payment-method', [
			'title'       => __( 'Payment Method', 'checkmate-pdf-invoices' ),
			'description' => __( 'Order payment method', 'checkmate-pdf-invoices' ),
			'category'    => 'order',
			'icon'        => 'credit-card',
			'attributes'  => [
				'label' => [
					'type'    => 'text',
					'default' => __( 'Payment Method:', 'checkmate-pdf-invoices' ),
					'label'   => __( 'Label', 'checkmate-pdf-invoices' ),
				],
				'showLabel' => [
					'type'    => 'toggle',
					'default' => true,
					'label'   => __( 'Show Label', 'checkmate-pdf-invoices' ),
				],
				'labelPosition' => [
					'type'    => 'select',
					'default' => 'left',
					'label'   => __( 'Label Position', 'checkmate-pdf-invoices' ),
					'options' => [
						'left'  => __( 'Left of value', 'checkmate-pdf-invoices' ),
						'right' => __( 'Right of value', 'checkmate-pdf-invoices' ),
					],
				],
				'align' => [
					'type'    => 'select',
					'default' => 'left',
					'label'   => __( 'Text Align', 'checkmate-pdf-invoices' ),
					'options' => [
						'start'  => __( 'Start', 'checkmate-pdf-invoices' ),
						'left'   => __( 'Left', 'checkmate-pdf-invoices' ),
						'center' => __( 'Center', 'checkmate-pdf-invoices' ),
						'right'  => __( 'Right', 'checkmate-pdf-invoices' ),
						'end'    => __( 'End', 'checkmate-pdf-invoices' ),
					],
				],
				'labelFontSize' => [
					'type'    => 'number',
					'default' => 0,
					'label'   => __( 'Label Font Size (pt)', 'checkmate-pdf-invoices' ),
					'min'     => 0,
					'max'     => 48,
				],
				'labelFontWeight' => [
					'type'    => 'select',
					'default' => 'bold',
					'label'   => __( 'Label Font Weight', 'checkmate-pdf-invoices' ),
					'options' => [
						'normal' => __( 'Normal', 'checkmate-pdf-invoices' ),
						'bold'   => __( 'Bold', 'checkmate-pdf-invoices' ),
					],
				],
				'labelFontStyle' => [
					'type'    => 'select',
					'default' => 'normal',
					'label'   => __( 'Label Font Style', 'checkmate-pdf-invoices' ),
					'options' => [
						'normal' => __( 'Normal', 'checkmate-pdf-invoices' ),
						'italic' => __( 'Italic', 'checkmate-pdf-invoices' ),
					],
				],
				'labelTextDecoration' => [
					'type'    => 'select',
					'default' => 'none',
					'label'   => __( 'Label Text Decoration', 'checkmate-pdf-invoices' ),
					'options' => [
						'none'         => __( 'None', 'checkmate-pdf-invoices' ),
						'underline'    => __( 'Underline', 'checkmate-pdf-invoices' ),
						'overline'     => __( 'Overline', 'checkmate-pdf-invoices' ),
						'line-through' => __( 'Line-through', 'checkmate-pdf-invoices' ),
					],
				],
				'labelTextTransform' => [
					'type'    => 'select',
					'default' => 'none',
					'label'   => __( 'Label Text Transform', 'checkmate-pdf-invoices' ),
					'options' => [
						'none'      => __( 'None', 'checkmate-pdf-invoices' ),
						'uppercase' => __( 'Uppercase', 'checkmate-pdf-invoices' ),
						'lowercase' => __( 'Lowercase', 'checkmate-pdf-invoices' ),
						'capitalize' => __( 'Capitalize', 'checkmate-pdf-invoices' ),
					],
				],
				'labelTextColor' => [
					'type'    => 'color',
					'default' => '',
					'label'   => __( 'Label Text Color', 'checkmate-pdf-invoices' ),
				],
				'fontSize' => [
					'type'    => 'number',
					'default' => 9,
					'label'   => __( 'Font Size (pt)', 'checkmate-pdf-invoices' ),
				],
			],
		] );

		$this->register_block( 'shipping-method', [
			'title'       => __( 'Shipping Method', 'checkmate-pdf-invoices' ),
			'description' => __( 'Order shipping method', 'checkmate-pdf-invoices' ),
			'category'    => 'order',
			'icon'        => 'package',
			'attributes'  => [
				'label' => [
					'type'    => 'text',
					'default' => __( 'Shipping Method:', 'checkmate-pdf-invoices' ),
					'label'   => __( 'Label', 'checkmate-pdf-invoices' ),
				],
				'showLabel' => [
					'type'    => 'toggle',
					'default' => true,
					'label'   => __( 'Show Label', 'checkmate-pdf-invoices' ),
				],
				'labelPosition' => [
					'type'    => 'select',
					'default' => 'left',
					'label'   => __( 'Label Position', 'checkmate-pdf-invoices' ),
					'options' => [
						'left'  => __( 'Left of value', 'checkmate-pdf-invoices' ),
						'right' => __( 'Right of value', 'checkmate-pdf-invoices' ),
					],
				],
				'align' => [
					'type'    => 'select',
					'default' => 'left',
					'label'   => __( 'Text Align', 'checkmate-pdf-invoices' ),
					'options' => [
						'start'  => __( 'Start', 'checkmate-pdf-invoices' ),
						'left'   => __( 'Left', 'checkmate-pdf-invoices' ),
						'center' => __( 'Center', 'checkmate-pdf-invoices' ),
						'right'  => __( 'Right', 'checkmate-pdf-invoices' ),
						'end'    => __( 'End', 'checkmate-pdf-invoices' ),
					],
				],
				'labelFontSize' => [
					'type'    => 'number',
					'default' => 0,
					'label'   => __( 'Label Font Size (pt)', 'checkmate-pdf-invoices' ),
					'min'     => 0,
					'max'     => 48,
				],
				'labelFontWeight' => [
					'type'    => 'select',
					'default' => 'bold',
					'label'   => __( 'Label Font Weight', 'checkmate-pdf-invoices' ),
					'options' => [
						'normal' => __( 'Normal', 'checkmate-pdf-invoices' ),
						'bold'   => __( 'Bold', 'checkmate-pdf-invoices' ),
					],
				],
				'labelFontStyle' => [
					'type'    => 'select',
					'default' => 'normal',
					'label'   => __( 'Label Font Style', 'checkmate-pdf-invoices' ),
					'options' => [
						'normal' => __( 'Normal', 'checkmate-pdf-invoices' ),
						'italic' => __( 'Italic', 'checkmate-pdf-invoices' ),
					],
				],
				'labelTextDecoration' => [
					'type'    => 'select',
					'default' => 'none',
					'label'   => __( 'Label Text Decoration', 'checkmate-pdf-invoices' ),
					'options' => [
						'none'         => __( 'None', 'checkmate-pdf-invoices' ),
						'underline'    => __( 'Underline', 'checkmate-pdf-invoices' ),
						'overline'     => __( 'Overline', 'checkmate-pdf-invoices' ),
						'line-through' => __( 'Line-through', 'checkmate-pdf-invoices' ),
					],
				],
				'labelTextTransform' => [
					'type'    => 'select',
					'default' => 'none',
					'label'   => __( 'Label Text Transform', 'checkmate-pdf-invoices' ),
					'options' => [
						'none'      => __( 'None', 'checkmate-pdf-invoices' ),
						'uppercase' => __( 'Uppercase', 'checkmate-pdf-invoices' ),
						'lowercase' => __( 'Lowercase', 'checkmate-pdf-invoices' ),
						'capitalize' => __( 'Capitalize', 'checkmate-pdf-invoices' ),
					],
				],
				'labelTextColor' => [
					'type'    => 'color',
					'default' => '',
					'label'   => __( 'Label Text Color', 'checkmate-pdf-invoices' ),
				],
				'fontSize' => [
					'type'    => 'number',
					'default' => 9,
					'label'   => __( 'Font Size (pt)', 'checkmate-pdf-invoices' ),
				],
			],
		] );

		$this->register_block( 'customer-note', [
			'title'       => __( 'Customer Note', 'checkmate-pdf-invoices' ),
			'description' => __( 'Note left by customer', 'checkmate-pdf-invoices' ),
			'category'    => 'order',
			'icon'        => 'message-square',
			'attributes'  => [
				'title' => [
					'type'    => 'text',
					'default' => __( 'Customer Note:', 'checkmate-pdf-invoices' ),
					'label'   => __( 'Title', 'checkmate-pdf-invoices' ),
				],
				'showTitle' => [
					'type'    => 'toggle',
					'default' => true,
					'label'   => __( 'Show Title', 'checkmate-pdf-invoices' ),
				],
				'titleFontSize' => [
					'type'    => 'number',
					'default' => 0,
					'label'   => __( 'Title Font Size (pt)', 'checkmate-pdf-invoices' ),
					'min'     => 0,
					'max'     => 48,
				],
				'titleFontWeight' => [
					'type'    => 'select',
					'default' => 'bold',
					'label'   => __( 'Title Font Weight', 'checkmate-pdf-invoices' ),
					'options' => [
						'normal' => __( 'Normal', 'checkmate-pdf-invoices' ),
						'bold'   => __( 'Bold', 'checkmate-pdf-invoices' ),
					],
				],
				'titleFontStyle' => [
					'type'    => 'select',
					'default' => 'normal',
					'label'   => __( 'Title Font Style', 'checkmate-pdf-invoices' ),
					'options' => [
						'normal' => __( 'Normal', 'checkmate-pdf-invoices' ),
						'italic' => __( 'Italic', 'checkmate-pdf-invoices' ),
					],
				],
				'titleTextDecoration' => [
					'type'    => 'select',
					'default' => 'none',
					'label'   => __( 'Title Text Decoration', 'checkmate-pdf-invoices' ),
					'options' => [
						'none'         => __( 'None', 'checkmate-pdf-invoices' ),
						'underline'    => __( 'Underline', 'checkmate-pdf-invoices' ),
						'overline'     => __( 'Overline', 'checkmate-pdf-invoices' ),
						'line-through' => __( 'Line-through', 'checkmate-pdf-invoices' ),
					],
				],
				'titleTextTransform' => [
					'type'    => 'select',
					'default' => 'none',
					'label'   => __( 'Title Text Transform', 'checkmate-pdf-invoices' ),
					'options' => [
						'none'      => __( 'None', 'checkmate-pdf-invoices' ),
						'uppercase' => __( 'Uppercase', 'checkmate-pdf-invoices' ),
						'lowercase' => __( 'Lowercase', 'checkmate-pdf-invoices' ),
						'capitalize' => __( 'Capitalize', 'checkmate-pdf-invoices' ),
					],
				],
				'titleTextColor' => [
					'type'    => 'color',
					'default' => '',
					'label'   => __( 'Title Text Color', 'checkmate-pdf-invoices' ),
				],
				'hideIfEmpty' => [
					'type'    => 'toggle',
					'default' => true,
					'label'   => __( 'Hide if empty', 'checkmate-pdf-invoices' ),
				],
				'fontSize' => [
					'type'    => 'number',
					'default' => 9,
					'label'   => __( 'Font Size (pt)', 'checkmate-pdf-invoices' ),
				],
				'align' => [
					'type'    => 'select',
					'default' => 'left',
					'label'   => __( 'Text Align', 'checkmate-pdf-invoices' ),
					'options' => [
						'start'  => __( 'Start', 'checkmate-pdf-invoices' ),
						'left'   => __( 'Left', 'checkmate-pdf-invoices' ),
						'center' => __( 'Center', 'checkmate-pdf-invoices' ),
						'right'  => __( 'Right', 'checkmate-pdf-invoices' ),
						'end'    => __( 'End', 'checkmate-pdf-invoices' ),
					],
				],
			],
		] );

		// Table blocks
		$this->register_block( 'items-table', [
			'title'       => __( 'Items Table', 'checkmate-pdf-invoices' ),
			'description' => __( 'Order items with configurable columns', 'checkmate-pdf-invoices' ),
			'category'    => 'tables',
			'icon'        => 'table',
			'attributes'  => [
				'columns' => [
					'type'    => 'columns',
					'default' => [ 'product', 'quantity', 'price' ],
					'label'   => __( 'Columns', 'checkmate-pdf-invoices' ),
					'options' => [
						'product'    => __( 'Product', 'checkmate-pdf-invoices' ),
						'sku'        => __( 'SKU', 'checkmate-pdf-invoices' ),
						'quantity'   => __( 'Quantity', 'checkmate-pdf-invoices' ),
						'price'      => __( 'Unit Price', 'checkmate-pdf-invoices' ),
						'total'      => __( 'Total', 'checkmate-pdf-invoices' ),
						'weight'     => __( 'Weight', 'checkmate-pdf-invoices' ),
						'tax'        => __( 'Tax', 'checkmate-pdf-invoices' ),
					],
				],
				'showHeader' => [
					'type'    => 'toggle',
					'default' => true,
					'label'   => __( 'Show Header', 'checkmate-pdf-invoices' ),
				],
				'headerBackground' => [
					'type'    => 'color',
					'default' => '#000000',
					'label'   => __( 'Header Background', 'checkmate-pdf-invoices' ),
				],
				'headerColor' => [
					'type'    => 'color',
					'default' => '#ffffff',
					'label'   => __( 'Header Text Color', 'checkmate-pdf-invoices' ),
				],
				'headerProductLabel' => [
					'type'    => 'text',
					'default' => __( 'Product', 'checkmate-pdf-invoices' ),
					'label'   => __( 'Header Label: Product', 'checkmate-pdf-invoices' ),
				],
				'headerSkuLabel' => [
					'type'    => 'text',
					'default' => __( 'SKU', 'checkmate-pdf-invoices' ),
					'label'   => __( 'Header Label: SKU', 'checkmate-pdf-invoices' ),
				],
				'headerQuantityLabel' => [
					'type'    => 'text',
					'default' => __( 'Qty', 'checkmate-pdf-invoices' ),
					'label'   => __( 'Header Label: Quantity', 'checkmate-pdf-invoices' ),
				],
				'headerPriceLabel' => [
					'type'    => 'text',
					'default' => __( 'Price', 'checkmate-pdf-invoices' ),
					'label'   => __( 'Header Label: Unit Price', 'checkmate-pdf-invoices' ),
				],
				'headerTotalLabel' => [
					'type'    => 'text',
					'default' => __( 'Total', 'checkmate-pdf-invoices' ),
					'label'   => __( 'Header Label: Total', 'checkmate-pdf-invoices' ),
				],
				'headerWeightLabel' => [
					'type'    => 'text',
					'default' => __( 'Weight', 'checkmate-pdf-invoices' ),
					'label'   => __( 'Header Label: Weight', 'checkmate-pdf-invoices' ),
				],
				'headerTaxLabel' => [
					'type'    => 'text',
					'default' => __( 'Tax', 'checkmate-pdf-invoices' ),
					'label'   => __( 'Header Label: Tax', 'checkmate-pdf-invoices' ),
				],
				'showSku' => [
					'type'    => 'toggle',
					'default' => true,
					'label'   => __( 'Show SKU in product', 'checkmate-pdf-invoices' ),
				],
				'showMeta' => [
					'type'    => 'toggle',
					'default' => true,
					'label'   => __( 'Show item meta', 'checkmate-pdf-invoices' ),
				],
				'borderColor' => [
					'type'    => 'color',
					'default' => '#cccccc',
					'label'   => __( 'Border Color', 'checkmate-pdf-invoices' ),
				],
				'fontSize' => [
					'type'    => 'number',
					'default' => 9,
					'label'   => __( 'Font Size (pt)', 'checkmate-pdf-invoices' ),
				],
				'textAlign' => [
					'type'    => 'select',
					'default' => 'left',
					'label'   => __( 'Text Align', 'checkmate-pdf-invoices' ),
					'options' => [
						'start'  => __( 'Start', 'checkmate-pdf-invoices' ),
						'left'   => __( 'Left', 'checkmate-pdf-invoices' ),
						'center' => __( 'Center', 'checkmate-pdf-invoices' ),
						'right'  => __( 'Right', 'checkmate-pdf-invoices' ),
						'end'    => __( 'End', 'checkmate-pdf-invoices' ),
					],
				],
			],
		] );

		$this->register_block( 'totals-table', [
			'title'       => __( 'Totals Table', 'checkmate-pdf-invoices' ),
			'description' => __( 'Order totals summary', 'checkmate-pdf-invoices' ),
			'category'    => 'tables',
			'icon'        => 'calculator',
			'attributes'  => [
				'discountTextColor' => [
					'type'    => 'color',
					'default' => '#c00',
					'label'   => __( 'Discount Value Color', 'checkmate-pdf-invoices' ),
				],
				'labelPosition' => [
					'type'    => 'select',
					'default' => 'left',
					'label'   => __( 'Label Position', 'checkmate-pdf-invoices' ),
					'options' => [
						'left'  => __( 'Left of value', 'checkmate-pdf-invoices' ),
						'right' => __( 'Right of value', 'checkmate-pdf-invoices' ),
					],
				],
				'labelSubtotal' => [
					'type'    => 'text',
					'default' => __( 'Subtotal:', 'checkmate-pdf-invoices' ),
					'label'   => __( 'Label: Subtotal', 'checkmate-pdf-invoices' ),
				],
				'labelShipping' => [
					'type'    => 'text',
					'default' => __( 'Shipping:', 'checkmate-pdf-invoices' ),
					'label'   => __( 'Label: Shipping', 'checkmate-pdf-invoices' ),
				],
				'labelDiscount' => [
					'type'    => 'text',
					'default' => __( 'Discount:', 'checkmate-pdf-invoices' ),
					'label'   => __( 'Label: Discount', 'checkmate-pdf-invoices' ),
				],
				'labelTax' => [
					'type'    => 'text',
					'default' => __( 'Tax:', 'checkmate-pdf-invoices' ),
					'label'   => __( 'Label: Tax', 'checkmate-pdf-invoices' ),
				],
				'labelTotal' => [
					'type'    => 'text',
					'default' => __( 'Total:', 'checkmate-pdf-invoices' ),
					'label'   => __( 'Label: Total', 'checkmate-pdf-invoices' ),
				],
				'labelFontSize' => [
					'type'    => 'number',
					'default' => 0,
					'label'   => __( 'Labels Font Size (pt)', 'checkmate-pdf-invoices' ),
					'min'     => 0,
					'max'     => 48,
				],
				'labelFontWeight' => [
					'type'    => 'select',
					'default' => 'normal',
					'label'   => __( 'Labels Font Weight', 'checkmate-pdf-invoices' ),
					'options' => [
						'normal' => __( 'Normal', 'checkmate-pdf-invoices' ),
						'bold'   => __( 'Bold', 'checkmate-pdf-invoices' ),
					],
				],
				'labelFontStyle' => [
					'type'    => 'select',
					'default' => 'normal',
					'label'   => __( 'Labels Font Style', 'checkmate-pdf-invoices' ),
					'options' => [
						'normal' => __( 'Normal', 'checkmate-pdf-invoices' ),
						'italic' => __( 'Italic', 'checkmate-pdf-invoices' ),
					],
				],
				'labelTextDecoration' => [
					'type'    => 'select',
					'default' => 'none',
					'label'   => __( 'Labels Text Decoration', 'checkmate-pdf-invoices' ),
					'options' => [
						'none'         => __( 'None', 'checkmate-pdf-invoices' ),
						'underline'    => __( 'Underline', 'checkmate-pdf-invoices' ),
						'overline'     => __( 'Overline', 'checkmate-pdf-invoices' ),
						'line-through' => __( 'Line-through', 'checkmate-pdf-invoices' ),
					],
				],
				'labelTextTransform' => [
					'type'    => 'select',
					'default' => 'none',
					'label'   => __( 'Labels Text Transform', 'checkmate-pdf-invoices' ),
					'options' => [
						'none'       => __( 'None', 'checkmate-pdf-invoices' ),
						'uppercase'  => __( 'Uppercase', 'checkmate-pdf-invoices' ),
						'lowercase'  => __( 'Lowercase', 'checkmate-pdf-invoices' ),
						'capitalize' => __( 'Capitalize', 'checkmate-pdf-invoices' ),
					],
				],
				'labelTextColor' => [
					'type'    => 'color',
					'default' => '',
					'label'   => __( 'Labels Text Color', 'checkmate-pdf-invoices' ),
				],
				'showSubtotal' => [
					'type'    => 'toggle',
					'default' => true,
					'label'   => __( 'Show Subtotal', 'checkmate-pdf-invoices' ),
				],
				'showShipping' => [
					'type'    => 'toggle',
					'default' => true,
					'label'   => __( 'Show Shipping', 'checkmate-pdf-invoices' ),
				],
				'showTax' => [
					'type'    => 'toggle',
					'default' => true,
					'label'   => __( 'Show Tax', 'checkmate-pdf-invoices' ),
				],
				'showDiscount' => [
					'type'    => 'toggle',
					'default' => true,
					'label'   => __( 'Show Discount', 'checkmate-pdf-invoices' ),
				],
				'showTotal' => [
					'type'    => 'toggle',
					'default' => true,
					'label'   => __( 'Show Total', 'checkmate-pdf-invoices' ),
				],
				'totalBold' => [
					'type'    => 'toggle',
					'default' => true,
					'label'   => __( 'Bold Total', 'checkmate-pdf-invoices' ),
				],
				'align' => [
					'type'    => 'select',
					'default' => 'right',
					'label'   => __( 'Alignment', 'checkmate-pdf-invoices' ),
					'options' => [
						'left'  => __( 'Left', 'checkmate-pdf-invoices' ),
						'right' => __( 'Right', 'checkmate-pdf-invoices' ),
					],
				],
				'width' => [
					'type'    => 'number',
					'default' => 40,
					'label'   => __( 'Width (%)', 'checkmate-pdf-invoices' ),
					'min'     => 20,
					'max'     => 100,
				],
				'fontSize' => [
					'type'    => 'number',
					'default' => 9,
					'label'   => __( 'Font Size (pt)', 'checkmate-pdf-invoices' ),
				],
				'textAlign' => [
					'type'    => 'select',
					'default' => 'right',
					'label'   => __( 'Text Align', 'checkmate-pdf-invoices' ),
					'options' => [
						'start'  => __( 'Start', 'checkmate-pdf-invoices' ),
						'left'   => __( 'Left', 'checkmate-pdf-invoices' ),
						'center' => __( 'Center', 'checkmate-pdf-invoices' ),
						'right'  => __( 'Right', 'checkmate-pdf-invoices' ),
						'end'    => __( 'End', 'checkmate-pdf-invoices' ),
					],
				],
			],
		] );

		$this->register_block( 'notes', [
			'title'       => __( 'Notes', 'checkmate-pdf-invoices' ),
			'description' => __( 'Document notes/terms', 'checkmate-pdf-invoices' ),
			'category'    => 'document',
			'icon'        => 'file-text',
			'attributes'  => [
				'title' => [
					'type'    => 'text',
					'default' => __( 'Notes:', 'checkmate-pdf-invoices' ),
					'label'   => __( 'Title', 'checkmate-pdf-invoices' ),
				],
				'showTitle' => [
					'type'    => 'toggle',
					'default' => true,
					'label'   => __( 'Show Title', 'checkmate-pdf-invoices' ),
				],
				'content' => [
					'type'    => 'textarea',
					'default' => __( 'Thank you for your business!', 'checkmate-pdf-invoices' ),
					'label'   => __( 'Content', 'checkmate-pdf-invoices' ),
				],
				'fontSize' => [
					'type'    => 'number',
					'default' => 8,
					'label'   => __( 'Font Size (pt)', 'checkmate-pdf-invoices' ),
				],
				'align' => [
					'type'    => 'select',
					'default' => 'left',
					'label'   => __( 'Text Align', 'checkmate-pdf-invoices' ),
					'options' => [
						'start'  => __( 'Start', 'checkmate-pdf-invoices' ),
						'left'   => __( 'Left', 'checkmate-pdf-invoices' ),
						'center' => __( 'Center', 'checkmate-pdf-invoices' ),
						'right'  => __( 'Right', 'checkmate-pdf-invoices' ),
						'end'    => __( 'End', 'checkmate-pdf-invoices' ),
					],
				],
			],
		] );

		$this->register_block( 'footer', [
			'title'       => __( 'Footer', 'checkmate-pdf-invoices' ),
			'description' => __( 'Page footer content', 'checkmate-pdf-invoices' ),
			'category'    => 'document',
			'icon'        => 'align-bottom',
			'attributes'  => [
				'content' => [
					'type'    => 'textarea',
					'default' => '',
					'label'   => __( 'Content', 'checkmate-pdf-invoices' ),
				],
				'fontSize' => [
					'type'    => 'number',
					'default' => 7,
					'label'   => __( 'Font Size (pt)', 'checkmate-pdf-invoices' ),
				],
				'textColor' => [
					'type'    => 'color',
					'default' => '#666666',
					'label'   => __( 'Text Color', 'checkmate-pdf-invoices' ),
				],
				'align' => [
					'type'    => 'select',
					'default' => 'center',
					'label'   => __( 'Alignment', 'checkmate-pdf-invoices' ),
					'options' => [
						'start'  => __( 'Start', 'checkmate-pdf-invoices' ),
						'left'   => __( 'Left', 'checkmate-pdf-invoices' ),
						'center' => __( 'Center', 'checkmate-pdf-invoices' ),
						'right'  => __( 'Right', 'checkmate-pdf-invoices' ),
						'end'    => __( 'End', 'checkmate-pdf-invoices' ),
					],
				],
			],
		] );

		// Apply filter for extensibility
		$this->blocks = apply_filters( 'checkmate_pdf_registered_blocks', $this->blocks );
	}

	/**
	 * Register a single block
	 *
	 * @param string $type Block type identifier.
	 * @param array  $config Block configuration.
	 */
	public function register_block( string $type, array $config ): void {
		$base_style_attributes = [
			'paddingTop' => [
				'type'    => 'number',
				'default' => 0,
				'label'   => __( 'Padding Top (px)', 'checkmate-pdf-invoices' ),
				'min'     => 0,
				'max'     => 100,
			],
			'paddingRight' => [
				'type'    => 'number',
				'default' => 0,
				'label'   => __( 'Padding Right (px)', 'checkmate-pdf-invoices' ),
				'min'     => 0,
				'max'     => 100,
			],
			'paddingBottom' => [
				'type'    => 'number',
				'default' => 0,
				'label'   => __( 'Padding Bottom (px)', 'checkmate-pdf-invoices' ),
				'min'     => 0,
				'max'     => 100,
			],
			'paddingLeft' => [
				'type'    => 'number',
				'default' => 0,
				'label'   => __( 'Padding Left (px)', 'checkmate-pdf-invoices' ),
				'min'     => 0,
				'max'     => 100,
			],
			'marginTop' => [
				'type'    => 'number',
				'default' => 0,
				'label'   => __( 'Margin Top (px)', 'checkmate-pdf-invoices' ),
				'min'     => 0,
				'max'     => 100,
			],
			'marginRight' => [
				'type'    => 'number',
				'default' => 0,
				'label'   => __( 'Margin Right (px)', 'checkmate-pdf-invoices' ),
				'min'     => 0,
				'max'     => 100,
			],
			'marginBottom' => [
				'type'    => 'number',
				'default' => 5,
				'label'   => __( 'Margin Bottom (px)', 'checkmate-pdf-invoices' ),
				'min'     => 0,
				'max'     => 100,
			],
			'marginLeft' => [
				'type'    => 'number',
				'default' => 0,
				'label'   => __( 'Margin Left (px)', 'checkmate-pdf-invoices' ),
				'min'     => 0,
				'max'     => 100,
			],
			'backgroundColor' => [
				'type'    => 'color',
				'default' => '',
				'label'   => __( 'Background Color', 'checkmate-pdf-invoices' ),
			],
			'backgroundImage' => [
				'type'    => 'image',
				'default' => '',
				'label'   => __( 'Background Image', 'checkmate-pdf-invoices' ),
			],
			'backgroundRepeat' => [
				'type'    => 'select',
				'default' => 'no-repeat',
				'label'   => __( 'Background Repeat', 'checkmate-pdf-invoices' ),
				'options' => [
					'no-repeat' => __( 'No Repeat', 'checkmate-pdf-invoices' ),
					'repeat'    => __( 'Repeat', 'checkmate-pdf-invoices' ),
					'repeat-x'  => __( 'Repeat X', 'checkmate-pdf-invoices' ),
					'repeat-y'  => __( 'Repeat Y', 'checkmate-pdf-invoices' ),
				],
			],
			'backgroundPosition' => [
				'type'    => 'select',
				'default' => 'top left',
				'label'   => __( 'Background Position', 'checkmate-pdf-invoices' ),
				'options' => [
					'top left'      => __( 'Top Left', 'checkmate-pdf-invoices' ),
					'top center'    => __( 'Top Center', 'checkmate-pdf-invoices' ),
					'top right'     => __( 'Top Right', 'checkmate-pdf-invoices' ),
					'center left'   => __( 'Center Left', 'checkmate-pdf-invoices' ),
					'center center' => __( 'Center', 'checkmate-pdf-invoices' ),
					'center right'  => __( 'Center Right', 'checkmate-pdf-invoices' ),
					'bottom left'   => __( 'Bottom Left', 'checkmate-pdf-invoices' ),
					'bottom center' => __( 'Bottom Center', 'checkmate-pdf-invoices' ),
					'bottom right'  => __( 'Bottom Right', 'checkmate-pdf-invoices' ),
				],
			],
			'backgroundSize' => [
				'type'    => 'select',
				'default' => 'auto',
				'label'   => __( 'Background Size', 'checkmate-pdf-invoices' ),
				'options' => [
					'auto'    => __( 'Auto', 'checkmate-pdf-invoices' ),
					'cover'   => __( 'Cover', 'checkmate-pdf-invoices' ),
					'contain' => __( 'Contain', 'checkmate-pdf-invoices' ),
					'100%'    => __( '100%', 'checkmate-pdf-invoices' ),
				],
			],
		];

		// Avoid confusing UX: image/logo blocks already render an image; background-image controls
		// make it look like an image is set while the real image URL is empty.
		if ( in_array( $type, [ 'image', 'logo' ], true ) ) {
			unset( $base_style_attributes['backgroundImage'], $base_style_attributes['backgroundRepeat'], $base_style_attributes['backgroundPosition'], $base_style_attributes['backgroundSize'] );
		}

		$typography_attributes = [
			'textColor' => [
				'type'    => 'color',
				'default' => '',
				'label'   => __( 'Text Color', 'checkmate-pdf-invoices' ),
			],
			'textTransform' => [
				'type'    => 'select',
				'default' => 'none',
				'label'   => __( 'Text Transform', 'checkmate-pdf-invoices' ),
				'options' => [
					'none'       => __( 'None', 'checkmate-pdf-invoices' ),
					'uppercase'  => __( 'Uppercase', 'checkmate-pdf-invoices' ),
					'lowercase'  => __( 'Lowercase', 'checkmate-pdf-invoices' ),
					'capitalize' => __( 'Capitalize', 'checkmate-pdf-invoices' ),
				],
			],
			'fontWeight' => [
				'type'    => 'select',
				'default' => 'normal',
				'label'   => __( 'Font Weight', 'checkmate-pdf-invoices' ),
				'options' => [
					'normal' => __( 'Normal', 'checkmate-pdf-invoices' ),
					'bold'   => __( 'Bold', 'checkmate-pdf-invoices' ),
				],
			],
			'fontStyle' => [
				'type'    => 'select',
				'default' => 'normal',
				'label'   => __( 'Font Style', 'checkmate-pdf-invoices' ),
				'options' => [
					'normal' => __( 'Normal', 'checkmate-pdf-invoices' ),
					'italic' => __( 'Italic', 'checkmate-pdf-invoices' ),
				],
			],
			'textDecoration' => [
				'type'    => 'select',
				'default' => 'none',
				'label'   => __( 'Text Decoration', 'checkmate-pdf-invoices' ),
				'options' => [
					'none'         => __( 'None', 'checkmate-pdf-invoices' ),
					'underline'    => __( 'Underline', 'checkmate-pdf-invoices' ),
					'overline'     => __( 'Overline', 'checkmate-pdf-invoices' ),
					'line-through' => __( 'Line-through', 'checkmate-pdf-invoices' ),
				],
			],
		];

		$non_typography_blocks = [ 'row', 'column', 'spacer', 'divider', 'logo', 'image' ];
		$include_typography = ! in_array( $type, $non_typography_blocks, true );

		$block_attributes = array_merge(
			$base_style_attributes,
			$include_typography ? $typography_attributes : [],
			$config['attributes'] ?? []
		);

		$this->blocks[ $type ] = array_merge( [
			'type'        => $type,
			'title'       => '',
			'description' => '',
			'category'    => 'content',
			'icon'        => 'square',
			'supports'    => [],
			'attributes'  => [],
		], $config, [ 'attributes' => $block_attributes ] );
	}

	/**
	 * Get all registered blocks
	 *
	 * @return array
	 */
	public function get_blocks(): array {
		return $this->blocks;
	}

	/**
	 * Get a single block definition
	 *
	 * @param string $type Block type.
	 * @return array|null
	 */
	public function get_block( string $type ): ?array {
		return $this->blocks[ $type ] ?? null;
	}

	/**
	 * Get blocks by category
	 *
	 * @param string $category Category ID.
	 * @return array
	 */
	public function get_blocks_by_category( string $category ): array {
		return array_filter( $this->blocks, fn( $block ) => $block['category'] === $category );
	}

	/**
	 * Get all categories
	 *
	 * @return array
	 */
	public function get_categories(): array {
		return $this->categories;
	}

	/**
	 * Get blocks organized by category
	 *
	 * @return array
	 */
	public function get_blocks_grouped(): array {
		$grouped = [];
		foreach ( $this->categories as $cat_id => $category ) {
			$blocks = $this->get_blocks_by_category( $cat_id );
			if ( ! empty( $blocks ) ) {
				$grouped[ $cat_id ] = [
					'category' => $category,
					'blocks'   => $blocks,
				];
			}
		}
		return $grouped;
	}

	/**
	 * Get default attributes for a block type
	 *
	 * @param string $type Block type.
	 * @return array
	 */
	public function get_default_attributes( string $type ): array {
		$block = $this->get_block( $type );
		if ( ! $block ) {
			return [];
		}

		$defaults = [];
		foreach ( $block['attributes'] as $key => $config ) {
			$defaults[ $key ] = $config['default'] ?? '';
		}
		return $defaults;
	}

	/**
	 * Create a new block instance with defaults
	 *
	 * @param string $type Block type.
	 * @param array  $attributes Override attributes.
	 * @return array|null
	 */
	public function create_block( string $type, array $attributes = [] ): ?array {
		$block_def = $this->get_block( $type );
		if ( ! $block_def ) {
			return null;
		}

		$block = [
			'id'         => wp_generate_uuid4(),
			'type'       => $type,
			'attributes' => array_merge( $this->get_default_attributes( $type ), $attributes ),
			'children'   => [],
		];

		// Add default children if specified
		if ( ! empty( $block_def['default_children'] ) ) {
			foreach ( $block_def['default_children'] as $child_config ) {
				$child = $this->create_block( $child_config['type'], $child_config['attributes'] ?? [] );
				if ( $child ) {
					$block['children'][] = $child;
				}
			}
		}

		return $block;
	}
}
