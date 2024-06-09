<?php

namespace App\MessageHandler;

use App\Message\TemplateVectorize;
use App\Repository\TemplateRepository;
use App\Service\Gpt\AIService;
use App\Service\Gpt\Request\GptEmbeddingRequest;
use App\Service\OpenAI\Tokenizer\Tokenizer;
use App\Service\VectorSearch\Embedding;
use App\Service\VectorSearch\RedisSearcher;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

final class TemplateVectorizeHandler implements MessageHandlerInterface
{
    private $templateRepository;
    private $AIService;
    private $redisSearcher;
    private $tokenizer;
    private $vectorizerLogger;

    public function __construct(TemplateRepository $templateRepository, AIService $AIService, RedisSearcher $redisSearcher, Tokenizer $tokenizer, LoggerInterface $vectorizerLogger)
    {
        $this->templateRepository = $templateRepository;
        $this->AIService = $AIService;
        $this->redisSearcher = $redisSearcher;
        $this->tokenizer = $tokenizer;
        $this->vectorizerLogger = $vectorizerLogger;
    }

    public function __invoke(TemplateVectorize $message)
    {
        try {
            $gptService = $message->getGptService();
            $gptApiKey = $message->getGptApiKey();
            $gptEmbeddingModel = $message->getGptEmbeddingModel();
            $gptMaxTokensPerChunk = $message->getGptMaxTokensPerChunk();

            $template = $this->templateRepository->find($message->getTemplateId());

            if($template) {
                $templateId = $template->getId();
                $templateKey = RedisSearcher::ROOT.RedisSearcher::DELIMITER.'templates'.RedisSearcher::DELIMITER.$templateId;
                $templateTitle = $template->getTemplateTitle();
                $templateContent = $template->getTemplateContent();
                $templateTitleEmbedding = null;
                $templateContentEmbedding = null;

                // if Template key IS NOT exist
                //if(!$this->redisSearcher->exists($templateKey)) {

                /* Template title Embedding request */
                if($templateTitle) {
                    $templateTitleGptEmbeddingRequest = (new GptEmbeddingRequest())
                        ->setApiKey($gptApiKey)
                        ->setModel($gptEmbeddingModel)
                        ->setPrompt($templateTitle);

                    $templateTitleGptEmbeddingResponse = $this->AIService->embedding($gptService, $templateTitleGptEmbeddingRequest);
                    $templateTitleEmbedding = $templateTitleGptEmbeddingResponse->embedding;
                }

                /* Template content Embedding request */
                if($templateContent) {

                    /* Chunk Template content */
                    /*
                    $templateContentChunks = $this->tokenizer->chunk($templateContent, 'gpt-3.5-turbo', $gptMaxTokensPerChunk);

                    $templateContentEmbedding = [];
                    foreach ($templateContentChunks as $chunk) {
                        $templateContentChunkGptEmbeddingRequest = (new GptEmbeddingRequest())
                            ->setApiKey($gptApiKey)
                            ->setModel($gptEmbeddingModel)
                            ->setPrompt($chunk);

                        $templateContentChunkGptEmbeddingResponse = $gptClient->embedding($templateContentChunkGptEmbeddingRequest);
                        $templateContentChunkEmbedding = $templateContentChunkGptEmbeddingResponse->embedding;
                        $templateContentEmbedding = array_merge($templateContentEmbedding, $templateContentChunkEmbedding);
                    }
                    */

                    $templateContentGptEmbeddingRequest = (new GptEmbeddingRequest())
                        ->setApiKey($gptApiKey)
                        ->setModel($gptEmbeddingModel)
                        ->setPrompt($templateTitle);

                    $templateContentGptEmbeddingResponse = $this->AIService->embedding($gptService, $templateContentGptEmbeddingRequest);
                    $templateContentEmbedding = $templateContentGptEmbeddingResponse->embedding;
                }

                /* Store Template title & content Embedding */
                $this->redisSearcher->setEmbedding(
                    new Embedding($templateId, 'template', $templateTitleEmbedding, $templateContentEmbedding),
                    $templateKey,
                    '$'
                );

                // Log
                $this->vectorizerLogger->info('Template#'.$template->getId().' has been vectorized');
                //}

            } else {
                // Log
                $this->vectorizerLogger->error('Template#'.$message->getTemplateId().' not found');
            }

            return;
        }
        catch (\Exception $e) {
            $this->vectorizerLogger->error('Error: '.$e->getMessage());
        }
    }
}
