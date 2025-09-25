<?php

namespace App\Service\Gpt\Response;

use App\Service\Gpt\Extension\Arrayable;

class GptEmbeddingResponse
{
    use Arrayable;

    /** @var string|null */
    public $model;

    /** @var array|null */
    public $embedding;

    /** @var integer|null */
    public $dimensions;

    /** @var integer|null */
    public $prompt_tokens;

    /** @var integer|null */
    public $total_tokens;

    public function __construct(array $data=[])
    {
        $this->map($data);
    }

    private function map(array $data) : void
    {
        foreach ($data as $key=>$value) {
            if(property_exists(self::class, $key)) {
                $this->$key = $value;
            }
        }
    }
}