<?php

namespace Checkmate\PdfInvoices\WooCommerce;

use Checkmate\PdfInvoices\Editor\Template;
use Checkmate\PdfInvoices\Renderer\PDFRenderer;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class PdfService {
	/**
	 * @return array<string, mixed>
	 */
	public static function get_settings(): array {
		$defaults = [
			'invoice_number_format'  => 'INV-{year}-{number}',
			'invoice_next_number'    => 1,
			'invoice_number_padding' => 5,
			'invoice_reset_yearly'   => false,
			'packing_slip_prefix'    => 'PS-{year}-{number}',
			'credit_note_prefix'     => 'CN-{year}-{number}',
			'storage_enabled'        => false,
			'storage_path'           => 'checkmate-pdf-invoices',
			'auto_generate'          => true,
			'customer_access'        => true,
			'guest_access'           => false,
			'admin_order_buttons'    => true,
			'admin_bulk_actions'     => true,
			'admin_order_column'     => false,
			'debug_mode'             => false,
			'cache_enabled'          => true,
			'cleanup_days'           => 30,
		];

		$settings = get_option( 'checkmate_pdf_settings', [] );
		return wp_parse_args( is_array( $settings ) ? $settings : [], $defaults );
	}

	public static function get_storage_dir(): string {
		$settings = self::get_settings();
		$upload = wp_upload_dir();
		$base = ( is_array( $upload ) && ! empty( $upload['basedir'] ) && is_string( $upload['basedir'] ) ) ? $upload['basedir'] : WP_CONTENT_DIR;

		$folder = isset( $settings['storage_path'] ) ? sanitize_file_name( (string) $settings['storage_path'] ) : 'checkmate-pdf-invoices';
		if ( $folder === '' ) {
			$folder = 'checkmate-pdf-invoices';
		}

		return trailingslashit( $base ) . $folder;
	}

	public static function ensure_storage_dir(): bool {
		$dir = self::get_storage_dir();
		return (bool) wp_mkdir_p( $dir );
	}

	/**
	 * Ensure invoice number/date exist and return them.
	 *
	 * @return array{number: string, date_gmt: string}
	 */
	public static function ensure_invoice_meta( \WC_Order $order ): array {
		$existing_number = (string) $order->get_meta( '_checkmate_invoice_number', true );
		$existing_date = (string) $order->get_meta( '_checkmate_invoice_date', true );

		if ( $existing_number !== '' && $existing_date !== '' ) {
			return [ 'number' => $existing_number, 'date_gmt' => $existing_date ];
		}

		$number = $existing_number !== '' ? $existing_number : InvoiceNumbering::next_invoice_number();
		$date_gmt = $existing_date !== '' ? $existing_date : gmdate( 'Y-m-d H:i:s' );

		$order->update_meta_data( '_checkmate_invoice_number', $number );
		$order->update_meta_data( '_checkmate_invoice_date', $date_gmt );
		$order->save();

		return [ 'number' => $number, 'date_gmt' => $date_gmt ];
	}

	/**
	 * Build the `order_data` array that PDFRenderer expects.
	 *
	 * @return array<string, mixed>
	 */
	public static function build_order_data( \WC_Order $order, string $document_type = 'invoice' ): array {
		$currency = $order->get_currency();
		$date_created = $order->get_date_created();
		$order_date_gmt = $date_created ? $date_created->date( 'Y-m-d H:i:s' ) : gmdate( 'Y-m-d H:i:s' );

		$billing_name = trim( $order->get_formatted_billing_full_name() );
		$shipping_name = trim( $order->get_formatted_shipping_full_name() );

		$billing_address = self::build_address_lines(
			$order->get_billing_address_1(),
			$order->get_billing_address_2(),
			$order->get_billing_city(),
			$order->get_billing_state(),
			$order->get_billing_postcode(),
			$order->get_billing_country()
		);
		$shipping_address = self::build_address_lines(
			$order->get_shipping_address_1(),
			$order->get_shipping_address_2(),
			$order->get_shipping_city(),
			$order->get_shipping_state(),
			$order->get_shipping_postcode(),
			$order->get_shipping_country()
		);

		$items = [];
		$items_subtotal = 0.0;
		foreach ( $order->get_items() as $item ) {
			if ( ! $item instanceof \WC_Order_Item_Product ) {
				continue;
			}

			$product = $item->get_product();
			$sku = $product ? (string) $product->get_sku() : '';

			$qty_int = (int) ( $item->get_quantity() ?? 0 );
			$qty = (string) $qty_int;
			$line_subtotal = (float) $item->get_subtotal();
			$line_total = (float) $item->get_total();
			$line_tax = (float) $item->get_total_tax();
			$items_subtotal += $line_subtotal;

			$unit_price = $qty_int > 0 ? ( $line_subtotal / $qty_int ) : $line_subtotal;

			$items[] = [
				'product'  => esc_html( $item->get_name() ),
				'sku'      => esc_html( $sku ),
				'quantity' => esc_html( $qty ),
				'price'    => esc_html( self::format_money_plain( $unit_price, $currency ) ),
				'total'    => esc_html( self::format_money_plain( $line_total, $currency ) ),
				'weight'   => '',
				'tax'      => esc_html( self::format_money_plain( $line_tax, $currency ) ),
			];
		}

		$shipping_total = (float) $order->get_shipping_total();
		$discount_total = (float) $order->get_discount_total() + (float) $order->get_discount_tax();
		$tax_total = (float) $order->get_total_tax();
		$total = (float) $order->get_total();

		$totals = [
			'subtotal' => self::format_money_plain( $items_subtotal, $currency ),
			'shipping' => self::format_money_plain( $shipping_total, $currency ),
			'discount' => $discount_total > 0 ? ( '-' . self::format_money_plain( $discount_total, $currency ) ) : '',
			'tax'      => self::format_money_plain( $tax_total, $currency ),
			'total'    => self::format_money_plain( $total, $currency ),
		];

		$shipping_method = method_exists( $order, 'get_shipping_method' ) ? (string) $order->get_shipping_method() : '';
		$payment_method = method_exists( $order, 'get_payment_method_title' ) ? (string) $order->get_payment_method_title() : '';

		$invoice = ( $document_type === 'invoice' ) ? self::ensure_invoice_meta( $order ) : [ 'number' => '', 'date_gmt' => '' ];

		return [
			'order_number'    => (string) $order->get_order_number(),
			'order_date'      => $order_date_gmt,
			'payment_method'  => $payment_method,
			'shipping_method' => $shipping_method,
			'customer_note'   => (string) $order->get_customer_note(),

			'document_number' => $invoice['number'] !== '' ? $invoice['number'] : ( '#DOC-' . (string) $order->get_order_number() ),
			'document_date'   => $invoice['date_gmt'] !== '' ? $invoice['date_gmt'] : $order_date_gmt,

			'billing_address' => [
				'name'    => $billing_name !== '' ? $billing_name : trim( (string) $order->get_billing_first_name() . ' ' . (string) $order->get_billing_last_name() ),
				'address' => $billing_address,
				'email'   => (string) $order->get_billing_email(),
				'phone'   => (string) $order->get_billing_phone(),
			],
			'shipping_address' => [
				'name'    => $shipping_name !== '' ? $shipping_name : trim( (string) $order->get_shipping_first_name() . ' ' . (string) $order->get_shipping_last_name() ),
				'address' => $shipping_address,
				'email'   => '',
				'phone'   => '',
			],

			'items'  => $items,
			'totals' => $totals,
		];
	}

	/**
	 * Render a PDF to a file and return its absolute path.
	 */
	public static function render_pdf_to_file( Template $template, \WC_Order $order, int $template_id = 0 ): string {
		if ( ! self::ensure_storage_dir() ) {
			return '';
		}

		$document_type = (string) $template->get_document_type();
		$order_data = self::build_order_data( $order, $document_type );

		$invoice_number = (string) ( $order_data['document_number'] ?? '' );
		$safe_invoice_number = $invoice_number !== '' ? preg_replace( '/[^A-Za-z0-9\-_]+/', '-', $invoice_number ) : '';
		$safe_invoice_number = is_string( $safe_invoice_number ) ? trim( $safe_invoice_number, '-' ) : '';

		$filename = sprintf(
			'checkmate-%s-order-%d%s%s.pdf',
			sanitize_key( $document_type ),
			(int) $order->get_id(),
			$safe_invoice_number !== '' ? '-' : '',
			$safe_invoice_number !== '' ? $safe_invoice_number : ( $template_id ? 'template-' . (int) $template_id : 'document' )
		);

		$file_path = trailingslashit( self::get_storage_dir() ) . sanitize_file_name( $filename );

		$renderer = new PDFRenderer();
		$pdf = $renderer->render( $template, $order_data );
		if ( ! is_string( $pdf ) || $pdf === '' ) {
			return '';
		}

		global $wp_filesystem;
		if ( empty( $wp_filesystem ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
			WP_Filesystem();
		}

		$result = $wp_filesystem->put_contents( $file_path, $pdf, FS_CHMOD_FILE );
		if ( ! $result ) {
			return '';
		}

		return $file_path;
	}

	private static function build_address_lines( string $a1, string $a2, string $city, string $state, string $postcode, string $country ): string {
		$lines = [];
		if ( $a1 !== '' ) {
			$lines[] = $a1;
		}
		if ( $a2 !== '' ) {
			$lines[] = $a2;
		}
		$city_line = trim( $city . ( $state !== '' ? ', ' . $state : '' ) . ( $postcode !== '' ? ' ' . $postcode : '' ) );
		if ( $city_line !== '' ) {
			$lines[] = $city_line;
		}
		if ( $country !== '' ) {
			$country_name = $country;
			if ( function_exists( 'WC' ) && WC()->countries && isset( WC()->countries->countries[ $country ] ) ) {
				$country_name = (string) WC()->countries->countries[ $country ];
			}
			$lines[] = $country_name;
		}

		return trim( implode( "\n", array_filter( array_map( 'trim', $lines ) ) ) );
	}

	private static function format_money_plain( float $amount, string $currency ): string {
		if ( function_exists( 'wc_price' ) ) {
			$html = wc_price( $amount, [ 'currency' => $currency ] );
			return html_entity_decode( wp_strip_all_tags( (string) $html ), ENT_QUOTES );
		}

		return number_format_i18n( $amount, 2 );
	}
}
