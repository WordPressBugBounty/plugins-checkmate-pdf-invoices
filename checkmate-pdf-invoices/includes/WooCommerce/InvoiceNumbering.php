<?php

namespace Checkmate\PdfInvoices\WooCommerce;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class InvoiceNumbering {
	/**
	 * Generate the next invoice number and increment the counter.
	 */
	public static function next_invoice_number(): string {
		$settings = PdfService::get_settings();

		$reset_yearly = ! empty( $settings['invoice_reset_yearly'] );
		$year = (int) wp_date( 'Y' );
		$last_year = isset( $settings['invoice_last_year'] ) ? (int) $settings['invoice_last_year'] : 0;

		if ( $reset_yearly && $last_year > 0 && $last_year !== $year ) {
			$settings['invoice_next_number'] = 1;
		}

		$next = isset( $settings['invoice_next_number'] ) ? max( 1, (int) $settings['invoice_next_number'] ) : 1;
		$padding = isset( $settings['invoice_number_padding'] ) ? max( 0, (int) $settings['invoice_number_padding'] ) : 5;
		$format = isset( $settings['invoice_number_format'] ) ? (string) $settings['invoice_number_format'] : 'INV-{year}-{number}';

		$number_part = $padding > 0 ? str_pad( (string) $next, $padding, '0', STR_PAD_LEFT ) : (string) $next;
		$invoice_number = str_replace(
			[ '{year}', '{month}', '{day}', '{number}' ],
			[ wp_date( 'Y' ), wp_date( 'm' ), wp_date( 'd' ), $number_part ],
			$format
		);
		$invoice_number = sanitize_text_field( $invoice_number );

		$settings['invoice_next_number'] = $next + 1;
		if ( $reset_yearly ) {
			$settings['invoice_last_year'] = $year;
		}

		update_option( 'checkmate_pdf_settings', $settings, false );

		return $invoice_number;
	}
}
