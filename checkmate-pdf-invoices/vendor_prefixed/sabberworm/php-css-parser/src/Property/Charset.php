<?php
/**
 * @license MIT
 *
 * Modified by checkmate on 14-February-2026 using {@see https://github.com/BrianHenryIE/strauss}.
 */

declare(strict_types=1);

namespace Checkmate\Vendor\Sabberworm\CSS\Property;

use Checkmate\Vendor\Sabberworm\CSS\Comment\CommentContainer;
use Checkmate\Vendor\Sabberworm\CSS\OutputFormat;
use Checkmate\Vendor\Sabberworm\CSS\Position\Position;
use Checkmate\Vendor\Sabberworm\CSS\Position\Positionable;
use Checkmate\Vendor\Sabberworm\CSS\Value\CSSString;

/**
 * Class representing an `@charset` rule.
 *
 * The following restrictions apply:
 * - May not be found in any CSSList other than the Document.
 * - May only appear at the very top of a Document’s contents.
 * - Must not appear more than once.
 */
class Charset implements AtRule, Positionable
{
    use CommentContainer;
    use Position;

    /**
     * @var CSSString
     */
    private $charset;

    /**
     * @param int<1, max>|null $lineNumber
     */
    public function __construct(CSSString $charset, ?int $lineNumber = null)
    {
        $this->charset = $charset;
        $this->setPosition($lineNumber);
    }

    /**
     * @param string|CSSString $charset
     */
    public function setCharset($charset): void
    {
        $charset = $charset instanceof CSSString ? $charset : new CSSString($charset);
        $this->charset = $charset;
    }

    public function getCharset(): string
    {
        return $this->charset->getString();
    }

    /**
     * @return non-empty-string
     */
    public function render(OutputFormat $outputFormat): string
    {
        return "{$outputFormat->getFormatter()->comments($this)}@charset {$this->charset->render($outputFormat)};";
    }

    /**
     * @return non-empty-string
     */
    public function atRuleName(): string
    {
        return 'charset';
    }

    public function atRuleArgs(): CSSString
    {
        return $this->charset;
    }
}
