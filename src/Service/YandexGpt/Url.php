<?php


namespace App\Service\YandexGpt;


class Url
{
    public const ORIGIN = 'https://llm.api.cloud.yandex.net';
    public const PATH = 'foundationModels';
    public const API_VERSION = 'v1';
    public const URL = self::ORIGIN . '/' . self::PATH . '/' . self::API_VERSION;

    /**
     * @return string
     */
    public static function completion(): string
    {
        return self::URL . '/completion';
    }

    /**
     * @return string
     */
    public static function embedding(): string
    {
        return self::URL . '/textEmbedding';
    }

    /**
     * @return string
     */
    public static function tokenize(): string
    {
        return self::URL . '/tokenize';
    }

    /**
     * @return string
     */
    public static function tokenizeCompletion(): string
    {
        return self::URL . '/tokenizeCompletion';
    }
}