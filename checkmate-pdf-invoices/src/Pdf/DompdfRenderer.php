<?php

declare(strict_types=1);

namespace Checkmate\PdfInvoices\Pdf;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Checkmate\Vendor\Dompdf\Dompdf;
use Checkmate\Vendor\Dompdf\Options;

final class DompdfRenderer
{
	/**
	 * @return string Raw PDF bytes
	 */
	public function render(string $html, string $paper = 'A4', string $orientation = 'portrait'): string
	{
		$options = new Options();
		$options->set('isRemoteEnabled', false);
		$options->set('isHtml5ParserEnabled', true);
		$options->set('defaultMediaType', 'print');

		$dompdf = new Dompdf($options);
		$dompdf->setPaper($paper, $orientation);
		$dompdf->loadHtml($html, 'UTF-8');
		$dompdf->render();

		return $dompdf->output();
	}
}
