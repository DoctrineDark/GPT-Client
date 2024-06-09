<?php

namespace App\Service\OpenAI\Tokenizer;

use App\Service\OpenAI\Tiktoken\EncoderProvider;

class Tokenizer
{
    /**
     * @var EncoderProvider
     */
    private $provider;
    /**
     * @var TokenCalculator
     */
    private $calculator;

    public function __construct(EncoderProvider $provider, TokenCalculator $calculator)
    {
        $this->provider = $provider;
        $this->calculator = $calculator;
    }

    public function tokens($prompt, $model = null): array
    {
        if ($model != null) {
            $encoder = $this->provider->getForModel($model);
            $tokens = $encoder->encode($prompt);

            return $tokens;
        }

        return $this->calculator->gpt_encode($prompt);
    }

    public function count($prompt, $model = null): int
    {
        if ($model != null) {
            $encoder = $this->provider->getForModel($model);
            $tokens = $encoder->encode($prompt);

            return count($tokens);
        }

        return count($this->calculator->gpt_encode($prompt));
    }

    public function chunk(string $prompt, string $model, int $maxTokensPerChunk) : array
    {
        $chunks = [];

        $encoder = $this->provider->getForModel($model);
        $encodedChunks = $encoder->encodeInChunks($prompt, $maxTokensPerChunk);

        foreach ($encodedChunks as $encodedChunk) {
            $chunks[] = $encoder->decode($encodedChunk);
        }

        return $chunks;
    }
}
