<?php

declare(strict_types=1);

namespace App\Service\OpenAI\Tiktoken\Vocab;

interface VocabLoader
{
    public function load(string $uri) : Vocab;
}
