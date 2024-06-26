<?php

namespace App\Service\OpenAI\Tiktoken;

use Stringable;
use App\Service\OpenAI\Tiktoken\Exception\RegexError;
use App\Service\OpenAI\Tiktoken\Util\EncodeUtil;
use App\Service\OpenAI\Tiktoken\Vocab\Vocab;

use function array_map;
use function array_merge;
use function array_slice;
use function array_values;
use function assert;
use function count;
use function implode;
use function preg_last_error_msg;
use function preg_match_all;
use function range;
use function sprintf;

use const PHP_INT_MAX;

final class Encoder implements Stringable
{
    /**
     * @var string
     */
    private $name;
    /**
     * @var Vocab
     */
    private $vocab;
    /**
     * @var string
     */
    private $pattern;

    public function __construct(string $name, Vocab $vocab, string $pattern)
    {
        $this->name = $name;
        $this->vocab = $vocab;
        $this->pattern = $pattern;
    }

    public function __toString(): string
    {
        return sprintf('Encoder(name="%s", vocab=%d)', $this->name, count($this->vocab));
    }

    public function encode(string $text): array
    {
        if ($text === '') {
            return [];
        }

        if (preg_match_all($this->pattern, $text, $matches) === false) {
            throw new RegexError(sprintf('Matching failed with error: %s', preg_last_error_msg()));
        }

        $tokens = [];

        foreach ($matches[0] as $match) {
            if ($match === '') {
                continue;
            }

            $piece = EncodeUtil::toBytes($match);
            $rank = $this->vocab->tryGetRank($piece);

            if ($rank !== null) {
                $tokens[] = $rank;

                continue;
            }

            foreach ($this->mergeBytePairs($piece) as $rank) {
                $tokens[] = $rank;
            }
        }

        return $tokens;
    }

    public function encodeInChunks(string $text, int $maxTokensPerChunk): array
    {
        if ($text === '') {
            return [];
        }

        if (preg_match_all($this->pattern, $text, $matches) === false) {
            throw new RegexError(sprintf('Matching failed with error: %s', preg_last_error_msg()));
        }

        $chunks = [];
        $tokensInCurrentChunk = [];

        foreach ($matches[0] as $match) {
            if ($match === '') {
                continue;
            }

            $tokenBytes = EncodeUtil::toBytes($match);
            $mergedBytePairs = $this->mergeBytePairs($tokenBytes);

            if (count($tokensInCurrentChunk) + count($mergedBytePairs) > $maxTokensPerChunk) {
                $chunks[] = $tokensInCurrentChunk;
                $tokensInCurrentChunk = [];
            }

            $tokensInCurrentChunk = array_merge($tokensInCurrentChunk, $mergedBytePairs);
        }

        if (count($tokensInCurrentChunk) > 0) {
            $chunks[] = $tokensInCurrentChunk;
        }

        return $chunks;
    }

    public function decode(array $tokens): string
    {
        if ($tokens === []) {
            return '';
        }

        return implode(array_map([$this->vocab, 'getToken'], $tokens));
    }

    private function mergeBytePairs(array $bytes): array
    {
        $parts = array_map(
            function (int $i) use ($bytes): array {
                if ($i + 1 < count($bytes)) {
                    $piece = array_slice($bytes, $i, 2);
                    assert(count($piece) === 2);

                    return [$i, $this->vocab->tryGetRank($piece) ?? PHP_INT_MAX];
                }

                return [$i, PHP_INT_MAX];
            },
            range(0, count($bytes))
        );
        $getRank = function (array $parts, int $startIndex) use ($bytes): int {
            if ($startIndex + 2 >= count($parts)) {
                return PHP_INT_MAX;
            }

            $offset = $parts[$startIndex][0];
            $piece  = array_slice($bytes, $offset, $parts[$startIndex + 2][0] - $offset);
            assert(count($piece) > 0);

            return $this->vocab->tryGetRank($piece) ?? PHP_INT_MAX;
        };

        while (count($parts) > 1) {
            $minRank = PHP_INT_MAX;
            $partIndex = 0;
            $stop = count($parts) - 1;

            for ($i = 0; $i < $stop; $i++) {
                if ($minRank <= $parts[$i][1]) {
                    continue;
                }

                $minRank = $parts[$i][1];
                $partIndex = $i;
            }

            if ($minRank === PHP_INT_MAX) {
                break;
            }

            unset($parts[$partIndex + 1]);
            $parts = array_values($parts);

            $parts[$partIndex][1] = $getRank($parts, $partIndex);

            if ($partIndex <= 0) {
                continue;
            }

            $parts[$partIndex - 1][1] = $getRank($parts, $partIndex - 1);
        }

        $stop = count($parts) - 1;
        $res = [];

        for ($i = 0; $i < $stop; $i++) {
            $piece = array_slice($bytes, $parts[$i][0], $parts[$i + 1][0] - $parts[$i][0]);
            assert(count($piece) > 0);

            $res[] = $this->vocab->getRank($piece);
        }

        return $res;
    }
}
