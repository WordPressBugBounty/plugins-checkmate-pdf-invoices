<?php

namespace Checkmate\PdfInvoices\WooCommerce;

use Checkmate\PdfInvoices\Editor\TemplateRepository;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class EmailAttachments {
	/**
	 * Register WooCommerce email attachment hooks.
	 */
	public static function register(): void {
		if ( ! class_exists( 'WooCommerce' ) ) {
			return;
		}

		add_filter( 'woocommerce_email_attachments', [ __CLASS__, 'filter_email_attachments' ], 10, 4 );
	}

	/**
	 * @param string[] $attachments
	 * @param string   $email_id
	 * @param mixed    $object
	 * @param mixed    $email
	 *
	 * @return string[]
	 */
	public static function filter_email_attachments( array $attachments, string $email_id, $object, $email ): array {
		if ( $email_id === '' ) {
			return $attachments;
		}

		$order = self::extract_order( $object );
		if ( ! $order ) {
			return $attachments;
		}

		$template_id = self::find_template_id_for_event( $email_id );
		if ( ! $template_id ) {
			return $attachments;
		}

		$repository = TemplateRepository::instance();
		$template = $repository->find( $template_id );
		if ( ! $template ) {
			return $attachments;
		}

		try {
			$file_path = PdfService::render_pdf_to_file( $template, $order, $template_id );
			if ( $file_path ) {
				$attachments[] = $file_path;
			}
		} catch ( \Throwable $e ) {
			// Intentionally swallow errors to avoid breaking checkout/emails.
			// Site owners can debug via their server error logs if needed.
			return $attachments;
		}

		return $attachments;
	}

	/**
	 * @param mixed $object
	 *
	 * @return \WC_Order|null
	 */
	private static function extract_order( $object ) {
		if ( $object instanceof \WC_Order ) {
			return $object;
		}

		// Some emails pass the order ID.
		if ( is_numeric( $object ) && function_exists( 'wc_get_order' ) ) {
			$order = wc_get_order( (int) $object );
			return ( $order instanceof \WC_Order ) ? $order : null;
		}

		// Some emails pass a WC_Order_Refund, but we still want the parent order when possible.
		if ( $object instanceof \WC_Order_Refund ) {
			$parent_id = $object->get_parent_id();
			if ( $parent_id && function_exists( 'wc_get_order' ) ) {
				$order = wc_get_order( $parent_id );
				return ( $order instanceof \WC_Order ) ? $order : null;
			}
		}

		return null;
	}

	/**
	 * Find the template id assigned to a given WooCommerce email id.
	 */
	private static function find_template_id_for_event( string $email_id ): int {
		global $wpdb;

		$option_pattern = 'checkmate_template_event_%';
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$option_name = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE %s AND option_value = %s LIMIT 1",
				$option_pattern,
				$email_id
			)
		);

		if ( ! is_string( $option_name ) || $option_name === '' ) {
			return 0;
		}

		// option_name looks like: checkmate_template_event_{template_id}
		if ( preg_match( '/^checkmate_template_event_(\d+)$/', $option_name, $m ) ) {
			return (int) $m[1];
		}

		return 0;
	}

	// Pdf generation and order-data mapping are handled by PdfService.
}
