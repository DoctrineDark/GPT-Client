<?php

namespace App\MessageHandler;

use App\Message\ArticleVectorize;
use App\Repository\ArticleRepository;
use App\Service\Gpt\AIService;
use App\Service\Gpt\Request\GptEmbeddingRequest;
use App\Service\OpenAI\Tokenizer\Tokenizer;
use App\Service\VectorSearch\Embedding;
use App\Service\VectorSearch\RedisSearcher;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

final class ArticleVectorizeHandler implements MessageHandlerInterface
{
    private $articleRepository;
    private $AIService;
    private $redisSearcher;
    private $tokenizer;
    private $vectorizerLogger;

    public function __construct(ArticleRepository $articleRepository, AIService $AIService, RedisSearcher $redisSearcher, Tokenizer $tokenizer, LoggerInterface $vectorizerLogger)
    {
        $this->articleRepository = $articleRepository;
        $this->AIService = $AIService;
        $this->redisSearcher = $redisSearcher;
        $this->tokenizer = $tokenizer;
        $this->vectorizerLogger = $vectorizerLogger;
    }

    public function __invoke(ArticleVectorize $message)
    {
        try {
            $gptService = $message->getGptService();
            $gptApiKey = $message->getGptApiKey();
            $gptEmbeddingModel = $message->getGptEmbeddingModel();
            $gptMaxTokensPerChunk = $message->getGptMaxTokensPerChunk();

            $article = $this->articleRepository->find($message->getArticleId());

            if($article) {
                $articleId = $article->getId();
                $articleKey = RedisSearcher::ROOT.RedisSearcher::DELIMITER.'articles'.RedisSearcher::DELIMITER.$articleId;
                $articleTitle = $article->getArticleTitle();
                $articleTitleEmbedding = null;
                $articleContentEmbedding = null;

                // if Article key IS NOT exist
                //if(!$this->redisSearcher->exists($articleKey)) {

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
                        new Embedding($articleId, 'article', $articleTitleEmbedding, $articleContentEmbedding),
                        $articleKey,
                        '$'
                    );

                    // Log
                    $this->vectorizerLogger->info('Article#'.$article->getId().' has been vectorized');

                    $articleParagraphs = $article->getParagraphs();

                    foreach ($articleParagraphs as $articleParagraph) {
                        $articleParagraphId = $articleParagraph->getId();
                        $articleParagraphKey = RedisSearcher::ROOT.RedisSearcher::DELIMITER.'articles'.RedisSearcher::DELIMITER.$articleId.RedisSearcher::DELIMITER.'paragraphs'.RedisSearcher::DELIMITER.$articleParagraphId;
                        $articleParagraphTitle = $articleParagraph->getParagraphTitle();
                        $articleParagraphContent = $articleParagraph->getParagraphContent();
                        $articleParagraphTitleEmbedding = null;
                        $articleParagraphContentEmbedding = null;

                        // if ArticleParagraph key IS NOT exist
                        //if(!$this->redisSearcher->exists($articleParagraphKey)) {

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
                                new Embedding($articleParagraphId, 'article_paragraph', $articleParagraphTitleEmbedding, $articleParagraphContentEmbedding),
                                $articleParagraphKey,
                                '$'
                            );

                            // Log
                            $this->vectorizerLogger->info('ArticleParagraph#'.$articleParagraph->getId().' has been vectorized');
                        //}
                    }
                //}

            } else {
                // Log
                $this->vectorizerLogger->error('Article#'.$message->getArticleId().' not found');
            }

            return;
        }
        catch (\Exception $e) {
            $this->vectorizerLogger->error('Error: '.$e->getMessage());
        }
    }
}
