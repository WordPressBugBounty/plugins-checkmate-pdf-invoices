<?php
/**
 * @license MIT
 *
 * Modified by checkmate on 14-February-2026 using {@see https://github.com/BrianHenryIE/strauss}.
 */

declare(strict_types=1);

namespace Checkmate\Vendor\Sabberworm\CSS\Parsing;

use Checkmate\Vendor\Sabberworm\CSS\Position\Position;
use Checkmate\Vendor\Sabberworm\CSS\Position\Positionable;

class SourceException extends \Exception implements Positionable
{
    use Position;

    /**
     * @param int<1, max>|null $lineNumber
     */
    public function __construct(string $message, ?int $lineNumber = null)
    {
        $this->setPosition($lineNumber);
        if ($lineNumber !== null) {
            $message .= " [line no: $lineNumber]";
        }
        parent::__construct($message);
    }
}
