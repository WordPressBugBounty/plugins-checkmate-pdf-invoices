<?php
/**
 * Template Data Model - Handles template structure and persistence
 *
 * @package Checkmate\PdfInvoices\Editor
 */

namespace Checkmate\PdfInvoices\Editor;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Template class
 */
class Template {

	/**
	 * Template ID
	 *
	 * @var int
	 */
	private int $id = 0;

	/**
	 * Template name
	 *
	 * @var string
	 */
	private string $name = '';

	/**
	 * Document type (invoice, packing-slip, etc.)
	 *
	 * @var string
	 */
	private string $document_type = 'invoice';

	/**
	 * Template blocks
	 *
	 * @var array
	 */
	private array $blocks = [];

	/**
	 * Page settings
	 *
	 * @var array
	 */
	private array $page_settings = [];

	/**
	 * Is this template active for its document type
	 *
	 * @var bool
	 */
	private bool $is_active = false;

	/**
	 * Created timestamp
	 *
	 * @var string
	 */
	private string $created_at = '';

	/**
	 * Updated timestamp
	 *
	 * @var string
	 */
	private string $updated_at = '';

	/**
	 * Default page settings
	 *
	 * @var array
	 */
	private static array $default_page_settings = [
		'paperSize'       => 'a4',         // a4, letter, legal
		'orientation'     => 'portrait',   // portrait, landscape
		'marginTop'       => 15,           // mm
		'marginRight'     => 15,           // mm
		'marginBottom'    => 15,           // mm
		'marginLeft'      => 15,           // mm
		'fontFamily'      => 'DejaVu Sans',
		'baseFontSize'    => 9,            // pt
		'textColor'       => '#000000',
		'backgroundColor' => '#ffffff',
		'backgroundImage' => '',
		'backgroundRepeat' => 'no-repeat',
		'backgroundPosition' => 'top left',
		'backgroundSize' => 'auto',
	];

	/**
	 * Constructor
	 *
	 * @param array $data Template data.
	 */
	public function __construct( array $data = [] ) {
		if ( ! empty( $data ) ) {
			$this->hydrate( $data );
		} else {
			$this->page_settings = self::$default_page_settings;
		}
	}

	/**
	 * Hydrate template from data array
	 *
	 * @param array $data Template data.
	 */
	public function hydrate( array $data ): void {
		$this->id            = (int) ( $data['id'] ?? 0 );
		$this->name          = sanitize_text_field( $data['name'] ?? '' );
		$this->document_type = sanitize_key( $data['document_type'] ?? 'invoice' );
		$this->is_active     = (bool) ( $data['is_active'] ?? false );
		$this->created_at    = $data['created_at'] ?? current_time( 'mysql' );
		$this->updated_at    = $data['updated_at'] ?? current_time( 'mysql' );

		// Parse and sanitize blocks
		if ( ! empty( $data['blocks'] ) ) {
			$raw_blocks   = is_string( $data['blocks'] )
				? json_decode( $data['blocks'], true )
				: $data['blocks'];
			$this->blocks = is_array( $raw_blocks ) ? $this->sanitize_blocks( $raw_blocks ) : [];
		}

		// Parse and sanitize page settings
		$raw_page_settings = [];
		if ( ! empty( $data['page_settings'] ) ) {
			$raw_page_settings = is_string( $data['page_settings'] )
				? json_decode( $data['page_settings'], true )
				: $data['page_settings'];
			$raw_page_settings = is_array( $raw_page_settings ) ? $this->sanitize_page_settings( $raw_page_settings ) : [];
		}
		$this->page_settings = wp_parse_args( $raw_page_settings, self::$default_page_settings );
	}

	/**
	 * Create Template instance from array data
	 *
	 * @param array $data Template data.
	 * @return self
	 */
	public static function from_array( array $data ): self {
		return new self( $data );
	}

	/**
	 * Get template ID
	 *
	 * @return int
	 */
	public function get_id(): int {
		return $this->id;
	}

