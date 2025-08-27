<?php

namespace App\MessageHandler;

use App\Entity\Article;
use App\Entity\Template;
use App\Message\ArticleVectorize;
use App\Message\TemplateVectorize;
use App\Message\Vectorize;
use App\Repository\ArticleRepository;
use App\Repository\TemplateRepository;
use App\Service\Cloudflare\Vectorize\Client as VectorizeClient;
use App\Service\Cloudflare\WorkersAI\Client as WorkersAIClient;
use App\Service\Gpt\BGEClient;
use App\Service\Gpt\CloudflareClient;
use App\Service\Gpt\Exception\GptServiceException;
use App\Service\Gpt\OpenAIClient;
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
    private $vectorizeClient;
    private $vectorizerLogger;

    private $delay = 500;

    public function __construct(ArticleRepository $articleRepository, TemplateRepository $templateRepository, MessageBusInterface $bus, RedisSearcher $redisSearcher, Tokenizer $tokenizer, VectorizeClient $vectorizeClient, LoggerInterface $vectorizerLogger)
    {
        $this->articleRepository = $articleRepository;
        $this->templateRepository = $templateRepository;
        $this->bus = $bus;
        $this->redisSearcher = $redisSearcher;
        $this->tokenizer = $tokenizer;
        $this->vectorizeClient = $vectorizeClient;
        $this->vectorizerLogger = $vectorizerLogger;
    }

    public function __invoke(Vectorize $message)
    {
        try {
            $gptService = $message->getGptService();
            $accountId = $message->getAccountId();
            $gptApiKey = $message->getGptApiKey();
            $gptEmbeddingModel = $message->getGptEmbeddingModel();
            $index = $message->getIndex();
            $gptMaxTokensPerChunk = $message->getGptMaxTokensPerChunk();

            $articleQuery = $this->articleRepository->query('select a from App\Entity\Article a');
            $templateQuery = $this->templateRepository->query('select t from App\Entity\Template t');

            switch ($gptService) {
                case OpenAIClient::SERVICE:
                    /** @var Article $article */
                    foreach ($articleQuery->toIterable() as $article) {
                        $articleId = $article->getId();
                        $articleKey = RedisSearcher::ROOT . RedisSearcher::DELIMITER . 'articles' . RedisSearcher::DELIMITER . $articleId;

                        if (!$this->redisSearcher->exists($articleKey)) {
                            $this->bus->dispatch(
                                new ArticleVectorize($articleId, $gptService, null, $gptApiKey, $gptEmbeddingModel, null, $gptMaxTokensPerChunk),
                                [
                                    new DelayStamp($this->delay),
                                ]
                            );
                        }

                        $this->articleRepository->getEntityManager()->detach($article);
                    }

                    /**/

                    /** @var Template $template */
                    foreach ($templateQuery->toIterable() as $template) {
                        $templateId = $template->getId();
                        $templateKey = RedisSearcher::ROOT . RedisSearcher::DELIMITER . 'templates' . RedisSearcher::DELIMITER . $templateId;

                        if (!$this->redisSearcher->exists($templateKey)) {
                            $this->bus->dispatch(
                                new TemplateVectorize($templateId, $gptService, null, $gptApiKey, $gptEmbeddingModel, null, $gptMaxTokensPerChunk),
                                [
                                    new DelayStamp($this->delay),
                                ]
                            );
                        }

                        $this->templateRepository->getEntityManager()->detach($template);
                    }

                    break;

                case CloudflareClient::SERVICE:
                    // Set Credentials
                    $this->vectorizeClient->setAccountId($accountId);
                    $this->vectorizeClient->setApiKey($gptApiKey);

                    // Dispatch
                    /** @var Article $article */
                    foreach ($articleQuery->toIterable() as $article) {
                        $articleId = $article->getId();
                        $this->bus->dispatch(
                            new ArticleVectorize($articleId, $gptService, $accountId, $gptApiKey, $gptEmbeddingModel, $index, $gptMaxTokensPerChunk),
                            [
                                new DelayStamp($this->delay),
                            ]
                        );

                        $this->articleRepository->getEntityManager()->detach($article);
                    }

                    /**/

                    /** @var Template $template */
                    foreach ($templateQuery->toIterable() as $template) {
                        $templateId = $template->getId();
                        $this->bus->dispatch(
                            new TemplateVectorize($templateId, $gptService, $accountId, $gptApiKey, $gptEmbeddingModel, $index, $gptMaxTokensPerChunk),
                            [
                                new DelayStamp($this->delay),
                            ]
                        );

                        $this->templateRepository->getEntityManager()->detach($template);
                    }

                    break;

                case BGEClient::SERVICE:

                    // Dispatch
                    /** @var Article $article */
                    foreach ($articleQuery->toIterable() as $article) {
                        $articleId = $article->getId();
                        $this->bus->dispatch(
                            new ArticleVectorize($articleId, $gptService, $accountId, $gptApiKey, $gptEmbeddingModel, $index, $gptMaxTokensPerChunk),
                            [
                                new DelayStamp($this->delay),
                            ]
                        );

                        $this->articleRepository->getEntityManager()->detach($article);
                    }

                    /**/

                    /** @var Template $template */
                    foreach ($templateQuery->toIterable() as $template) {
                        $templateId = $template->getId();
                        $this->bus->dispatch(
                            new TemplateVectorize($templateId, $gptService, $accountId, $gptApiKey, $gptEmbeddingModel, $index, $gptMaxTokensPerChunk),
                            [
                                new DelayStamp($this->delay),
                            ]
                        );

                        $this->templateRepository->getEntityManager()->detach($template);
                    }

                    break;
            }
        } catch (\Exception $e) {
            $this->vectorizerLogger->error('Vectorize error: ' . $e->getMessage());
            $this->vectorizerLogger->error(json_encode([
                'gpt_service' => $message->getGptService(),
                'account_id' => $message->getAccountId(),
                'api_key' => $message->getGptApiKey(),
                'embeddings_model' => $message->getGptEmbeddingModel()
            ]));
        }

        return;
    }
}
