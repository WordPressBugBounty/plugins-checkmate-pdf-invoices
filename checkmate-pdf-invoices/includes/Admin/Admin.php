<?php
/**
 * Admin class for Checkmate PDF Invoices dashboard
 *
 * @package Checkmate\PdfInvoices
 */

namespace Checkmate\PdfInvoices\Admin;

use Checkmate\PdfInvoices\Editor\BlockRegistry;
use Checkmate\PdfInvoices\Editor\Template;
use Checkmate\PdfInvoices\Editor\TemplateRepository;
use Checkmate\PdfInvoices\Editor\PresetTemplates;
use Checkmate\PdfInvoices\Renderer\PDFRenderer;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Admin menu and dashboard handler
 */
final class Admin {

	/**
	 * Plugin version
	 */
	const VERSION = '2.0.1';

	/**
	 * Singleton instance
	 *
	 * @var Admin|null
	 */
	private static ?Admin $instance = null;

	/**
	 * Get singleton instance
	 *
	 * @return Admin
	 */
	public static function instance(): Admin {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor
	 */
	private function __construct() {
		$this->init_hooks();
	}

	/**
	 * Initialize hooks
	 */
	private function init_hooks(): void {
		add_action( 'admin_menu', [ $this, 'add_admin_menu' ], 10 );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_assets' ] );
		add_action( 'wp_ajax_checkmate_save_theme_mode', [ $this, 'ajax_save_theme_mode' ] );
		add_action( 'wp_ajax_checkmate_save_template', [ $this, 'ajax_save_template' ] );
		add_action( 'wp_ajax_checkmate_delete_template', [ $this, 'ajax_delete_template' ] );
		add_action( 'wp_ajax_checkmate_duplicate_template', [ $this, 'ajax_duplicate_template' ] );
		add_action( 'wp_ajax_checkmate_toggle_template_status', [ $this, 'ajax_toggle_template_status' ] );
		add_action( 'wp_ajax_checkmate_assign_template_event', [ $this, 'ajax_assign_template_event' ] );
		add_action( 'wp_ajax_checkmate_generate_pdf', [ $this, 'ajax_generate_pdf' ] );
		add_action( 'wp_ajax_checkmate_preview_pdf', [ $this, 'ajax_preview_pdf' ] );
		add_action( 'wp_ajax_checkmate_preview_template', [ $this, 'ajax_preview_template' ] );
	}

	/**
	 * AJAX handler for preset/saved template HTML preview (used on Templates page)
	 */
	public function ajax_preview_template(): void {
		$nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : '';
		if ( empty( $nonce ) ) {
			$nonce = isset( $_GET['nonce'] ) ? sanitize_text_field( wp_unslash( $_GET['nonce'] ) ) : '';
		}

		if ( ! wp_verify_nonce( $nonce, 'checkmate_admin_nonce' ) ) {
			wp_die( 'Security check failed' );
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Unauthorized' );
		}

		$preset_id   = isset( $_REQUEST['preset'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['preset'] ) ) : '';
		$template_id = isset( $_REQUEST['template_id'] ) ? absint( wp_unslash( $_REQUEST['template_id'] ) ) : 0;

		$template = null;
		if ( ! empty( $preset_id ) ) {
			$template = PresetTemplates::create_template( $preset_id );
		} elseif ( $template_id > 0 ) {
			$template = TemplateRepository::instance()->find( $template_id );
		}

		if ( ! $template ) {
			wp_die( 'Template not found' );
		}

		$this->generate_pdf_output( $template, false );
	}

	/**
	 * Add admin menu
	 */
	public function add_admin_menu(): void {
		// Add main menu page
		add_menu_page(
			esc_html__( 'Checkmate PDF', 'checkmate-pdf-invoices' ),
			esc_html__( 'Checkmate PDF', 'checkmate-pdf-invoices' ),
			'manage_options',
			'checkmate-pdf-invoices',
			[ $this, 'render_dashboard' ],
			$this->get_menu_icon(),
			56
		);

		// Add Dashboard submenu (replaces default duplicate)
		add_submenu_page(
			'checkmate-pdf-invoices',
			esc_html__( 'Dashboard', 'checkmate-pdf-invoices' ),
			esc_html__( 'Dashboard', 'checkmate-pdf-invoices' ),
			'manage_options',
			'checkmate-pdf-invoices',
			[ $this, 'render_dashboard' ]
		);

		// Add Templates submenu
		add_submenu_page(
			'checkmate-pdf-invoices',
			esc_html__( 'Templates', 'checkmate-pdf-invoices' ),
			esc_html__( 'Templates', 'checkmate-pdf-invoices' ),
			'manage_options',
			'checkmate-pdf-templates',
			[ $this, 'render_templates_page' ]
		);

		// Add Settings submenu
		add_submenu_page(
			'checkmate-pdf-invoices',
			esc_html__( 'Settings', 'checkmate-pdf-invoices' ),
			esc_html__( 'Settings', 'checkmate-pdf-invoices' ),
			'manage_options',
			'checkmate-pdf-settings',
			[ $this, 'render_settings_page' ]
		);

		// Add Editor page (hidden from menu)
		add_submenu_page(
			null, // Hidden page
			esc_html__( 'Template Editor', 'checkmate-pdf-invoices' ),
			esc_html__( 'Editor', 'checkmate-pdf-invoices' ),
			'manage_options',
			'checkmate-pdf-editor',
			[ $this, 'render_editor_page' ]
		);
	}

	/**
	 * Get menu icon SVG
	 *
	 * @return string
	 */
	private function get_menu_icon(): string {
		$svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>';
		return 'data:image/svg+xml;base64,' . base64_encode( $svg );
	}

	/**
	 * Enqueue admin assets
	 *
	 * @param string $hook Current admin page hook.
	 */
	public function enqueue_admin_assets( string $hook ): void {
		// Only load on our plugin pages
		if ( strpos( $hook, 'checkmate-pdf' ) === false ) {
			return;
		}

		$plugin_url = plugin_dir_url( CHECKMATE_PDF_INVOICES_PLUGIN_FILE );
		$plugin_dir = CHECKMATE_PDF_INVOICES_PLUGIN_DIR;

		// Theme init script — must load early (in <head>) on every plugin page.
		$theme_init_file = $plugin_dir . '/assets/js/admin-theme-init.js';
		$theme_init_version = file_exists( $theme_init_file ) ? filemtime( $theme_init_file ) : self::VERSION;
		wp_enqueue_script(
			'checkmate-pdf-theme-init',
			$plugin_url . 'assets/js/admin-theme-init.js',
			[],
			$theme_init_version,
			false // Load in <head> so theme class is applied before paint.
		);
		wp_localize_script(
			'checkmate-pdf-theme-init',
			'checkmateThemeInit',
			[
				'mode' => $this->get_user_theme_mode(),
			]
		);

		// Check if we're on the editor page
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only GET parameter for menu page routing.
		$page = isset( $_GET['page'] ) ? sanitize_key( (string) wp_unslash( $_GET['page'] ) ) : '';
		$is_editor = ( $page === 'checkmate-pdf-editor' );

		if ( $is_editor ) {
			$this->enqueue_editor_assets( $plugin_url, $plugin_dir );
			return;
		}

		// Enqueue dashboard styles
		$css_file = $plugin_dir . '/assets/css/admin-dashboard.css';
		$css_version = file_exists( $css_file ) ? filemtime( $css_file ) : self::VERSION;
		wp_enqueue_style(
			'checkmate-pdf-admin-dashboard',
			$plugin_url . 'assets/css/admin-dashboard.css',
			[],
			$css_version
		);

		// Enqueue dashboard scripts
		$js_file = $plugin_dir . '/assets/js/admin-dashboard.js';
		$js_version = file_exists( $js_file ) ? filemtime( $js_file ) : self::VERSION;
		wp_enqueue_script(
			'checkmate-pdf-admin-dashboard',
			$plugin_url . 'assets/js/admin-dashboard.js',
			[],
			$js_version,
			true
		);

		// Localize script
		wp_localize_script(
			'checkmate-pdf-admin-dashboard',
			'checkmateAdmin',
			[
				'ajaxUrl'   => admin_url( 'admin-ajax.php' ),
				'nonce'     => wp_create_nonce( 'checkmate_admin_nonce' ),
				'themeMode' => $this->get_user_theme_mode(),
			]
		);

		// Templates page scripts
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only GET parameter for menu page routing.
		$is_templates = ( $page === 'checkmate-pdf-templates' );
		if ( $is_templates ) {
			$tpl_js_file = $plugin_dir . '/assets/js/admin-templates.js';
			$tpl_js_version = file_exists( $tpl_js_file ) ? filemtime( $tpl_js_file ) : self::VERSION;
			wp_enqueue_script(
				'checkmate-pdf-admin-templates',
				$plugin_url . 'assets/js/admin-templates.js',
				[ 'checkmate-pdf-admin-dashboard' ],
				$tpl_js_version,
				true
			);

			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only GET parameter for UI state.
			$action = isset( $_GET['action'] ) ? sanitize_key( (string) wp_unslash( $_GET['action'] ) ) : '';
			$auto_open = ( $action === 'create' );
			$repository = TemplateRepository::instance();
			if ( $repository->is_template_limit_reached() ) {
				$auto_open = false;
			}
			wp_localize_script(
				'checkmate-pdf-admin-templates',
				'checkmateTemplates',
				[
					'ajaxUrl'       => admin_url( 'admin-ajax.php' ),
					'nonce'         => wp_create_nonce( 'checkmate_admin_nonce' ),
					'editorUrl'     => admin_url( 'admin.php?page=checkmate-pdf-editor' ),
					'autoOpenModal' => $auto_open,
					'i18n'          => [
						'noTemplates' => __( 'No templates of this type yet.', 'checkmate-pdf-invoices' ),
					],
				]
			);
		}
	}

	/**
	 * Enqueue editor-specific assets
	 *
	 * @param string $plugin_url Plugin URL.
	 * @param string $plugin_dir Plugin directory.
	 */
	private function enqueue_editor_assets( string $plugin_url, string $plugin_dir ): void {
		// WordPress media uploader
		wp_enqueue_media();

		// Editor CSS
		$css_file = $plugin_dir . '/assets/css/editor.css';
		$css_version = file_exists( $css_file ) ? filemtime( $css_file ) : self::VERSION;
		wp_enqueue_style(
			'checkmate-pdf-editor',
			$plugin_url . 'assets/css/editor.css',
			[],
			$css_version
		);

		// Editor JavaScript
		$js_file = $plugin_dir . '/assets/js/editor.js';
		$js_version = file_exists( $js_file ) ? filemtime( $js_file ) : self::VERSION;
		wp_enqueue_script(
			'checkmate-pdf-editor',
			$plugin_url . 'assets/js/editor.js',
			[ 'jquery' ],
			$js_version,
			true
		);
	}

	/**
	 * Get user theme mode preference
	 *
	 * @return string
	 */
	private function get_user_theme_mode(): string {
		$mode = get_user_meta( get_current_user_id(), 'checkmate_pdf_theme_mode', true );
		$allowed = [ 'dark', 'light', 'auto' ];
		return in_array( $mode, $allowed, true ) ? $mode : 'dark';
	}

	/**
	 * AJAX handler to save theme mode
	 */
	public function ajax_save_theme_mode(): void {
		check_ajax_referer( 'checkmate_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Unauthorized' );
		}

		$mode = isset( $_POST['mode'] ) ? sanitize_text_field( wp_unslash( $_POST['mode'] ) ) : 'dark';
		$allowed = [ 'dark', 'light', 'auto' ];

		if ( ! in_array( $mode, $allowed, true ) ) {
			$mode = 'dark';
		}

		update_user_meta( get_current_user_id(), 'checkmate_pdf_theme_mode', $mode );
		wp_send_json_success( [ 'mode' => $mode ] );
	}