	/**
	 * Get template name
	 *
	 * @return string
	 */
	public function get_name(): string {
		return $this->name;
	}

	/**
	 * Set template name
	 *
	 * @param string $name Template name.
	 */
	public function set_name( string $name ): void {
		$this->name = sanitize_text_field( $name );
	}

	/**
	 * Get document type
	 *
	 * @return string
	 */
	public function get_document_type(): string {
		return $this->document_type;
	}

	/**
	 * Set document type
	 *
	 * @param string $type Document type.
	 */
	public function set_document_type( string $type ): void {
		$valid_types = [ 'invoice', 'packing-slip', 'credit-note', 'delivery-note' ];
		if ( in_array( $type, $valid_types, true ) ) {
			$this->document_type = $type;
		}
	}

	/**
	 * Get all blocks
	 *
	 * @return array
	 */
	public function get_blocks(): array {
		return $this->blocks;
	}

	/**
	 * Set blocks
	 *
	 * @param array $blocks Template blocks.
	 */
	public function set_blocks( array $blocks ): void {
		$this->blocks = $this->sanitize_blocks( $blocks );
	}

	/**
	 * Get page settings
	 *
	 * @return array
	 */
	public function get_page_settings(): array {
		return $this->page_settings;
	}

	/**
	 * Set page settings
	 *
	 * @param array $settings Page settings.
	 */
	public function set_page_settings( array $settings ): void {
		$this->page_settings = wp_parse_args( 
			$this->sanitize_page_settings( $settings ), 
			self::$default_page_settings 
		);
	}

	/**
	 * Get a single page setting
	 *
	 * @param string $key Setting key.
	 * @return mixed
	 */
	public function get_page_setting( string $key ) {
		return $this->page_settings[ $key ] ?? null;
	}

	/**
	 * Set a single page setting
	 *
	 * @param string $key   Setting key.
	 * @param mixed  $value Setting value.
	 */
	public function set_page_setting( string $key, $value ): void {
		if ( array_key_exists( $key, self::$default_page_settings ) ) {
			$this->page_settings[ $key ] = $value;
		}
	}

	/**
	 * Check if template is active
	 *
	 * @return bool
	 */
	public function is_active(): bool {
		return $this->is_active;
	}

	/**
	 * Set active status
	 *
	 * @param bool $active Is active.
	 */
	public function set_active( bool $active ): void {
		$this->is_active = $active;
	}

	/**
	 * Get created timestamp
	 *
	 * @return string
	 */
	public function get_created_at(): string {
		return $this->created_at;
	}

	/**
	 * Get updated timestamp
	 *
	 * @return string
	 */
	public function get_updated_at(): string {
		return $this->updated_at;
	}

	/**
	 * Add a block to the template
	 *
	 * @param array      $block  Block data.
	 * @param int|null   $index  Position to insert at (null = end).
	 * @param string|null $parent_id Parent block ID for nested blocks.
	 * @return string Block ID.
	 */
	public function add_block( array $block, ?int $index = null, ?string $parent_id = null ): string {
		if ( empty( $block['id'] ) ) {
			$block['id'] = wp_generate_uuid4();
		}

		if ( $parent_id ) {
			$this->blocks = $this->add_block_to_parent( $this->blocks, $parent_id, $block, $index );
		} else {
			if ( $index === null ) {
				$this->blocks[] = $block;
			} else {
				array_splice( $this->blocks, $index, 0, [ $block ] );
			}
		}

		return $block['id'];
	}

	/**
	 * Update a block by ID
	 *
	 * @param string $block_id   Block ID.
	 * @param array  $attributes New attributes.
	 * @return bool Success.
	 */
	public function update_block( string $block_id, array $attributes ): bool {
		$this->blocks = $this->update_block_recursive( $this->blocks, $block_id, $attributes );
		return true;
	}

