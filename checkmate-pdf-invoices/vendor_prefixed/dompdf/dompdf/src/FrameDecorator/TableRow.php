<?php
/**
 * @package dompdf
 * @link    https://github.com/dompdf/dompdf
 * @license http://www.gnu.org/copyleft/lesser.html GNU Lesser General Public License
 *
 * Modified by checkmate on 14-February-2026 using {@see https://github.com/BrianHenryIE/strauss}.
 */
namespace Checkmate\Vendor\Dompdf\FrameDecorator;

use Checkmate\Vendor\Dompdf\Dompdf;
use Checkmate\Vendor\Dompdf\Frame;

/**
 * Decorates Frames for table row layout
 *
 * @package dompdf
 */
class TableRow extends AbstractFrameDecorator
{
    /**
     * TableRow constructor.
     * @param Frame $frame
     * @param Dompdf $dompdf
     */
    function __construct(Frame $frame, Dompdf $dompdf)
    {
        parent::__construct($frame, $dompdf);
    }
}
