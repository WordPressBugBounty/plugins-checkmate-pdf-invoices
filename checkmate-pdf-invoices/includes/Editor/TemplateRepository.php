<?php
/**
 * Template Repository - Handles template CRUD operations
 *
 * @package Checkmate\PdfInvoices\Editor
 */

namespace Checkmate\PdfInvoices\Editor;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Template Repository class
 */
class TemplateRepository {

	/**
	 * Default template limit (for the current non-licensed build).
	 *
	 * Can be overridden via the `CHECKMATE_PDF_INVOICES_TEMPLATE_LIMIT` constant
	 * or the `checkmate_pdf_invoices_template_limit` filter.
	 */
	private const DEFAULT_TEMPLATE_LIMIT = 3;

	/**
	 * Table name
	 *
	 * @var string
	 */
	private string $table_name;

	/**
	 * Singleton instance
	 *
	 * @var TemplateRepository|null
	 */
	private static ?TemplateRepository $instance = null;

	/**
	 * Get singleton instance
	 *
	 * @return TemplateRepository
	 */
	public static function instance(): TemplateRepository {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor
	 */
	private function __construct() {
		global $wpdb;
		$this->table_name = $wpdb->prefix . 'checkmate_pdf_templates';
	}

	/**
	 * Create templates table
	 */
	public static function create_table(): void {
		global $wpdb;

		$table_name      = $wpdb->prefix . 'checkmate_pdf_templates';
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE $table_name (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			name varchar(255) NOT NULL,
			document_type varchar(50) NOT NULL DEFAULT 'invoice',
			blocks longtext NOT NULL,
			page_settings text NOT NULL,
			is_active tinyint(1) NOT NULL DEFAULT 0,
			created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY document_type (document_type),
			KEY is_active (is_active)
		) $charset_collate;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}

	/**
	 * Drop templates table
	 */
	public static function drop_table(): void {
		global $wpdb;
		$table_name = $wpdb->prefix . 'checkmate_pdf_templates';
		$wpdb->query( "DROP TABLE IF EXISTS $table_name" ); // phpcs:ignore
	}

	/**
	 * Save a template
	 *
	 * @param Template $template Template to save.
	 * @return int Template ID.
	 */
	public function save( Template $template ): int {
		global $wpdb;

		// Check if table exists
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$table_exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $this->table_name ) );
		if ( ! $table_exists ) {
			self::create_table();
		}

		$data = [
			'name'          => $template->get_name(),
			'document_type' => $template->get_document_type(),
			'blocks'        => wp_json_encode( $template->get_blocks() ),
			'page_settings' => wp_json_encode( $template->get_page_settings() ),
			'is_active'     => $template->is_active() ? 1 : 0,
			'updated_at'    => current_time( 'mysql' ),
		];

		$format = [ '%s', '%s', '%s', '%s', '%d', '%s' ];