	/**
	 * Remove a block by ID
	 *
	 * @param string $block_id Block ID.
	 * @return bool Success.
	 */
	public function remove_block( string $block_id ): bool {
		$this->blocks = $this->remove_block_recursive( $this->blocks, $block_id );
		return true;
	}

	/**
	 * Move a block
	 *
	 * @param string      $block_id   Block ID to move.
	 * @param int         $new_index  New position.
	 * @param string|null $new_parent New parent ID.
	 * @return bool Success.
	 */
	public function move_block( string $block_id, int $new_index, ?string $new_parent = null ): bool {
		// Find and remove the block
		$block = $this->find_block( $this->blocks, $block_id );
		if ( ! $block ) {
			return false;
		}

		$this->remove_block( $block_id );
		$this->add_block( $block, $new_index, $new_parent );

		return true;
	}

	/**
	 * Get a block by ID
	 *
	 * @param string $block_id Block ID.
	 * @return array|null
	 */
	public function get_block( string $block_id ): ?array {
		return $this->find_block( $this->blocks, $block_id );
	}

	/**
	 * Find block recursively
	 *
	 * @param array  $blocks   Blocks array.
	 * @param string $block_id Block ID.
	 * @return array|null
	 */
	private function find_block( array $blocks, string $block_id ): ?array {
		foreach ( $blocks as $block ) {
			if ( $block['id'] === $block_id ) {
				return $block;
			}
			if ( ! empty( $block['children'] ) ) {
				$found = $this->find_block( $block['children'], $block_id );
				if ( $found ) {
					return $found;
				}
			}
		}
		return null;
	}

	/**
	 * Add block to parent recursively
	 *
	 * @param array       $blocks    Blocks array.
	 * @param string      $parent_id Parent ID.
	 * @param array       $block     Block to add.
	 * @param int|null    $index     Position.
	 * @return array Updated blocks.
	 */
	private function add_block_to_parent( array $blocks, string $parent_id, array $block, ?int $index ): array {
		foreach ( $blocks as &$b ) {
			if ( $b['id'] === $parent_id ) {
				if ( ! isset( $b['children'] ) ) {
					$b['children'] = [];
				}
				if ( $index === null ) {
					$b['children'][] = $block;
				} else {
					array_splice( $b['children'], $index, 0, [ $block ] );
				}
				return $blocks;
			}
			if ( ! empty( $b['children'] ) ) {
				$b['children'] = $this->add_block_to_parent( $b['children'], $parent_id, $block, $index );
			}
		}
		return $blocks;
	}

	/**
	 * Update block recursively
	 *
	 * @param array  $blocks     Blocks array.
	 * @param string $block_id   Block ID.
	 * @param array  $attributes New attributes.
	 * @return array Updated blocks.
	 */
	private function update_block_recursive( array $blocks, string $block_id, array $attributes ): array {
		foreach ( $blocks as &$block ) {
			if ( $block['id'] === $block_id ) {
				$block['attributes'] = array_merge( $block['attributes'] ?? [], $attributes );
				return $blocks;
			}
			if ( ! empty( $block['children'] ) ) {
				$block['children'] = $this->update_block_recursive( $block['children'], $block_id, $attributes );
			}
		}
		return $blocks;
	}

	/**
	 * Remove block recursively
	 *
	 * @param array  $blocks   Blocks array.
	 * @param string $block_id Block ID to remove.
	 * @return array Updated blocks.
	 */
	private function remove_block_recursive( array $blocks, string $block_id ): array {
		$blocks = array_filter( $blocks, fn( $b ) => $b['id'] !== $block_id );
		foreach ( $blocks as &$block ) {
			if ( ! empty( $block['children'] ) ) {
				$block['children'] = $this->remove_block_recursive( $block['children'], $block_id );
			}
		}
		return array_values( $blocks );
	}

