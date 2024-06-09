<?php


namespace App\Service\Gpt\Exception;


class TokenLimitExceededException extends \Exception
{
    protected $message = 'Token limit exceeded.';
}