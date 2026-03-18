<?php

namespace Checkmate\PdfInvoices\WooCommerce;

use Checkmate\PdfInvoices\Editor\TemplateRepository;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Features {
	/**
	 * Read file contents via WP_Filesystem.
	 */
	private static function get_file_contents( string $path ): string {
		if ( ! is_file( $path ) || ! is_readable( $path ) ) {
			return '';
		}

		require_once ABSPATH . 'wp-admin/includes/file.php';
		WP_Filesystem();

		global $wp_filesystem;
		if ( ! $wp_filesystem ) {
			return '';
		}

		$contents = $wp_filesystem->get_contents( $path );
		return is_string( $contents ) ? $contents : '';
	}

	private static function output_binary_download( string $content, string $content_type, string $filename, string $disposition = 'attachment' ): void {
		header( 'Content-Type: ' . $content_type );
		$safe_filename = str_replace( [ '"', "\r", "\n" ], '', $filename );
		header( 'Content-Disposition: ' . $disposition . '; filename="' . $safe_filename . '"' );
		header( 'Content-Length: ' . (string) strlen( $content ) );
		header( 'Cache-Control: private, max-age=0, must-revalidate' );
		header( 'Pragma: public' );

		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Binary output.
		echo $content;
		exit;
	}

	public static function register(): void {
		if ( ! class_exists( 'WooCommerce' ) ) {
			return;
		}

		add_action( 'admin_post_checkmate_download_invoice', [ __CLASS__, 'handle_download_invoice' ] );
		add_action( 'admin_post_nopriv_checkmate_download_invoice', [ __CLASS__, 'handle_download_invoice' ] );

		// Customer account links.
		add_filter( 'woocommerce_my_account_my_orders_actions', [ __CLASS__, 'add_my_account_order_action' ], 10, 2 );

		// Email link for guests/customers.
		add_action( 'woocommerce_email_after_order_table', [ __CLASS__, 'add_email_invoice_link' ], 10, 4 );

		// Admin UI.
		add_action( 'add_meta_boxes', [ __CLASS__, 'register_order_metabox' ] );
		// HPOS / new Orders screen (no post meta boxes).
		add_action( 'woocommerce_admin_order_data_after_order_details', [ __CLASS__, 'render_order_screen_buttons' ] );

		add_filter( 'bulk_actions-edit-shop_order', [ __CLASS__, 'register_bulk_actions' ] );
		add_filter( 'handle_bulk_actions-edit-shop_order', [ __CLASS__, 'handle_bulk_action' ], 10, 3 );

		add_filter( 'bulk_actions-woocommerce_page_wc-orders', [ __CLASS__, 'register_bulk_actions' ] );
		add_filter( 'handle_bulk_actions-woocommerce_page_wc-orders', [ __CLASS__, 'handle_bulk_action' ], 10, 3 );

		add_action( 'admin_post_checkmate_bulk_download_invoices', [ __CLASS__, 'handle_bulk_download_invoices' ] );

		add_filter( 'manage_edit-shop_order_columns', [ __CLASS__, 'add_invoice_number_column' ], 20 );
		add_action( 'manage_shop_order_posts_custom_column', [ __CLASS__, 'render_invoice_number_column' ], 10, 2 );

		// HPOS list table (when enabled).
		add_filter( 'woocommerce_shop_order_list_table_columns', [ __CLASS__, 'add_invoice_number_column' ], 20 );
		add_action( 'woocommerce_shop_order_list_table_custom_column', [ __CLASS__, 'render_invoice_number_column_hpos' ], 10, 2 );
	}

	public static function render_order_screen_buttons( $order ): void {
		$settings = PdfService::get_settings();
		if ( empty( $settings['admin_order_buttons'] ) ) {
			return;
		}
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}
		if ( ! ( $order instanceof \WC_Order ) ) {
			return;
		}

		$order_id = (int) $order->get_id();
		$download_url = add_query_arg(
			[
				'action'   => 'checkmate_download_invoice',
				'order_id' => $order_id,
				'mode'     => 'download',
			],
			admin_url( 'admin-post.php' )
		);
		$download_url = wp_nonce_url( $download_url, 'checkmate_invoice_admin_' . $order_id );

		$view_url = add_query_arg(
			[
				'action'   => 'checkmate_download_invoice',
				'order_id' => $order_id,
				'mode'     => 'inline',
			],
			admin_url( 'admin-post.php' )
		);
		$view_url = wp_nonce_url( $view_url, 'checkmate_invoice_admin_' . $order_id );

		echo '<div class="order_data_column checkmate-invoice-card" style="padding: 12px; border: 1px solid #dcdcde; border-radius: 4px; margin: 12px 0; background: #fff; width: 100%;">';
		echo '<h3 style="margin: 0 0 10px;">' . esc_html__( 'Invoice PDF (Checkmate)', 'checkmate-pdf-invoices' ) . '</h3>';
		echo '<div class="checkmate-invoice-actions" style="display: flex; gap: 8px; flex-wrap: wrap; align-items: center;">';
		echo '<a class="button button-primary" href="' . esc_url( $download_url ) . '">' . esc_html__( 'Download PDF', 'checkmate-pdf-invoices' ) . '</a>';
		echo '<a class="button" target="_blank" rel="noopener noreferrer" href="' . esc_url( $view_url ) . '">' . esc_html__( 'View / Print', 'checkmate-pdf-invoices' ) . '</a>';
		echo '</div>';
		echo '</div>';
	}

	/**
	 * @param array<string, array<string, string>> $actions
	 */
	public static function add_my_account_order_action( array $actions, \WC_Order $order ): array {
		$settings = PdfService::get_settings();
		if ( empty( $settings['customer_access'] ) ) {
			return $actions;
		}

		if ( ! is_user_logged_in() ) {
			return $actions;
		}

		$user_id = get_current_user_id();
		if ( (int) $order->get_user_id() !== (int) $user_id ) {
			return $actions;
		}

		$url = add_query_arg(
			[
				'action'   => 'checkmate_download_invoice',
				'order_id' => (int) $order->get_id(),
				'mode'     => 'download',
			],
			admin_url( 'admin-post.php' )
		);

		$actions['checkmate_invoice'] = [
			'url'  => $url,
			'name' => __( 'Invoice PDF', 'checkmate-pdf-invoices' ),
		];

		return $actions;
	}

	/**
	 * Add invoice link to customer emails (optionally supports guest access via order key).
	 */
	public static function add_email_invoice_link( $order, bool $sent_to_admin, bool $plain_text, $email ): void {
		if ( $sent_to_admin ) {
			return;
		}
		if ( ! ( $order instanceof \WC_Order ) ) {
			return;
		}

		$settings = PdfService::get_settings();
		$customer_access = ! empty( $settings['customer_access'] );
		$guest_access = ! empty( $settings['guest_access'] );

		// If neither path is allowed, skip.
		if ( ! $customer_access && ! $guest_access ) {
			return;
		}

		$link_args = [
			'action'   => 'checkmate_download_invoice',
			'order_id' => (int) $order->get_id(),
			'mode'     => 'download',
		];

		// Guests authenticate via order key.
		if ( $guest_access ) {
			$link_args['key'] = (string) $order->get_order_key();
		}

		$url = add_query_arg( $link_args, admin_url( 'admin-post.php' ) );

		if ( $plain_text ) {
			echo "\n" . esc_html__( 'Download your invoice PDF:', 'checkmate-pdf-invoices' ) . "\n";
			echo esc_url_raw( $url ) . "\n";
			return;
		}

		echo '<p>' . esc_html__( 'Download your invoice PDF:', 'checkmate-pdf-invoices' ) . ' ';
		echo '<a href="' . esc_url( $url ) . '">' . esc_html__( 'Download', 'checkmate-pdf-invoices' ) . '</a>';
		echo '</p>';
	}

	public static function register_order_metabox(): void {
		$settings = PdfService::get_settings();
		if ( empty( $settings['admin_order_buttons'] ) ) {
			return;
		}

		add_meta_box(
			'checkmate_invoice_pdf',
			esc_html__( 'Invoice PDF (Checkmate)', 'checkmate-pdf-invoices' ),
			[ __CLASS__, 'render_order_metabox' ],
			'shop_order',
			'side',
			'high'
		);
	}

	public static function render_order_metabox( \WP_Post $post ): void {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}

		$order_id = (int) $post->ID;
		$download_url = add_query_arg(
			[
				'action'   => 'checkmate_download_invoice',
				'order_id' => $order_id,
				'mode'     => 'download',
			],
			admin_url( 'admin-post.php' )
		);
		$download_url = wp_nonce_url( $download_url, 'checkmate_invoice_admin_' . $order_id );

		$view_url = add_query_arg(
			[
				'action'   => 'checkmate_download_invoice',
				'order_id' => $order_id,
				'mode'     => 'inline',
			],
			admin_url( 'admin-post.php' )
		);
		$view_url = wp_nonce_url( $view_url, 'checkmate_invoice_admin_' . $order_id );

		echo '<div class="checkmate-invoice-card" style="padding: 10px; border: 1px solid #dcdcde; border-radius: 10px; background: #fff;">';
		echo '<div class="checkmate-invoice-actions" style="display: flex; gap: 8px; flex-wrap: wrap; align-items: center;">';
		echo '<a class="button button-primary" href="' . esc_url( $download_url ) . '">' . esc_html__( 'Download PDF', 'checkmate-pdf-invoices' ) . '</a>';
		echo '<a class="button" target="_blank" rel="noopener noreferrer" href="' . esc_url( $view_url ) . '">' . esc_html__( 'View / Print', 'checkmate-pdf-invoices' ) . '</a>';
		echo '</div>';
		echo '</div>';
	}

	/**
	 * Download/inline invoice handler for admin, customers, and guests.
	 */
	public static function handle_download_invoice(): void {
		if ( ! function_exists( 'wc_get_order' ) ) {
			wp_die( esc_html__( 'WooCommerce is not available.', 'checkmate-pdf-invoices' ) );
		}

		$order_id = isset( $_GET['order_id'] ) ? absint( wp_unslash( $_GET['order_id'] ) ) : 0;
		$mode = isset( $_GET['mode'] ) ? sanitize_key( (string) wp_unslash( $_GET['mode'] ) ) : 'download';
		$key = isset( $_GET['key'] ) ? sanitize_text_field( (string) wp_unslash( $_GET['key'] ) ) : '';

		if ( ! $order_id ) {
			wp_die( esc_html__( 'Missing order id.', 'checkmate-pdf-invoices' ) );
		}

		$order = wc_get_order( $order_id );
		if ( ! ( $order instanceof \WC_Order ) ) {
			wp_die( esc_html__( 'Order not found.', 'checkmate-pdf-invoices' ) );
		}

		$settings = PdfService::get_settings();

		// Admin access.
		if ( is_user_logged_in() && current_user_can( 'manage_woocommerce' ) ) {
			check_admin_referer( 'checkmate_invoice_admin_' . $order_id );
		} elseif ( is_user_logged_in() ) {
			if ( empty( $settings['customer_access'] ) ) {
				wp_die( esc_html__( 'Invoice downloads are disabled.', 'checkmate-pdf-invoices' ), 403 );
			}
			if ( (int) $order->get_user_id() !== (int) get_current_user_id() ) {
				wp_die( esc_html__( 'Unauthorized.', 'checkmate-pdf-invoices' ), 403 );
			}
		} else {
			// Guest path.
			if ( empty( $settings['guest_access'] ) ) {
				wp_die( esc_html__( 'Guest invoice downloads are disabled.', 'checkmate-pdf-invoices' ), 403 );
			}
			if ( $key === '' || ! hash_equals( (string) $order->get_order_key(), (string) $key ) ) {
				wp_die( esc_html__( 'Invalid download link.', 'checkmate-pdf-invoices' ), 403 );
			}
		}

		$repository = TemplateRepository::instance();
		$template = $repository->find_active( 'invoice' );
		if ( ! $template ) {
			wp_die( esc_html__( 'No active invoice template found.', 'checkmate-pdf-invoices' ) );
		}

		$file_path = PdfService::render_pdf_to_file( $template, $order );
		if ( $file_path === '' || ! file_exists( $file_path ) ) {
			wp_die( esc_html__( 'Failed to generate PDF.', 'checkmate-pdf-invoices' ) );
		}

		$filename = basename( $file_path );
		$disposition = ( $mode === 'inline' ) ? 'inline' : 'attachment';
		$pdf_content = self::get_file_contents( $file_path );
		if ( $pdf_content === '' ) {
			wp_die( esc_html__( 'Failed to read PDF file.', 'checkmate-pdf-invoices' ) );
		}

		self::output_binary_download( $pdf_content, 'application/pdf', $filename, $disposition );
	}

	/**
	 * @param array<string, string> $bulk_actions
	 * @return array<string, string>
	 */
	public static function register_bulk_actions( array $bulk_actions ): array {
		$settings = PdfService::get_settings();
		if ( empty( $settings['admin_bulk_actions'] ) ) {
			return $bulk_actions;
		}
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return $bulk_actions;
		}

		$bulk_actions['checkmate_download_invoices'] = __( 'Download invoices (PDF, ZIP)', 'checkmate-pdf-invoices' );
		return $bulk_actions;
	}

	/**
	 * @param string $redirect_url
	 * @param string $action
	 * @param int[]  $post_ids
	 */
	public static function handle_bulk_action( string $redirect_url, string $action, array $post_ids ): string {
		if ( $action !== 'checkmate_download_invoices' ) {
			return $redirect_url;
		}

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return add_query_arg( 'checkmate_bulk_error', 'cap', $redirect_url );
		}

		$post_ids = array_values( array_filter( array_map( 'absint', $post_ids ) ) );
		if ( empty( $post_ids ) ) {
			return add_query_arg( 'checkmate_bulk_error', 'empty', $redirect_url );
		}

		$token = wp_generate_password( 20, false, false );
		set_transient( 'checkmate_bulk_' . $token, $post_ids, 10 * MINUTE_IN_SECONDS );

		$url = add_query_arg(
			[
				'action' => 'checkmate_bulk_download_invoices',
				'token'  => $token,
			],
			admin_url( 'admin-post.php' )
		);
		$url = wp_nonce_url( $url, 'checkmate_bulk_download_invoices' );

		return $url;
	}

	public static function handle_bulk_download_invoices(): void {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'Unauthorized.', 'checkmate-pdf-invoices' ), 403 );
		}

		// Verify nonce when present, but don't hard-fail with the generic "link expired" page.
		// Bulk downloads are already protected by capability checks + a short-lived transient token.
		$nonce = '';
		if ( isset( $_REQUEST['_wpnonce'] ) ) {
			$nonce = sanitize_text_field( wp_unslash( $_REQUEST['_wpnonce'] ) );
		} elseif ( isset( $_REQUEST['amp;_wpnonce'] ) ) {
			// When a URL is copied from HTML (with &amp;), PHP may parse the key as "amp;_wpnonce".
			$nonce = sanitize_text_field( wp_unslash( $_REQUEST['amp;_wpnonce'] ) );
		}
		if ( $nonce === '' || ! wp_verify_nonce( $nonce, 'checkmate_bulk_download_invoices' ) ) {
			wp_die( esc_html__( 'Security check failed.', 'checkmate-pdf-invoices' ), 403 );
		}

		$token = '';
		if ( isset( $_REQUEST['token'] ) ) {
			$token = sanitize_text_field( (string) wp_unslash( $_REQUEST['token'] ) );
		} elseif ( isset( $_REQUEST['amp;token'] ) ) {
			// When a URL is copied from HTML (with &amp;), PHP may parse the key as "amp;token".
			$token = sanitize_text_field( (string) wp_unslash( $_REQUEST['amp;token'] ) );
		}
		if ( $token === '' ) {
			wp_die( esc_html__( 'Missing token.', 'checkmate-pdf-invoices' ) );
		}

		$order_ids = get_transient( 'checkmate_bulk_' . $token );
		delete_transient( 'checkmate_bulk_' . $token );

		if ( ! is_array( $order_ids ) || empty( $order_ids ) ) {
			wp_die( esc_html__( 'Nothing to download.', 'checkmate-pdf-invoices' ) );
		}

		if ( ! class_exists( '\\ZipArchive' ) ) {
			wp_die( esc_html__( 'ZIP extension is not available on this server.', 'checkmate-pdf-invoices' ) );
		}

		$repository = TemplateRepository::instance();
		$template = $repository->find_active( 'invoice' );
		if ( ! $template ) {
			wp_die( esc_html__( 'No active invoice template found.', 'checkmate-pdf-invoices' ) );
		}

		// Create zip in a temp file.
		$tmp = wp_tempnam( 'checkmate-invoices' );
		if ( ! $tmp ) {
			wp_die( esc_html__( 'Failed to create temp file.', 'checkmate-pdf-invoices' ) );
		}

		$zip = new \ZipArchive();
		if ( true !== $zip->open( $tmp, \ZipArchive::OVERWRITE ) ) {
			wp_die( esc_html__( 'Failed to create ZIP.', 'checkmate-pdf-invoices' ) );
		}

		$max = 50;
		$count = 0;
		foreach ( $order_ids as $order_id ) {
			$order_id = absint( $order_id );
			if ( ! $order_id ) {
				continue;
			}
			if ( $count >= $max ) {
				break;
			}

			$order = wc_get_order( $order_id );
			if ( ! ( $order instanceof \WC_Order ) ) {
				continue;
			}

			$file_path = PdfService::render_pdf_to_file( $template, $order );
			if ( $file_path === '' || ! file_exists( $file_path ) ) {
				continue;
			}

			$zip->addFile( $file_path, basename( $file_path ) );
			$count++;
		}

		$zip->close();

		$zip_name = 'checkmate-invoices-' . gmdate( 'Y-m-d' ) . '.zip';
		$zip_content = self::get_file_contents( $tmp );
		wp_delete_file( $tmp );
		if ( $zip_content === '' ) {
			wp_die( esc_html__( 'Failed to read ZIP file.', 'checkmate-pdf-invoices' ) );
		}

		self::output_binary_download( $zip_content, 'application/zip', $zip_name, 'attachment' );
	}

	/**
	 * @param array<string, string> $columns
	 * @return array<string, string>
	 */
	public static function add_invoice_number_column( array $columns ): array {
		$settings = PdfService::get_settings();
		if ( empty( $settings['admin_order_column'] ) ) {
			return $columns;
		}

		$new = [];
		foreach ( $columns as $key => $label ) {
			$new[ $key ] = $label;
			if ( $key === 'order_total' ) {
				$new['checkmate_invoice_number'] = __( 'Invoice #', 'checkmate-pdf-invoices' );
			}
		}

		if ( ! isset( $new['checkmate_invoice_number'] ) ) {
			$new['checkmate_invoice_number'] = __( 'Invoice #', 'checkmate-pdf-invoices' );
		}

		return $new;
	}

	public static function render_invoice_number_column( string $column, int $post_id ): void {
		if ( $column !== 'checkmate_invoice_number' ) {
			return;
		}

		$order = function_exists( 'wc_get_order' ) ? wc_get_order( $post_id ) : null;
		if ( ! ( $order instanceof \WC_Order ) ) {
			echo esc_html__( '—', 'checkmate-pdf-invoices' );
			return;
		}

		$number = (string) $order->get_meta( '_checkmate_invoice_number', true );
		echo $number !== '' ? esc_html( $number ) : esc_html__( '—', 'checkmate-pdf-invoices' );
	}

	public static function render_invoice_number_column_hpos( string $column, $order ): void {
		if ( $column !== 'checkmate_invoice_number' ) {
			return;
		}
		if ( ! ( $order instanceof \WC_Order ) ) {
			echo esc_html__( '—', 'checkmate-pdf-invoices' );
			return;
		}

		$number = (string) $order->get_meta( '_checkmate_invoice_number', true );
		echo $number !== '' ? esc_html( $number ) : esc_html__( '—', 'checkmate-pdf-invoices' );
	}
}