	/**
	 * Sanitize blocks array
	 *
	 * @param array $blocks Blocks to sanitize.
	 * @return array Sanitized blocks.
	 */
	private function sanitize_blocks( array $blocks ): array {
		$sanitized = [];
		$registry  = BlockRegistry::instance();

		foreach ( $blocks as $block ) {
			if ( empty( $block['type'] ) ) {
				continue;
			}

			$block_def = $registry->get_block( $block['type'] );
			if ( ! $block_def ) {
				continue;
			}

			$sanitized_block = [
				'id'         => sanitize_key( $block['id'] ?? wp_generate_uuid4() ),
				'type'       => $block['type'],
				'attributes' => [],
				'children'   => [],
			];

			// Sanitize attributes based on block definition
			if ( ! empty( $block['attributes'] ) ) {
				foreach ( $block['attributes'] as $key => $value ) {
					if ( isset( $block_def['attributes'][ $key ] ) ) {
						$attr_def = $block_def['attributes'][ $key ];
						$sanitized_block['attributes'][ $key ] = $this->sanitize_attribute_value( $value, $attr_def );
					}
				}
			}

			// Recursively sanitize children
			if ( ! empty( $block['children'] ) ) {
				$sanitized_block['children'] = $this->sanitize_blocks( $block['children'] );
			}

			$sanitized[] = $sanitized_block;
		}

		return $sanitized;
	}

	/**
	 * Sanitize a single attribute value
	 *
	 * @param mixed $value    The value to sanitize.
	 * @param array $attr_def Attribute definition.
	 * @return mixed Sanitized value.
	 */
	private function sanitize_attribute_value( $value, array $attr_def ) {
		$type = $attr_def['type'] ?? 'text';

		switch ( $type ) {
			case 'text':
			case 'textarea':
				return sanitize_text_field( $value );

			case 'richtext':
				return wp_kses_post( $value );

			case 'number':
				$num = (float) $value;
				if ( isset( $attr_def['min'] ) ) {
					$num = max( $attr_def['min'], $num );
				}
				if ( isset( $attr_def['max'] ) ) {
					$num = min( $attr_def['max'], $num );
				}
				return $num;

			case 'toggle':
				return (bool) $value;

			case 'select':
				$options = array_keys( $attr_def['options'] ?? [] );
				return in_array( $value, $options, true ) ? $value : ( $attr_def['default'] ?? '' );

			case 'color':
				return sanitize_hex_color( $value ) ?: ( $attr_def['default'] ?? '#000000' );

			case 'image':
				return is_string( $value ) ? esc_url_raw( $value ) : '';

			case 'columns':
				if ( is_array( $value ) ) {
					return array_map( 'sanitize_text_field', $value );
				}
				return [];

			default:
				return sanitize_text_field( $value );
		}
	}

