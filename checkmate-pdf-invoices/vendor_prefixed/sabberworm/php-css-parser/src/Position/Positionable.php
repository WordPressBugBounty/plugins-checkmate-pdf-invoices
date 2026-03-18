<?php
/**
 * @license MIT
 *
 * Modified by checkmate on 14-February-2026 using {@see https://github.com/BrianHenryIE/strauss}.
 */

declare(strict_types=1);

namespace Checkmate\Vendor\Sabberworm\CSS\Position;

/**
 * Represents a CSS item that may have a position in the source CSS document (line number and possibly column number).
 *
 * A standard implementation of this interface is available in the `Position` trait.
 */
interface Positionable
{
    /**
     * @return int<1, max>|null
     */
    public function getLineNumber(): ?int;

    /**
     * @return int<0, max>|null
     */
    public function getColumnNumber(): ?int;

    /**
     * @param int<1, max>|null $lineNumber
     * @param int<0, max>|null $columnNumber
     *
     * @return $this fluent interface
     */
    public function setPosition(?int $lineNumber, ?int $columnNumber = null): Positionable;
}