	/**
	 * Render dashboard page
	 */
	public function render_dashboard(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		require_once CHECKMATE_PDF_INVOICES_PLUGIN_DIR . '/includes/Admin/views/dashboard.php';
	}

	/**
	 * Render templates page
	 */
	public function render_templates_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		require_once CHECKMATE_PDF_INVOICES_PLUGIN_DIR . '/includes/Admin/views/templates.php';
	}

	/**
	 * Render settings page
	 */
	public function render_settings_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		require_once CHECKMATE_PDF_INVOICES_PLUGIN_DIR . '/includes/Admin/views/settings.php';
	}

	/**
	 * Render editor page
	 */
	public function render_editor_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		require_once CHECKMATE_PDF_INVOICES_PLUGIN_DIR . '/includes/Admin/views/editor.php';
	}

	/**
	 * AJAX handler to save template
	 */
	public function ajax_save_template(): void {
		check_ajax_referer( 'checkmate_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( [ 'message' => 'Unauthorized' ] );
		}

		// The raw JSON string is unslashed here; all decoded values (blocks, page_settings, etc.)
		// are fully sanitized inside Template::hydrate() before being stored or rendered.
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- JSON string; sanitize_text_field would corrupt valid JSON.
		$template_json = isset( $_POST['template'] ) ? wp_unslash( $_POST['template'] ) : '';
		if ( empty( $template_json ) || ! is_string( $template_json ) ) {
			wp_send_json_error( [ 'message' => 'No template data provided' ] );
		}

		// Template is JSON, decode it; individual values are sanitized inside Template::hydrate().
		$template_data = json_decode( $template_json, true );
		if ( json_last_error() !== JSON_ERROR_NONE ) {
			wp_send_json_error( [ 'message' => 'Invalid JSON data' ] );
		}
		if ( ! is_array( $template_data ) ) {
			wp_send_json_error( [ 'message' => 'Invalid template data' ] );
		}

		$template = Template::from_array( $template_data );
		$repository = TemplateRepository::instance();

		if ( $template->get_id() <= 0 && $repository->is_template_limit_reached() ) {
			$limit = $repository->get_template_limit();
			$used  = $repository->get_templates_used();
			wp_send_json_error(
				[
					'message' => sprintf(
						/* translators: 1: used templates count, 2: templates limit */
						__( 'Template limit reached (%1$d/%2$d). Delete a template to create a new one.', 'checkmate-pdf-invoices' ),
						$used,
						$limit
					),
					'used'  => $used,
					'limit' => $limit,
				],
				403
			);
		}

		$saved_id = $repository->save( $template );

		if ( $saved_id > 0 ) {
			wp_send_json_success( [
				'id'      => $saved_id,
				'message' => 'Template saved successfully',
			] );
		} else {
			wp_send_json_error( [ 'message' => 'Failed to save template' ] );
		}
	}

	/**
	 * AJAX handler to delete template
	 */
	public function ajax_delete_template(): void {
		check_ajax_referer( 'checkmate_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( [ 'message' => 'Unauthorized' ] );
		}

		$template_id = isset( $_POST['template_id'] ) ? absint( wp_unslash( $_POST['template_id'] ) ) : 0;
		if ( ! $template_id ) {
			wp_send_json_error( [ 'message' => 'No template ID provided' ] );
		}

		$repository = TemplateRepository::instance();
		$result = $repository->delete( $template_id );

		if ( $result ) {
			wp_send_json_success( [ 'message' => 'Template deleted successfully' ] );
		} else {
			wp_send_json_error( [ 'message' => 'Failed to delete template' ] );
		}
	}

	/**
	 * AJAX handler to duplicate a template
	 */
	public function ajax_duplicate_template(): void {
		check_ajax_referer( 'checkmate_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( [ 'message' => 'Unauthorized' ] );
		}

		$template_id = isset( $_POST['template_id'] ) ? absint( wp_unslash( $_POST['template_id'] ) ) : 0;
		if ( ! $template_id ) {
			wp_send_json_error( [ 'message' => 'No template ID provided' ] );
		}

		$repository = TemplateRepository::instance();
		if ( $repository->is_template_limit_reached() ) {
			$limit = $repository->get_template_limit();
			$used  = $repository->get_templates_used();
			wp_send_json_error(
				[
					'message' => sprintf(
						/* translators: 1: used templates count, 2: templates limit */
						__( 'Template limit reached (%1$d/%2$d). Delete a template to duplicate.', 'checkmate-pdf-invoices' ),
						$used,
						$limit
					),
					'used'  => $used,
					'limit' => $limit,
				],
				403
			);
		}

		$original   = $repository->find( $template_id );

		if ( ! $original ) {
			wp_send_json_error( [ 'message' => 'Template not found' ] );
		}

		// Create a copy with a new name
		$copy_name = $original->get_name() . ' (Copy)';
		$copy      = new Template();
		$copy->set_name( $copy_name );
		$copy->set_document_type( $original->get_document_type() );
		$copy->set_blocks( $original->get_blocks() );
		$copy->set_page_settings( $original->get_page_settings() );
		$copy->set_active( false );

		$new_id = $repository->save( $copy );

		if ( $new_id ) {
			wp_send_json_success( [
				'message'     => 'Template duplicated successfully',
				'template_id' => $new_id,
			] );
		} else {
			wp_send_json_error( [ 'message' => 'Failed to duplicate template' ] );
		}
	}

	/**
	 * AJAX handler to toggle template active status
	 */
	public function ajax_toggle_template_status(): void {
		check_ajax_referer( 'checkmate_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( [ 'message' => 'Unauthorized' ] );
		}

		$template_id = isset( $_POST['template_id'] ) ? absint( wp_unslash( $_POST['template_id'] ) ) : 0;
		$is_active   = isset( $_POST['is_active'] ) ? (bool) absint( wp_unslash( $_POST['is_active'] ) ) : false;

		if ( ! $template_id ) {
			wp_send_json_error( [ 'message' => 'No template ID provided' ] );
		}

		$repository = TemplateRepository::instance();
		$template   = $repository->find( $template_id );

		if ( ! $template ) {
			wp_send_json_error( [ 'message' => 'Template not found' ] );
		}

		if ( $is_active ) {
			// When activating, use set_active which deactivates others of same type
			$result = $repository->set_active( $template_id );
		} else {
			// When deactivating, just update the template
			$template->set_active( false );
			$result = $repository->save( $template );
		}

		if ( $result ) {
			wp_send_json_success( [ 'message' => 'Template status updated' ] );
		} else {
			wp_send_json_error( [ 'message' => 'Failed to update template status' ] );
		}
	}

	/**
	 * AJAX handler to assign template to an email event
	 */
	public function ajax_assign_template_event(): void {
		check_ajax_referer( 'checkmate_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( [ 'message' => 'Unauthorized' ] );
		}

		$template_id = isset( $_POST['template_id'] ) ? absint( wp_unslash( $_POST['template_id'] ) ) : 0;
		$event       = isset( $_POST['event'] ) ? sanitize_text_field( wp_unslash( $_POST['event'] ) ) : '';

		if ( ! $template_id ) {
			wp_send_json_error( [ 'message' => 'No template ID provided' ] );
		}

		// Clear old assignment for this template
		delete_option( 'checkmate_template_event_' . $template_id );

		if ( ! empty( $event ) ) {
			// Remove event from any other template.
			global $wpdb;
			$option_pattern = 'checkmate_template_event_%';
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$existing = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT option_name, option_value FROM {$wpdb->options} WHERE option_name LIKE %s AND option_value = %s",
					$option_pattern,
					$event
				)
			);
			foreach ( $existing as $row ) {
				delete_option( $row->option_name );
			}

			// Save the new assignment
			update_option( 'checkmate_template_event_' . $template_id, $event, false );
		}

		wp_send_json_success( [ 'message' => 'Event assignment updated' ] );
	}

	/**
	 * AJAX handler to generate PDF
	 */
	public function ajax_generate_pdf(): void {
		// Check nonce - can come from form or query.
		$nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : '';
		if ( empty( $nonce ) ) {
			$nonce = isset( $_REQUEST['nonce'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['nonce'] ) ) : '';
		}

		if ( ! wp_verify_nonce( $nonce, 'checkmate_admin_nonce' ) ) {
			wp_send_json_error(
				[ 'message' => esc_html__( 'Security check failed. Please reload the page and try again.', 'checkmate-pdf-invoices' ) ],
				403
			);
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( [ 'message' => esc_html__( 'Unauthorized.', 'checkmate-pdf-invoices' ) ], 403 );
		}

		// The raw JSON string is unslashed here; all decoded values (blocks, page_settings, etc.)
		// are fully sanitized inside Template::hydrate() before being stored or rendered.
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- JSON string; sanitize_text_field would corrupt valid JSON.
		$template_json = isset( $_POST['template'] ) ? wp_unslash( $_POST['template'] ) : '';
		if ( empty( $template_json ) || ! is_string( $template_json ) ) {
			wp_send_json_error( [ 'message' => esc_html__( 'No template data.', 'checkmate-pdf-invoices' ) ], 400 );
		}

		// Individual values are sanitized inside Template::hydrate().
		$template_data = json_decode( $template_json, true );
		if ( json_last_error() !== JSON_ERROR_NONE ) {
			wp_send_json_error(
				[ 'message' => esc_html__( 'Invalid template JSON.', 'checkmate-pdf-invoices' ) . ' ' . esc_html( json_last_error_msg() ) ],
				400
			);
		}
		if ( ! is_array( $template_data ) ) {
			wp_send_json_error( [ 'message' => esc_html__( 'Invalid template data.', 'checkmate-pdf-invoices' ) ], 400 );
		}

		try {
			$template  = Template::from_array( $template_data );
			$renderer  = new PDFRenderer();
			$pdf_bytes = $renderer->render( $template );
		} catch ( \Throwable $e ) {
			// Log full details for debugging, but return a safe message to the UI.
			error_log( 'Checkmate PDF ajax_generate_pdf error: ' . $e->getMessage() );
			error_log( $e->getTraceAsString() );
			wp_send_json_error(
				[ 'message' => esc_html__( 'PDF generation error. Please check PHP error logs for details.', 'checkmate-pdf-invoices' ) ],
				500
			);
		}

		$filename = sanitize_file_name( $template->get_name() ?: 'document' ) . '.pdf';
		$filename = str_replace( [ '"', "\r", "\n" ], '', $filename );

		nocache_headers();
		header( 'Content-Type: application/pdf' );
		header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
		header( 'Content-Length: ' . (string) strlen( $pdf_bytes ) );
		header( 'Cache-Control: private, max-age=0, must-revalidate' );
		header( 'Pragma: public' );

		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Binary PDF content cannot be escaped
		echo $pdf_bytes;
		exit;
	}

	/**
	 * AJAX handler for PDF preview
	 */
	public function ajax_preview_pdf(): void {
		// Preview can work with nonce from form or query
		$nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : '';
		if ( empty( $nonce ) ) {
			$nonce = isset( $_GET['nonce'] ) ? sanitize_text_field( wp_unslash( $_GET['nonce'] ) ) : '';
		}

		if ( ! wp_verify_nonce( $nonce, 'checkmate_admin_nonce' ) ) {
			wp_die( esc_html__( 'Security check failed.', 'checkmate-pdf-invoices' ) );
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Unauthorized.', 'checkmate-pdf-invoices' ) );
		}

		// The raw JSON string is unslashed here; all decoded values (blocks, page_settings, etc.)
		// are fully sanitized inside Template::hydrate() before being stored or rendered.
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- JSON string; sanitize_text_field would corrupt valid JSON.
		$template_json = isset( $_POST['template'] ) ? wp_unslash( $_POST['template'] ) : '';
		if ( empty( $template_json ) || ! is_string( $template_json ) ) {
			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- JSON string; sanitize_text_field would corrupt valid JSON.
			$template_json = isset( $_GET['template'] ) ? wp_unslash( $_GET['template'] ) : '';
		}

		if ( empty( $template_json ) || ! is_string( $template_json ) ) {
			wp_die( esc_html__( 'No template data', 'checkmate-pdf-invoices' ) );
		}

		// Individual values are sanitized inside Template::hydrate().
		$template_data = json_decode( $template_json, true );
		if ( json_last_error() !== JSON_ERROR_NONE ) {
			wp_die( esc_html( 'Invalid JSON: ' . json_last_error_msg() ) );
		}
		if ( ! is_array( $template_data ) ) {
			wp_die( esc_html__( 'Invalid template data', 'checkmate-pdf-invoices' ) );
		}

		$template = Template::from_array( $template_data );

		// Generate PDF for inline view
		$this->generate_pdf_output( $template, false );
	}

	/**
	 * Generate PDF output
	 *
	 * @param Template $template  Template object.
	 * @param bool     $download  Whether to force download.
	 */
	private function generate_pdf_output( Template $template, bool $download = false ): void {
		$renderer = new PDFRenderer();

		if ( $download ) {
			// Generate actual PDF
			try {
				$pdf_content = $renderer->render( $template );

				$filename = sanitize_file_name( $template->get_name() ?: 'document' ) . '.pdf';
				$filename = str_replace( [ '"', "\r", "\n" ], '', $filename );

				header( 'Content-Type: application/pdf' );
				header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
				header( 'Content-Length: ' . strlen( $pdf_content ) );
				header( 'Cache-Control: private, max-age=0, must-revalidate' );
				header( 'Pragma: public' );

				// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Binary PDF content cannot be escaped
				echo $pdf_content;
				exit;
			} catch ( \Exception $e ) {
				wp_die( 'PDF Generation Error: ' . esc_html( $e->getMessage() ) );
			}
		} else {
			// Output HTML preview (for iframe)
			$html = $renderer->render_html( $template );
			header( 'Content-Type: text/html; charset=utf-8' );
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Full HTML document for preview, escaped internally
			echo $html;
			exit;
		}
	}
}