	/**
	 * Sanitize page settings
	 *
	 * @param array $settings Settings to sanitize.
	 * @return array Sanitized settings.
	 */
	private function sanitize_page_settings( array $settings ): array {
		$sanitized = [];

		if ( isset( $settings['paperSize'] ) ) {
			$valid = [ 'a4', 'letter', 'legal', 'a3', 'a5' ];
			$sanitized['paperSize'] = in_array( $settings['paperSize'], $valid, true ) 
				? $settings['paperSize'] 
				: 'a4';
		}

		if ( isset( $settings['orientation'] ) ) {
			$valid = [ 'portrait', 'landscape' ];
			$sanitized['orientation'] = in_array( $settings['orientation'], $valid, true ) 
				? $settings['orientation'] 
				: 'portrait';
		}

		foreach ( [ 'marginTop', 'marginRight', 'marginBottom', 'marginLeft' ] as $margin ) {
			if ( isset( $settings[ $margin ] ) ) {
				$sanitized[ $margin ] = max( 0, min( 100, (int) $settings[ $margin ] ) );
			}
		}

		if ( isset( $settings['fontFamily'] ) ) {
			$valid = [ 'DejaVu Sans', 'Helvetica', 'Times', 'Courier' ];
			$sanitized['fontFamily'] = in_array( $settings['fontFamily'], $valid, true ) 
				? $settings['fontFamily'] 
				: 'DejaVu Sans';
		}

		if ( isset( $settings['baseFontSize'] ) ) {
			$sanitized['baseFontSize'] = max( 6, min( 18, (int) $settings['baseFontSize'] ) );
		}

		if ( isset( $settings['textColor'] ) ) {
			$sanitized['textColor'] = sanitize_hex_color( $settings['textColor'] ) ?: '#000000';
		}

		if ( isset( $settings['backgroundColor'] ) ) {
			$sanitized['backgroundColor'] = sanitize_hex_color( $settings['backgroundColor'] ) ?: '#ffffff';
		}

		if ( isset( $settings['backgroundImage'] ) ) {
			$sanitized['backgroundImage'] = esc_url_raw( $settings['backgroundImage'] );
		}

		if ( isset( $settings['backgroundRepeat'] ) ) {
			$valid = [ 'no-repeat', 'repeat', 'repeat-x', 'repeat-y' ];
			$sanitized['backgroundRepeat'] = in_array( $settings['backgroundRepeat'], $valid, true )
				? $settings['backgroundRepeat']
				: 'no-repeat';
		}

		if ( isset( $settings['backgroundPosition'] ) ) {
			$valid = [
				'top left', 'top center', 'top right',
				'center left', 'center center', 'center right',
				'bottom left', 'bottom center', 'bottom right',
			];
			$sanitized['backgroundPosition'] = in_array( $settings['backgroundPosition'], $valid, true )
				? $settings['backgroundPosition']
				: 'top left';
		}

		if ( isset( $settings['backgroundSize'] ) ) {
			$valid = [ 'auto', 'cover', 'contain', '100% 100%' ];
			$sanitized['backgroundSize'] = in_array( $settings['backgroundSize'], $valid, true )
				? $settings['backgroundSize']
				: 'auto';
		}

		return $sanitized;
	}

	/**
	 * Convert template to array for storage
	 *
	 * @return array
	 */
	public function to_array(): array {
		return [
			'id'            => $this->id,
			'name'          => $this->name,
			'document_type' => $this->document_type,
			'blocks'        => $this->blocks,
			'page_settings' => $this->page_settings,
			'is_active'     => $this->is_active,
			'created_at'    => $this->created_at,
			'updated_at'    => $this->updated_at,
		];
	}

	/**
	 * Convert template to JSON
	 *
	 * @return string
	 */
	public function to_json(): string {
		return wp_json_encode( $this->to_array() );
	}

	/**
	 * Get paper dimensions in mm
	 *
	 * @return array [ width, height ]
	 */
	public function get_paper_dimensions(): array {
		$sizes = [
			'a4'     => [ 210, 297 ],
			'letter' => [ 216, 279 ],
			'legal'  => [ 216, 356 ],
			'a3'     => [ 297, 420 ],
			'a5'     => [ 148, 210 ],
		];

		$size = $sizes[ $this->page_settings['paperSize'] ] ?? $sizes['a4'];

		if ( $this->page_settings['orientation'] === 'landscape' ) {
			$size = [ $size[1], $size[0] ];
		}

		return [
			'width'  => $size[0],
			'height' => $size[1],
		];
	}

	/**
	 * Get content area dimensions (minus margins)
	 *
	 * @return array [ width, height ]
	 */
	public function get_content_dimensions(): array {
		$paper = $this->get_paper_dimensions();

		return [
			'width'  => $paper['width'] - $this->page_settings['marginLeft'] - $this->page_settings['marginRight'],
			'height' => $paper['height'] - $this->page_settings['marginTop'] - $this->page_settings['marginBottom'],
		];
	}

	/**
	 * Get default page settings
	 *
	 * @return array
	 */
	public static function get_default_page_settings(): array {
		return self::$default_page_settings;
	}
}
