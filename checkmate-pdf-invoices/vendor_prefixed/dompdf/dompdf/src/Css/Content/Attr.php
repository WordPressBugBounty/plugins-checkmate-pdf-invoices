<?php
/**
 * @license LGPL-2.1
 *
 * Modified by checkmate on 14-February-2026 using {@see https://github.com/BrianHenryIE/strauss}.
 */
namespace Checkmate\Vendor\Dompdf\Css\Content;

final class Attr extends ContentPart
{
    /**
     * @var string
     */
    public $attribute;

    public function __construct(string $attribute)
    {
        $this->attribute = $attribute;
    }

    public function equals(ContentPart $other): bool
    {
        return $other instanceof self
            && $other->attribute === $this->attribute;
    }

    public function __toString(): string
    {
        return "attr($this->attribute)";
    }
}
