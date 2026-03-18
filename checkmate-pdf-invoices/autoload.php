<?php
/**
 * Minimal autoloader for this plugin's own PHP classes.
 *
 * We intentionally do NOT rely on Composer autoload at runtime to avoid
 * registering unprefixed third-party dependencies.
 *
 * @package Checkmate\\PdfInvoices
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

spl_autoload_register(static function (string $class): void {
	$prefix = 'Checkmate\\PdfInvoices\\';
	if (strncmp($class, $prefix, strlen($prefix)) !== 0) {
		return;
	}

	$relative = substr($class, strlen($prefix));
	$relativePath = str_replace('\\', '/', $relative) . '.php';
	
	// Try /includes/ first (where our classes are)
	$file = __DIR__ . '/includes/' . $relativePath;
	if (is_file($file)) {
		require_once $file;
		return;
	}
	
	// Fallback to /src/ for legacy support
	$file = __DIR__ . '/src/' . $relativePath;
	if (is_file($file)) {
		require_once $file;
	}
});
