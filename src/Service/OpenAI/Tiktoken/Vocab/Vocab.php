<?php

declare(strict_types=1);

namespace App\Service\OpenAI\Tiktoken\Vocab;

use Countable;
use InvalidArgumentException;
use OutOfBoundsException;
use RuntimeException;
use App\Service\OpenAI\Tiktoken\Exception\ParseError;
use App\Service\OpenAI\Tiktoken\Util\EncodeUtil;

use function array_flip;
use function array_map;
use function assert;
use function base64_decode;
use function count;
use function explode;
use function fclose;
use function fgets;
use function file_exists;
use function fopen;
use function implode;
use function rewind;
use function sprintf;
use function stream_get_meta_data;
use function strval;

class Vocab implements Countable
{
    private $tokenToRankMap;
    private $rankToTokenMap;

    private function __construct(array $tokenRankMap)
    {
        $this->tokenToRankMap = $tokenRankMap;
        $this->rankToTokenMap = array_map('strval', array_flip($tokenRankMap));

        if (\count($this->tokenToRankMap) !== \count($this->rankToTokenMap)) {
            throw new InvalidArgumentException('The map of tokens and ranks has duplicates of rank');
        }
    }

    public static function fromFile(string $bpeFile): self
    {
        if (! file_exists($bpeFile)) {
            throw new RuntimeException(sprintf('File "%s" does not exist', $bpeFile));
        }

        $stream = fopen($bpeFile, 'rb');

        if ($stream === false) {
            throw new RuntimeException(sprintf('Could not open file: %s', $bpeFile));
        }

        try {
            return self::fromStream($stream);
        } finally {
            fclose($stream);
        }
    }

    public static function fromStream($stream): self
    {
        $meta = stream_get_meta_data($stream);

        if ($meta['seekable']) {
            rewind($stream);
        }

        $line = fgets($stream);
        $lineNo = 1;
        $map = [];

        while ($line !== false) {
            [$encodedToken, $rank] = explode(' ', $line);
            $token = base64_decode($encodedToken, true);

            if ($token === false) {
                throw new ParseError(sprintf('Could not decode token "%s" at line %d', $encodedToken, $lineNo));
            }

            assert($token !== '');

            $map[$token] = (int) $rank;

            $line = fgets($stream);
            $lineNo++;
        }

        return new self($map);
    }

    public function tryGetRank(array $bytes) : ?int
    {
        return $this->tokenToRankMap[EncodeUtil::fromBytes($bytes)] ?? null;
    }

    public function getRank(array $bytes)
    {
        $rank = $this->tokenToRankMap[EncodeUtil::fromBytes($bytes)];

        if(is_null($rank)) {
            throw new OutOfBoundsException(sprintf('No rank for bytes vector: [%s]', implode(', ', $bytes)));
        }

        return $rank;
    }

    public function getToken(int $rank): string
    {
        $token = $this->rankToTokenMap[$rank];

        if(!$token) {
            throw new OutOfBoundsException(sprintf('No token for rank: %d', $rank));
        }

        return $token;
    }
    
    public function count(): int
    {
        return count($this->tokenToRankMap);
    }
}
