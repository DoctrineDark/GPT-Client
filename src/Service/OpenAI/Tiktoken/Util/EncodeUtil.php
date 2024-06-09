<?php

namespace App\Service\OpenAI\Tiktoken\Util;

use function array_map;
use function bin2hex;
use function hexdec;
use function pack;
use function str_split;

final class EncodeUtil
{
    public static function toBytes(string $text): array
    {
        return array_map('hexdec', str_split(bin2hex($text), 2));
    }

    public static function fromBytes(array $bytes): string
    {
        return pack('C*', ...$bytes);
    }
}
