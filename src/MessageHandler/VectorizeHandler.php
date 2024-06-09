<?php

namespace App\MessageHandler;

use App\Entity\Article;
use App\Entity\Template;
use App\Message\ArticleVectorize;
use App\Message\TemplateVectorize;
use App\Message\Vectorize;
use App\Repository\ArticleRepository;
use App\Repository\TemplateRepository;
use App\Service\OpenAI\Tokenizer\Tokenizer;
use App\Service\VectorSearch\RedisSearcher;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\DelayStamp;

final class VectorizeHandler implements MessageHandlerInterface
{
    private $articleRepository;
    private $templateRepository;
    private $bus;
    private $redisSearcher;
    private $tokenizer;
    private $logger;

    private $delay = 500;

    public function __construct(ArticleRepository $articleRepository, TemplateRepository $templateRepository, MessageBusInterface $bus, RedisSearcher $redisSearcher, Tokenizer $tokenizer, LoggerInterface $logger)
    {
        $this->articleRepository = $articleRepository;
        $this->templateRepository = $templateRepository;
        $this->bus = $bus;
        $this->redisSearcher = $redisSearcher;
        $this->tokenizer = $tokenizer;
        $this->logger = $logger;
    }

    public function __invoke(Vectorize $message)
    {
        $gptService = $message->getGptService();
        $gptApiKey = $message->getGptApiKey();
        $gptEmbeddingModel = $message->getGptEmbeddingModel();
        $gptMaxTokensPerChunk = $message->getGptMaxTokensPerChunk();

        $articleQuery = $this->articleRepository->query('select a from App\Entity\Article a');

        /** @var Article $article */
        foreach ($articleQuery->toIterable() as $article) {
            $articleId = $article->getId();
            $articleKey = RedisSearcher::ROOT . RedisSearcher::DELIMITER . 'articles' . RedisSearcher::DELIMITER . $articleId;

            if (!$this->redisSearcher->exists($articleKey)) {
                $this->bus->dispatch(
                    new ArticleVectorize($articleId, $gptService, $gptApiKey, $gptEmbeddingModel, $gptMaxTokensPerChunk),
                    [
                        new DelayStamp($this->delay),
                    ]
                );
            }

            $this->articleRepository->getEntityManager()->detach($article);
        }


        $templateQuery = $this->templateRepository->query('select t from App\Entity\Template t');

        /** @var Template $template */
        foreach ($templateQuery->toIterable() as $template) {
            $templateId = $template->getId();
            $templateKey = RedisSearcher::ROOT . RedisSearcher::DELIMITER . 'templates' . RedisSearcher::DELIMITER . $templateId;

            if (!$this->redisSearcher->exists($templateKey)) {
                $this->bus->dispatch(
                    new TemplateVectorize($templateId, $gptService, $gptApiKey, $gptEmbeddingModel, $gptMaxTokensPerChunk),
                    [
                        new DelayStamp($this->delay),
                    ]
                );
            }

            $this->templateRepository->getEntityManager()->detach($template);
        }

        return;
    }
}
