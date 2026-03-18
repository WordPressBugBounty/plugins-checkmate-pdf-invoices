<?php
/**
 * @license MIT
 *
 * Modified by checkmate on 14-February-2026 using {@see https://github.com/BrianHenryIE/strauss}.
 */

declare(strict_types=1);

namespace Checkmate\Vendor\Sabberworm\CSS\Value;

use Checkmate\Vendor\Sabberworm\CSS\OutputFormat;

class CalcRuleValueList extends RuleValueList
{
    /**
     * @param int<1, max>|null $lineNumber
     */
    public function __construct(?int $lineNumber = null)
    {
        parent::__construct(',', $lineNumber);
    }

    public function render(OutputFormat $outputFormat): string
    {
        return $outputFormat->getFormatter()->implode(' ', $this->components);
    }
}
