<?php
/**
 * @license LGPL-2.1-or-later
 *
 * Modified by checkmate on 14-February-2026 using {@see https://github.com/BrianHenryIE/strauss}.
 */

namespace Checkmate\Vendor\FontLib\Exception;

class FontNotFoundException extends \Exception
{
    public function __construct($fontPath)
    {
        $this->message = 'Font not found in: ' . $fontPath;
    }
}