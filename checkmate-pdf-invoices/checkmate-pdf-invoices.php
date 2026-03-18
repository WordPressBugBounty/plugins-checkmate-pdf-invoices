<?php
/**
 * Plugin Name: Checkmate PDF
 * Description: Create custom PDF Invoices and Packing Slips for WooCommerce. Includes a Visual Template Editor, HPOS support, Bulk Actions, and Email Attachments.
 * Version: 2.0.3
 * Author URI: https://kingaddons.com/
 * Author: KingAddons.com
 * License: GPLv2 or later
 * Text Domain: checkmate-pdf-invoices
 * Requires Plugins: woocommerce
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'CHECKMATE_PDF_INVOICES_PLUGIN_FILE', __FILE__ );
define( 'CHECKMATE_PDF_INVOICES_PLUGIN_DIR', __DIR__ );

define( 'CHECKMATE_PDF_INVOICES_PLUGIN_AUTOLOADER', CHECKMATE_PDF_INVOICES_PLUGIN_DIR . '/autoload.php' );
define( 'CHECKMATE_PDF_INVOICES_VENDOR_AUTOLOADER', CHECKMATE_PDF_INVOICES_PLUGIN_DIR . '/vendor_prefixed/autoload.php' );

/**
 * @return array<string, bool> Map of extension => isLoaded
 */
function checkmate_pdf_invoices_get_extension_status(): array {
	return [
		'dom' => extension_loaded( 'dom' ),
		'mbstring' => extension_loaded( 'mbstring' ),
		'gd' => extension_loaded( 'gd' ),
	];
}

/**
 * @return string[] Missing required extension names.
 */
function checkmate_pdf_invoices_get_missing_required_extensions(): array {
	$status = checkmate_pdf_invoices_get_extension_status();
	$missing = [];
	foreach ( [ 'dom', 'mbstring' ] as $ext ) {
		if ( empty( $status[ $ext ] ) ) {
			$missing[] = $ext;
		}
	}
	return $missing;
}

register_activation_hook( __FILE__, static function () {
	$missing = checkmate_pdf_invoices_get_missing_required_extensions();
	if ( ! empty( $missing ) ) {
		deactivate_plugins( plugin_basename( __FILE__ ) );
		wp_die(
			wp_kses(
				'Checkmate — PDF Invoices could not be activated.<br><br>' .
				'Missing required PHP extensions: <code>' . esc_html( implode( ', ', $missing ) ) . '</code>.<br><br>' .
				'Please enable these extensions in your hosting control panel or contact your host.',
				[ 'br' => [], 'code' => [] ]
			),
			esc_html__( 'Plugin activation failed', 'checkmate-pdf-invoices' ),
			[ 'back_link' => true ]
		);
	}

	// Load autoloaders for activation
	if ( file_exists( CHECKMATE_PDF_INVOICES_PLUGIN_AUTOLOADER ) ) {
		require_once CHECKMATE_PDF_INVOICES_PLUGIN_AUTOLOADER;
	}

	// Create database table
	\Checkmate\PdfInvoices\Editor\TemplateRepository::create_table();
} );

add_action( 'plugins_loaded', static function () {
	$missing = checkmate_pdf_invoices_get_missing_required_extensions();
	if ( ! empty( $missing ) ) {
		add_action( 'admin_notices', static function () use ( $missing ) {
			if ( ! current_user_can( 'manage_options' ) ) {
				return;
			}

			echo '<div class="notice notice-error"><p>';
			echo esc_html__( 'Checkmate PDF Invoices: missing required PHP extensions:', 'checkmate-pdf-invoices' ) . ' ';
			echo '<code>' . esc_html( implode( ', ', $missing ) ) . '</code>.';
			echo '</p><p>';
			echo esc_html__( 'These extensions are provided by the server PHP build and cannot be bundled inside the plugin. Please enable them or contact your host.', 'checkmate-pdf-invoices' );
			echo '</p></div>';
		} );
		return;
	}

	if ( file_exists( CHECKMATE_PDF_INVOICES_PLUGIN_AUTOLOADER ) ) {
		require_once CHECKMATE_PDF_INVOICES_PLUGIN_AUTOLOADER;
	}

	if ( file_exists( CHECKMATE_PDF_INVOICES_VENDOR_AUTOLOADER ) ) {
		require_once CHECKMATE_PDF_INVOICES_VENDOR_AUTOLOADER;
	}

	// Ensure database table exists (for upgrades)
	\Checkmate\PdfInvoices\Editor\TemplateRepository::create_table();

	// WooCommerce email attachments (frontend/runtime).
	if ( class_exists( 'WooCommerce' ) ) {
		\Checkmate\PdfInvoices\WooCommerce\EmailAttachments::register();
		\Checkmate\PdfInvoices\WooCommerce\Features::register();
	}

	// Load Admin Dashboard
	if ( is_admin() ) {
		require_once CHECKMATE_PDF_INVOICES_PLUGIN_DIR . '/includes/Admin/Admin.php';
		\Checkmate\PdfInvoices\Admin\Admin::instance();
	}
} );
