<?php


namespace App\Service\Cloudflare\WorkersAI;


class Url
{
    const ORIGIN = 'https://api.cloudflare.com/';
    const CLIENT = 'client/v4';

    const MODEL_PREFIX = '@cf/baai/';

    public $url;
    public $method;
    public $headers;

    public function __construct(string $url, string $method, array $headers = [])
    {
        $this->url = $url;
        $this->method = strtoupper($method);
        $this->headers = $headers;
    }

    /**
     * @param string $accountId
     * @param string $model
     * @return Url
     */
    public static function runModel(string $accountId, string $model): self
    {
        $url = self::ORIGIN . self::CLIENT . "/accounts/{$accountId}/ai/run/{$model}";
        $method = 'post';
        $headers = [
            'Accept: application/json',
            'Content-Type: application/json',
        ];

        return new self($url, $method, $headers);
    }
}