		if ( $template->get_id() > 0 ) {
			// Update existing
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->update(
				$this->table_name,
				$data,
				[ 'id' => $template->get_id() ],
				$format,
				[ '%d' ]
			);
			return $template->get_id();
		} else {
			if ( ! $this->can_create_new_template() ) {
				return 0;
			}

			// Insert new
			$data['created_at'] = current_time( 'mysql' );
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			$wpdb->insert( $this->table_name, $data, array_merge( $format, [ '%s' ] ) );
			return (int) $wpdb->insert_id;
		}
	}

	/**
	 * Get a template by ID
	 *
	 * @param int $id Template ID.
	 * @return Template|null
	 */
	public function find( int $id ): ?Template {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$row = $wpdb->get_row(
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$wpdb->prepare( "SELECT * FROM {$this->table_name} WHERE id = %d", $id ),
			ARRAY_A
		);

		if ( ! $row ) {
			return null;
		}

		return new Template( $row );
	}

	/**
	 * Get all templates
	 *
	 * @param array $args Query arguments.
	 * @return Template[]
	 */
	public function find_all( array $args = [] ): array {
		global $wpdb;

		$defaults = [
			'document_type' => '',
			'is_active'     => null,
			'orderby'       => 'updated_at',
			'order'         => 'DESC',
			'limit'         => 100,
			'offset'        => 0,
		];

		$args = wp_parse_args( $args, $defaults );

		$where  = [];
		$values = [];

		if ( ! empty( $args['document_type'] ) ) {
			$where[]  = 'document_type = %s';
			$values[] = $args['document_type'];
		}

		if ( $args['is_active'] !== null ) {
			$where[]  = 'is_active = %d';
			$values[] = $args['is_active'] ? 1 : 0;
		}

		$where_sql = ! empty( $where ) ? 'WHERE ' . implode( ' AND ', $where ) : '';

		$orderby = in_array( $args['orderby'], [ 'id', 'name', 'document_type', 'created_at', 'updated_at' ], true )
			? $args['orderby']
			: 'updated_at';

		$order = strtoupper( $args['order'] ) === 'ASC' ? 'ASC' : 'DESC';

		$limit  = max( 1, (int) $args['limit'] );
		$offset = max( 0, (int) $args['offset'] );

		$sql = "SELECT * FROM {$this->table_name} $where_sql ORDER BY $orderby $order LIMIT %d OFFSET %d";
		$values[] = $limit;
		$values[] = $offset;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter
		$rows = $wpdb->get_results(
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared
			$wpdb->prepare( $sql, ...$values ),
			ARRAY_A
		);

		return array_map( fn( $row ) => new Template( $row ), $rows ?: [] );
	}

	/**
	 * Get active template for a document type
	 *
	 * @param string $document_type Document type.
	 * @return Template|null
	 */
	public function find_active( string $document_type ): ?Template {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$row = $wpdb->get_row(
			$wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				"SELECT * FROM {$this->table_name} WHERE document_type = %s AND is_active = 1 LIMIT 1",
				$document_type
			),
			ARRAY_A
		);

		if ( ! $row ) {
			return null;
		}

		return new Template( $row );
	}

	/**
	 * Set a template as active (deactivates others of same type)
	 *
	 * @param int $id Template ID.
	 * @return bool Success.
	 */
	public function set_active( int $id ): bool {
		global $wpdb;

		$template = $this->find( $id );
		if ( ! $template ) {
			return false;
		}

		// Deactivate all templates of this document type
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->update(
			$this->table_name,
			[ 'is_active' => 0 ],
			[ 'document_type' => $template->get_document_type() ],
			[ '%d' ],
			[ '%s' ]
		);

		// Activate the selected one
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->update(
			$this->table_name,
			[ 'is_active' => 1 ],
			[ 'id' => $id ],
			[ '%d' ],
			[ '%d' ]
		);

		return true;
	}

	/**
	 * Delete a template
	 *
	 * @param int $id Template ID.
	 * @return bool Success.
	 */
	public function delete( int $id ): bool {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$result = $wpdb->delete(
			$this->table_name,
			[ 'id' => $id ],
			[ '%d' ]
		);

		return $result !== false;
	}

	/**
	 * Duplicate a template
	 *
	 * @param int $id Template ID to duplicate.
	 * @return int|null New template ID or null on failure.
	 */
	public function duplicate( int $id ): ?int {
		if ( ! $this->can_create_new_template() ) {
			return null;
		}

		$template = $this->find( $id );
		if ( ! $template ) {
			return null;
		}

		$new_template = new Template( [
			'name'          => $template->get_name() . ' (Copy)',
			'document_type' => $template->get_document_type(),
			'blocks'        => $template->get_blocks(),
			'page_settings' => $template->get_page_settings(),
			'is_active'     => false,
		] );

		return $this->save( $new_template );
	}

	/**
	 * Count templates
	 *
	 * @param array $args Filter arguments.
	 * @return int Count.
	 */
	public function count( array $args = [] ): int {
		global $wpdb;

		if ( ! $this->table_exists() ) {
			return 0;
		}

		$where  = [];
		$values = [];

		if ( ! empty( $args['document_type'] ) ) {
			$where[]  = 'document_type = %s';
			$values[] = $args['document_type'];
		}

		if ( isset( $args['is_active'] ) ) {
			$where[]  = 'is_active = %d';
			$values[] = $args['is_active'] ? 1 : 0;
		}

		$where_sql = ! empty( $where ) ? 'WHERE ' . implode( ' AND ', $where ) : '';

		if ( empty( $values ) ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			return (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$this->table_name}" );
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter
		return (int) $wpdb->get_var(
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
			$wpdb->prepare( "SELECT COUNT(*) FROM {$this->table_name} $where_sql", ...$values )
		);
	}

	/**
	 * Check if table exists
	 *
	 * @return bool
	 */
	public function table_exists(): bool {
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		return $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $this->table_name ) ) === $this->table_name;
	}

	/**
	 * Import a template from JSON
	 *
	 * @param string $json JSON string.
	 * @return int|null Template ID or null on failure.
	 */
	public function import_from_json( string $json ): ?int {
		if ( ! $this->can_create_new_template() ) {
			return null;
		}

		$data = json_decode( $json, true );
		if ( ! $data || json_last_error() !== JSON_ERROR_NONE ) {
			return null;
		}

		// Remove ID to create new
		unset( $data['id'] );
		$data['is_active'] = false;

		$template = new Template( $data );
		return $this->save( $template );
	}

	/**
	 * Get the maximum number of templates allowed.
	 *
	 * Returning 0 disables the limit.
	 */
	public function get_template_limit(): int {
		$limit = self::DEFAULT_TEMPLATE_LIMIT;

		if ( defined( 'CHECKMATE_PDF_INVOICES_TEMPLATE_LIMIT' ) ) {
			$limit = (int) CHECKMATE_PDF_INVOICES_TEMPLATE_LIMIT;
		}

		/**
		 * Filter the maximum number of templates allowed.
		 *
		 * Return 0 to disable the limit.
		 */
		$limit = (int) apply_filters( 'checkmate_pdf_invoices_template_limit', $limit );

		return max( 0, $limit );
	}

	/**
	 * Current number of stored templates.
	 */
	public function get_templates_used(): int {
		return $this->count();
	}

	public function is_template_limit_reached(): bool {
		$limit = $this->get_template_limit();
		return $limit > 0 && $this->get_templates_used() >= $limit;
	}

	public function can_create_new_template(): bool {
		return ! $this->is_template_limit_reached();
	}

	/**
	 * Export a template to JSON
	 *
	 * @param int $id Template ID.
	 * @return string|null JSON string or null on failure.
	 */
	public function export_to_json( int $id ): ?string {
		$template = $this->find( $id );
		if ( ! $template ) {
			return null;
		}

		$data = $template->to_array();
		unset( $data['id'], $data['is_active'], $data['created_at'], $data['updated_at'] );

		return wp_json_encode( $data, JSON_PRETTY_PRINT );
	}
}
