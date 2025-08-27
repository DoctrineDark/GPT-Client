<?php

namespace App\MessageHandler;

use App\Entity\Article;
use App\Entity\ArticleParagraph;
use App\Entity\CloudflareVector;
use App\Entity\OpenSearchIndex;
use App\Entity\OpenSearchVector;
use App\Message\ArticleVectorize;
use App\Repository\ArticleRepository;
use App\Repository\CloudflareIndexRepository;
use App\Repository\CloudflareVectorRepository;
use App\Repository\OpenSearchIndexRepository;
use App\Repository\OpenSearchVectorRepository;
use App\Service\OpenSearch\Client as OpenSearchClient;
use App\Service\Cloudflare\Vectorize\Client as VectorizeClient;
use App\Service\Gpt\AIService;
use App\Service\Gpt\BGEClient;
use App\Service\Gpt\CloudflareClient;
use App\Service\Gpt\Exception\GptServiceException;
use App\Service\Gpt\OpenAIClient;
use App\Service\Gpt\Request\GptEmbeddingRequest;
use App\Service\OpenAI\Tokenizer\Tokenizer;
use App\Service\VectorSearch\Embedding;
use App\Service\VectorSearch\RedisSearcher;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

final class ArticleVectorizeHandler implements MessageHandlerInterface
{
    private $entityManager;
    private $articleRepository;
    private $cloudflareIndexRepository;
    private $cloudflareVectorRepository;
    private $openSearchIndexRepository;
    private $openSearchVectorRepository;
    private $AIService;
    private $redisSearcher;
    private $tokenizer;
    private $vectorizeClient;
    private $openSearchClient;
    private $vectorizerLogger;

    public function __construct(EntityManagerInterface $entityManager, ArticleRepository $articleRepository, CloudflareIndexRepository $cloudflareIndexRepository, CloudflareVectorRepository $cloudflareVectorRepository, OpenSearchIndexRepository $openSearchIndexRepository, OpenSearchVectorRepository $openSearchVectorRepository, AIService $AIService, RedisSearcher $redisSearcher, Tokenizer $tokenizer, VectorizeClient $vectorizeClient, OpenSearchClient $openSearchClient, LoggerInterface $vectorizerLogger)
    {
        $this->entityManager = $entityManager;
        $this->articleRepository = $articleRepository;
        $this->cloudflareIndexRepository = $cloudflareIndexRepository;
        $this->cloudflareVectorRepository = $cloudflareVectorRepository;
        $this->openSearchIndexRepository = $openSearchIndexRepository;
        $this->openSearchVectorRepository = $openSearchVectorRepository;
        $this->AIService = $AIService;
        $this->redisSearcher = $redisSearcher;
        $this->tokenizer = $tokenizer;
        $this->vectorizeClient = $vectorizeClient;
        $this->openSearchClient = $openSearchClient;
        $this->vectorizerLogger = $vectorizerLogger;
    }

