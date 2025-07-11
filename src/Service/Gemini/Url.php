<?php


namespace App\Service\Gemini;


class Url
{
    public const ORIGIN = 'https://generativelanguage.googleapis.com';
    public const API_VERSION = 'v1beta';
    public const PATH = 'models';
    public const URL = self::ORIGIN . '/' . self::API_VERSION . '/' . self::PATH;

    /**
     * @param string $model
     * @return string
     */
    public static function generateContent(string $model): string
    {
        return self::URL . '/' . $model . ':generateContent';
    }

    /**
     * @param string $model
     * @return string
     */
    public static function streamGenerateContent(string $model): string
    {
        return self::URL . '/' . $model . ':streamGenerateContent';
    }

    /**
     * @param string $model
     * @return string
     */
    public static function embedContent(string $model): string
    {
        return self::URL . '/' . $model . ':embedContent';
    }

    /**
     * @param string $model
     * @return string
     */
    public static function countTokens(string $model): string
    {
        return self::URL . '/' . $model . ':countTokens';
    }
}