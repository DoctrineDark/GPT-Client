<?php


namespace App\Service\BGE;


class Url
{
    public $url;
    public $method;
    public $headers;

    public function __construct(string $url, string $method, array $headers = [])
    {
        $this->url = $url;
        $this->method = strtoupper($method);
        $this->headers = $headers;
    }

    public static function embedding(string $originUrl): self
    {
        $url = $originUrl . 'embedding';
        $method = 'post';
        $headers = [
            //'Content-Type: application/json',
            'Content-Type: application/x-www-form-urlencoded'
        ];

        return new self($url, $method, $headers);
    }
}
