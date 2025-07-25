<?php

namespace App\MessageHandler;

use App\Entity\CloudflareVector;
use App\Entity\Template;
use App\Message\TemplateVectorize;
use App\Repository\CloudflareIndexRepository;
use App\Repository\CloudflareVectorRepository;
use App\Repository\TemplateRepository;
use App\Service\Cloudflare\Vectorize\Client as VectorizeClient;
use App\Service\Gpt\AIService;
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

final class TemplateVectorizeHandler implements MessageHandlerInterface
{
    private $entityManager;
    private $templateRepository;
    private $cloudflareIndexRepository;
    private $cloudflareVectorRepository;
    private $AIService;
    private $redisSearcher;
    private $tokenizer;
    private $vectorizeClient;
    private $vectorizerLogger;

    public function __construct(EntityManagerInterface $entityManager, TemplateRepository $templateRepository, CloudflareIndexRepository $cloudflareIndexRepository, CloudflareVectorRepository $cloudflareVectorRepository, AIService $AIService, RedisSearcher $redisSearcher, Tokenizer $tokenizer, VectorizeClient $vectorizeClient, LoggerInterface $vectorizerLogger)
    {
        $this->entityManager = $entityManager;
        $this->templateRepository = $templateRepository;
        $this->cloudflareIndexRepository = $cloudflareIndexRepository;
        $this->cloudflareVectorRepository = $cloudflareVectorRepository;
        $this->AIService = $AIService;
        $this->redisSearcher = $redisSearcher;
        $this->tokenizer = $tokenizer;
        $this->vectorizeClient = $vectorizeClient;
        $this->vectorizerLogger = $vectorizerLogger;
    }

    public function __invoke(TemplateVectorize $message)
    {
        try {
            $templateId = $message->getTemplateId();
            $gptService = $message->getGptService();
            $accountId = $message->getAccountId();
            $gptApiKey = $message->getGptApiKey();
            $gptEmbeddingModel = $message->getGptEmbeddingModel();
            $index = $message->getIndex();
            $cloudflareIndex = $this->cloudflareIndexRepository->findOneBy(['name' => $index]);
            $gptMaxTokensPerChunk = $message->getGptMaxTokensPerChunk();

            $template = $this->templateRepository->find($templateId);

            if(!$template) {
                $this->vectorizerLogger->error('Template ID#'.$templateId.' not found');
            }

            switch ($gptService) {
                case OpenAIClient::SERVICE:
                    $templateId = $template->getId();
                    $templateKey = RedisSearcher::ROOT.RedisSearcher::DELIMITER.'templates'.RedisSearcher::DELIMITER.$templateId;
                    $templateTitle = $template->getTemplateTitle();
                    $templateContent = $template->getTemplateContent();
                    $templateTitleEmbedding = null;
                    $templateContentEmbedding = null;

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
                        new Embedding($templateId, Template::TYPE, $templateTitleEmbedding, $templateContentEmbedding),
                        $templateKey,
                        '$'
                    );

                    // Log
                    $this->vectorizerLogger->info('Template#'.$template->getId().' has been vectorized');

                    break;

                case CloudflareClient::SERVICE:
                    $templateTitle = $template->getTemplateTitle();
                    $templateContent = $template->getTemplateContent();
                    $cloudflareVector = $this->cloudflareVectorRepository->findOneBy([
                        'type' => Template::TYPE,
                        'template' => $template,
                        'cloudflareIndex' => $cloudflareIndex
                    ]);

                    if (!$cloudflareVector) {
                        // Get Embeddings
                        $promptEmbeddingRequest = (new GptEmbeddingRequest())
                            ->setAccountId($accountId)
                            ->setApiKey($gptApiKey)
                            ->setModel($gptEmbeddingModel)
                            ->setPrompt($templateTitle . PHP_EOL . $templateContent);

                        $embedding = $this->AIService->embedding($gptService, $promptEmbeddingRequest);

                        // Store Embeddings
                        $vector = $this->vectorizeClient->insertVectors($index, [
                            'vectors' => [
                                'id' => Template::TYPE.'_'.$templateId,
                                'values' => $embedding->embedding,
                                'metadata' => [
                                    'type' => Template::TYPE,
                                    'id' => $templateId
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
                            ->setType(Template::TYPE)
                            ->setTemplate($template)
                            ->setCloudflareIndex($cloudflareIndex);

                        $this->entityManager->persist($cloudflareVector);
                        $this->entityManager->flush();

                        // Log
                        $this->vectorizerLogger->info('Template#'.$template->getId().' has been vectorized. Vector ID: '.$cloudflareVector->getVectorId());
                    } else {
                        $this->vectorizerLogger->warning('Template#'.$template->getId().' has NOT been vectorized!. Vector ID: '.$cloudflareVector->getVectorId().' already exists.');
                    }

                    break;
            }
        }
        catch (\Exception $e) {
            $this->vectorizerLogger->error('Template Vectorize Error: ' . $e->getMessage());
            $this->vectorizerLogger->error(json_encode([
                'template_id' => $message->getTemplateId(),
                'gpt_service' => $message->getGptService(),
                'account_id' => $message->getAccountId(),
                'api_key' => $message->getGptApiKey(),
                'embeddings_model' => $message->getGptEmbeddingModel()
            ]));
        }

        return;
    }
}