    public function __invoke(ArticleVectorize $message)
    {
        try {
            $articleId = $message->getArticleId();
            $gptService = $message->getGptService();
            $accountId = $message->getAccountId();
            $gptApiKey = $message->getGptApiKey();
            $gptEmbeddingModel = $message->getGptEmbeddingModel();
            $index = $message->getIndex();
            $cloudflareIndex = $this->cloudflareIndexRepository->findOneBy(['name' => $index]);
            $openSearchIndex = $this->openSearchIndexRepository->findOneBy(['name' => $index]);
            $gptMaxTokensPerChunk = $message->getGptMaxTokensPerChunk();

            $article = $this->articleRepository->find($articleId);

            if(!$article) {
                $this->vectorizerLogger->error('Article ID#'.$articleId.' not found');
            }

            switch ($gptService) {
                case OpenAIClient::SERVICE:
                    $articleKey = RedisSearcher::ROOT.RedisSearcher::DELIMITER.'articles'.RedisSearcher::DELIMITER.$articleId;
                    $articleTitle = $article->getArticleTitle();
                    $articleTitleEmbedding = null;
                    $articleContentEmbedding = null;

                    /* Article title Embedding request */
                    if($articleTitle) {
                        $articleTitleGptEmbeddingRequest = (new GptEmbeddingRequest())
                            ->setApiKey($gptApiKey)
                            ->setModel($gptEmbeddingModel)
                            ->setPrompt($articleTitle);

                        $articleTitleGptEmbeddingResponse = $this->AIService->embedding($gptService, $articleTitleGptEmbeddingRequest);
                        $articleTitleEmbedding = $articleTitleGptEmbeddingResponse->embedding;
                    }

                    /* Store Article title Embedding */
                    $this->redisSearcher->setEmbedding(
                        new Embedding($articleId, Article::TYPE, $articleTitleEmbedding, $articleContentEmbedding),
                        $articleKey,
                        '$'
                    );

                    // Log
                    $this->vectorizerLogger->info('Article#'.$article->getId().' has been vectorized');

                    $articleParagraphs = $article->getParagraphs();

                    /** @var ArticleParagraph $articleParagraph */
                    foreach ($articleParagraphs as $articleParagraph) {
                        $articleParagraphId = $articleParagraph->getId();
                        $articleParagraphKey = RedisSearcher::ROOT.RedisSearcher::DELIMITER.'articles'.RedisSearcher::DELIMITER.$articleId.RedisSearcher::DELIMITER.'paragraphs'.RedisSearcher::DELIMITER.$articleParagraphId;
                        $articleParagraphTitle = $articleParagraph->getParagraphTitle();
                        $articleParagraphContent = $articleParagraph->getParagraphContent();
                        $articleParagraphTitleEmbedding = null;
                        $articleParagraphContentEmbedding = null;

                        /* ArticleParagraph title Embedding request */
                        if($articleParagraphTitle) {
                            $articleParagraphTitleGptEmbeddingRequest = (new GptEmbeddingRequest())
                                ->setApiKey($gptApiKey)
                                ->setModel($gptEmbeddingModel)
                                ->setPrompt($articleParagraphTitle);

                            $articleParagraphTitleGptEmbeddingResponse = $this->AIService->embedding($gptService, $articleParagraphTitleGptEmbeddingRequest);
                            $articleParagraphTitleEmbedding = $articleParagraphTitleGptEmbeddingResponse->embedding;
                        }

                        /* ArticleParagraph content Embedding request */
                        if($articleParagraphContent) {

                            /* Chunk ArticleParagraph content */
                            /*
                            $articleParagraphContentChunks = $this->tokenizer->chunk($articleParagraphContent, 'gpt-3.5-turbo', $gptMaxTokensPerChunk);

                            $articleParagraphContentEmbedding = [];
                            foreach ($articleParagraphContentChunks as $chunk) {
                                $articleParagraphContentChunkGptEmbeddingRequest = (new GptEmbeddingRequest())
                                    ->setApiKey($gptApiKey)
                                    ->setModel($gptEmbeddingModel)
                                    ->setPrompt($chunk);

                                $articleParagraphContentChunkGptEmbeddingResponse = $gptClient->embedding($articleParagraphContentChunkGptEmbeddingRequest);
                                $articleParagraphContentChunkEmbedding = $articleParagraphContentChunkGptEmbeddingResponse->embedding;
                                $articleParagraphContentEmbedding = array_merge($articleParagraphContentEmbedding, $articleParagraphContentChunkEmbedding);
                            }
                            */

                            $articleParagraphContentGptEmbeddingRequest = (new GptEmbeddingRequest())
                                ->setApiKey($gptApiKey)
                                ->setModel($gptEmbeddingModel)
                                ->setPrompt($articleParagraphContent);

                            $articleParagraphContentGptEmbeddingResponse = $this->AIService->embedding($gptService, $articleParagraphContentGptEmbeddingRequest);
                            $articleParagraphContentEmbedding = $articleParagraphContentGptEmbeddingResponse->embedding;
                        }

                        /* Store ArticleParagraph Embedding */
                        $this->redisSearcher->setEmbedding(
                            new Embedding($articleParagraphId, ArticleParagraph::TYPE, $articleParagraphTitleEmbedding, $articleParagraphContentEmbedding),
                            $articleParagraphKey,
                            '$'
                        );

                        // Log
                        $this->vectorizerLogger->info('ArticleParagraph#'.$articleParagraph->getId().' has been vectorized');
                    }

                    break;

                case CloudflareClient::SERVICE:
                    $articleTitle = $article->getArticleTitle();
                    $articleParagraphs = $article->getParagraphs();

                    /** @var ArticleParagraph $articleParagraph */
                    foreach ($articleParagraphs as $articleParagraph) {
                        $articleParagraphId = $articleParagraph->getId();
                        $articleParagraphTitle = $articleParagraph->getParagraphTitle();
                        $articleParagraphContent = $articleParagraph->getParagraphContent();
                        $cloudflareVector = $this->cloudflareVectorRepository->findOneBy([
                            'type' => ArticleParagraph::TYPE,
                            'articleParagraph' => $articleParagraph,
                            'cloudflareIndex' => $cloudflareIndex
                        ]);

                        if (!$cloudflareVector) {
                            // Get Embeddings
                            $promptEmbeddingRequest = (new GptEmbeddingRequest())
                                ->setAccountId($accountId)
                                ->setApiKey($gptApiKey)
                                ->setModel($gptEmbeddingModel)
                                ->setPrompt($articleTitle . PHP_EOL . $articleParagraphTitle . PHP_EOL . $articleParagraphContent);

                            $embedding = $this->AIService->embedding($gptService, $promptEmbeddingRequest);

                            // Store Embeddings
                            $vector = $this->vectorizeClient
                                ->setAccountId($accountId)
                                ->setApiKey($gptApiKey)
                                ->insertVectors($index, [
                                    'vectors' => [
                                        'id' => ArticleParagraph::TYPE.'_'.$articleParagraphId,
                                        'values' => $embedding->embedding,
                                        'metadata' => [
                                            'type' => ArticleParagraph::TYPE,
                                            'id' => $articleParagraphId
                                        ]
                                    ]
                            ]);

                            // Log
                            $this->vectorizerLogger->debug('VECTOR');
                            $this->vectorizerLogger->debug($vector);

                            $vector = json_decode($vector, 1);

                            if (false === $vector['success']) {
                                throw new GptServiceException(implode(' ', array_column($vector['errors'], 'message')));
                            }

                            // Save CloudflareVector
                            $cloudflareVector = (new CloudflareVector())
                                ->setVectorId($vector['result']['mutationId'])
                                ->setType(ArticleParagraph::TYPE)
                                ->setArticle($article)
                                ->setArticleParagraph($articleParagraph)
                                ->setCloudflareIndex($cloudflareIndex);

                            $this->entityManager->persist($cloudflareVector);
                            $this->entityManager->flush();

                            // Log
                            $this->vectorizerLogger->info('ArticleParagraph#'.$articleParagraph->getId().' has been vectorized. Vector ID: '.$cloudflareVector->getVectorId());
                        } else {
                            $this->vectorizerLogger->warning('ArticleParagraph#'.$articleParagraph->getId().' has NOT been vectorized. Vector ID: '.$cloudflareVector->getVectorId().' already exists.');
                        }
                    }

                    break;

                case BGEClient::SERVICE:
                    $articleTitle = $article->getArticleTitle();
                    $articleParagraphs = $article->getParagraphs();

                    /** @var ArticleParagraph $articleParagraph */
                    foreach ($articleParagraphs as $articleParagraph) {
                        $articleParagraphId = $articleParagraph->getId();
                        $articleParagraphTitle = $articleParagraph->getParagraphTitle();
                        $articleParagraphContent = $articleParagraph->getParagraphContent();

                        $openSearchVector = $this->openSearchVectorRepository->findOneBy([
                            'type' => ArticleParagraph::TYPE,
                            'articleParagraph' => $articleParagraph,
                            'openSearchIndex' => $openSearchIndex,
                        ]);

                        if (!$openSearchVector) {
                            // Get Embeddings
                            $promptEmbeddingRequest = (new GptEmbeddingRequest())
                                ->setModel($gptEmbeddingModel)
                                ->setPrompt($articleTitle . PHP_EOL . $articleParagraphTitle . PHP_EOL . $articleParagraphContent);

                            $embedding = $this->AIService->embedding($gptService, $promptEmbeddingRequest);

                            // Store Embeddings
                            $vector = $this->openSearchClient->insertVector($index, [
                                'id' => ArticleParagraph::TYPE.'_'.$articleParagraphId,
                                OpenSearchIndex::TEXT_PROPERTY => $articleParagraph->getParagraphTitle() . ' ' . $articleParagraph->getParagraphContent(),
                                OpenSearchIndex::EMBEDDING_PROPERTY => $embedding->embedding,
                                'metadata' => [
                                    'id' => $articleParagraphId,
                                    'type' => ArticleParagraph::TYPE,
                                    'title' => $articleParagraph->getParagraphTitle(),
                                    'content' => $articleParagraph->getParagraphContent()
                                ],
                            ]);

                            // Log
                            $this->vectorizerLogger->debug('VECTOR');
                            $this->vectorizerLogger->debug($vector);

                            $vector = json_decode($vector, 1);

                            if (isset($vector['error'])) {
                                throw new GptServiceException($vector['error']['reason']);
                            }

                            // Save OpenSearchVector
                            $openSearchVector = (new OpenSearchVector())
                                ->setVectorId($vector['_id'])
                                ->setType(ArticleParagraph::TYPE)
                                ->setArticle($article)
                                ->setArticleParagraph($articleParagraph)
                                ->setOpenSearchIndex($openSearchIndex);

                            $this->entityManager->persist($openSearchVector);
                            $this->entityManager->flush();

                            // Log
                            $this->vectorizerLogger->info('ArticleParagraph#'.$articleParagraph->getId().' has been vectorized. OpenSearch Vector ID: '.$openSearchVector->getVectorId());
                        } else {
                            $this->vectorizerLogger->warning('ArticleParagraph#'.$articleParagraph->getId().' has NOT been vectorized. OpenSearch Vector ID: '.$openSearchVector->getVectorId().' already exists.');
                        }
                    }

                    break;
            }
        } catch (\Exception $e) {
            $this->vectorizerLogger->error('Article Vectorize Error: ' . $e->getMessage());
            $this->vectorizerLogger->error(json_encode([
                'article_id' => $message->getArticleId(),
                'gpt_service' => $message->getGptService(),
                'account_id' => $message->getAccountId(),
                'api_key' => $message->getGptApiKey(),
                'embeddings_model' => $message->getGptEmbeddingModel(),
                'backtrace' => $e->getTrace()
            ]));
        }

        return;
    }
}
