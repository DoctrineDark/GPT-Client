<?php

namespace App\MessageHandler;

use App\Message\Test;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

final class TestHandler implements MessageHandlerInterface
{
    private $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function __invoke(Test $message)
    {
        $this->logger->info($message->getText());
    }
}
