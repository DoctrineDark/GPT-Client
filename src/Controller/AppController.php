<?php

namespace App\Controller;

use App\Entity\Article;
use App\Entity\ArticleParagraph;
use App\Entity\CloudflareIndex;
use App\Entity\GptAssistantOption;
use App\Entity\GptRequestOption;
use App\Entity\GptSearchOption;
use App\Entity\GptSummarizeOption;
use App\Entity\Message;
use App\Entity\Template;
use App\Message\Vectorize;
use App\Repository\CloudflareIndexRepository;
use App\Repository\GptAssistantOptionRepository;
use App\Repository\GptRequestHistoryRepository;
use App\Repository\GptRequestOptionRepository;
use App\Repository\GptSearchOptionRepository;
use App\Repository\GptSummarizeOptionRepository;
use App\Repository\MessageRepository;
use App\Service\Gpt\AIService;
use App\Service\Gpt\CloudflareClient;
use App\Service\Gpt\GeminiClient;
use App\Service\Gpt\OpenAIClient;
use App\Service\Gpt\Request\GptAssistantRequest;
use App\Service\Gpt\Request\GptEmbeddingRequest;
use App\Service\Gpt\Request\GptKnowledgebaseRequest;
use App\Service\Gpt\Request\GptQuestionRequest;
use App\Service\Gpt\Response\GptResponse;
use App\Service\Gpt\Request\GptSummarizeRequest;
use App\Service\Gpt\YandexGptClient;
use App\Service\OpenAI\Tokenizer\Tokenizer;
use App\Service\VectorSearch\RedisSearcher;
use App\Validator\EntityExist;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Constraints\All;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Constraints\Choice;
use Symfony\Component\Validator\Constraints\Collection;
use Symfony\Component\Validator\Constraints\Expression;
use Symfony\Component\Validator\Constraints\Json;
use Symfony\Component\Validator\Constraints\Optional;
use Symfony\Component\Validator\Constraints\Range;
use Symfony\Component\Validator\Constraints\Type;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class AppController extends AbstractController
{
    private $gptRequestHistoryRepository;

    private $validator;
    private $AIService;
    private $tokenizer;
    private $redisSearcher;
    private $bus;
    private $serializer;
    private $logger;

    /* Gpt Chat default options*/
    private $gptModel = 'gpt-3.5-turbo';
    private $gptTemperature = 1;
    private $gptMaxTokens = 2000;
    private $gptTokenLimit = 2000;
    private $gptFrequencyPenalty = 0;
    private $gptPresencePenalty = 0;
    private $gptResponseFormatType = 'text';
    private $gptResponseContentType = 'text/plain';
    private $gptTopP = 0.95;
    private $gptTopK = 5;

    /* Gpt Embedding default options */
    private $gptEmbeddingModel = 'text-embedding-3-small';
    private $gptMaxTokensPerChunk = 2000;

    public function __construct(
        GptRequestHistoryRepository $gptRequestHistoryRepository,
        ValidatorInterface $validator,
        AIService $AIService,
        Tokenizer $tokenizer,
        RedisSearcher $redisSearcher,
        MessageBusInterface $bus,
        SerializerInterface $serializer,
        LoggerInterface $logger
    )
    {
        $this->gptRequestHistoryRepository = $gptRequestHistoryRepository;
        $this->validator = $validator;
        $this->AIService = $AIService;
        $this->tokenizer = $tokenizer;
        $this->redisSearcher = $redisSearcher;
        $this->bus = $bus;
        $this->serializer = $serializer;
        $this->logger = $logger;
    }

    public function gptRequestPage(GptRequestOptionRepository $gptRequestOptionRepository): Response
    {
        $requestOption = $gptRequestOptionRepository->findOneBy([]) ?? new GptRequestOption();

        return $this->render('app/request.html.twig', [
            'title' => 'GPT-Requester',
            'aiServices' => AIService::list(),
            'requestOption' => $requestOption,
            'openaiModels' => (new \App\Service\OpenAI\Client(''))->models(),
            'yandexGptModels' => (new \App\Service\YandexGpt\Client(''))->models(),
            'geminiModels' => (new \App\Service\Gemini\Client(''))->models(),
        ]);
    }

    public function gptAssistantPage(GptAssistantOptionRepository $gptAssistantOptionRepository): Response
    {
        $assistantOption = $gptAssistantOptionRepository->findOneBy([]) ?? new GptAssistantOption();

        return $this->render('app/assistant.html.twig', [
            'title' => 'GPT-Assistant',
            'aiServices' => AIService::list(),
            'assistantOption' => $assistantOption,
            'openaiModels' => (new \App\Service\OpenAI\Client(''))->models(),
            'yandexGptModels' => (new \App\Service\YandexGpt\Client(''))->models(),
            'geminiModels' => (new \App\Service\Gemini\Client(''))->models(),
        ]);
    }

    public function gptSearchPage(GptSearchOptionRepository $gptSearchOptionRepository, CloudflareIndexRepository $cloudflareIndexRepository): Response
    {
        $searchOption = $gptSearchOptionRepository->findOneBy([]) ?? new GptSearchOption();

        return $this->render('app/search.html.twig', [
            'title' => 'GPT-Searcher',
            'searchOption' => $searchOption,
            'openaiGptModels' => (new \App\Service\OpenAI\Client(''))->models(),
            'openaiEmbeddingsModels' => ['text-embedding-3-small', 'text-embedding-3-large', 'text-embedding-ada-002'],
            'cloudflareGptModels' => (new \App\Service\Cloudflare\WorkersAI\Client('', ''))->getTextGenerationModels(),
            'cloudflareEmbeddingsModels' => (new \App\Service\Cloudflare\WorkersAI\Client('', ''))->getTextEmbeddingsModels(),
            'cloudflareIndexes' => $cloudflareIndexRepository->findAll()
        ]);
    }

    public function gptSummarizePage(MessageRepository $messageRepository, GptSummarizeOptionRepository $gptSummarizeOptionRepository): Response
    {
        $summarizeOption = $gptSummarizeOptionRepository->findOneBy([]) ?? new GptSummarizeOption();
        $messages = $messageRepository->findAll();

        return $this->render('app/summarize.html.twig', [
            'title' => 'GPT-Summarizer',
            'messages' => $messages,
            'summarizeOption' => $summarizeOption
        ]);
    }


    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function request(Request $request)
    {
        // Carriage return "\r" fix
        $request->request->add($this->carriageReturnFix($request->request->all()));

        try {
            $gptService = $request->request->get('gpt_service', 'openai');

            $errors = $this->validateGptRequest($this->validator, $gptService, $request->request->all());

            if(count($errors) > 0) {
                $messages = [];
                foreach ($errors as $violation) {
                    $messages[] = $violation->getPropertyPath().': '.$violation->getMessage();
                }

                throw new Exception('Validation failed: '. implode(' ', $messages));
            }

            $gptApiKey = $request->request->get('gpt_api_key');
            $gptFolderId = $request->request->get('gpt_folder_id');
            $gptModel = $request->request->get('gpt_model', $this->gptModel);
            $gptTemperature = $request->request->get('gpt_temperature', $this->gptTemperature);
            $gptMaxTokens = $request->request->get('gpt_max_tokens', $this->gptMaxTokens);
            $gptTokenLimit = $request->request->get('gpt_token_limit', $this->gptTokenLimit);
            $gptFrequencyPenalty = $request->request->get('gpt_frequency_penalty', $this->gptFrequencyPenalty);
            $gptPresencePenalty = $request->request->get('gpt_presence_penalty', $this->gptPresencePenalty);
            $gptResponseFormatType = $request->request->get('gpt_response_format_type', $this->gptResponseFormatType);
            $gptResponseContentType = $request->request->get('gpt_response_content_type', $this->gptResponseContentType);
            $gptTopP = $request->request->get('gpt_top_p', $this->gptTopP);
            $gptTopK = $request->request->get('gpt_top_k', $this->gptTopK);

            $raw = $request->request->get('raw');
            $entryTemplate = $request->request->get('entry_template');
            $clientMessage = $request->request->get('client_message');
            $clientMessageTemplate = $request->request->get('client_message_template');
            $lists = $request->request->get('lists', []);
            $listsValues = $request->request->get('lists_values', []);
            $listsMessageTemplate = $request->request->get('lists_message_template');
            $checkboxes = $request->request->get('checkboxes', []);
            $checkboxesMessageTemplate = $request->request->get('checkboxes_message_template');
            $systemMessage = $request->request->get('system_message', '');

            // Gpt Request

            $gptQuestionRequest = (new GptQuestionRequest())
                ->setApiKey($gptApiKey)
                ->setFolderId($gptFolderId)
                ->setModel($gptModel)
                ->setTemperature($gptTemperature)
                ->setMaxTokens($gptMaxTokens)
                ->setTokenLimit($gptTokenLimit)
                ->setFrequencyPenalty($gptFrequencyPenalty)
                ->setPresencePenalty($gptPresencePenalty)
                ->setResponseFormatType($gptResponseFormatType)
                ->setResponseContentType($gptResponseContentType)
                ->setTopP($gptTopP)
                ->setTopK($gptTopK)
                ->setClientMessage($clientMessage)
                ->setLists($lists, $listsValues)
                ->setCheckboxes($checkboxes)
                ->setSystemMessage($systemMessage);

            if($entryTemplate) { $gptQuestionRequest->setEntryTemplate($entryTemplate); }
            if($listsMessageTemplate) { $gptQuestionRequest->setListsMessageTemplate($listsMessageTemplate); }
            if($checkboxesMessageTemplate) { $gptQuestionRequest->setCheckboxesMessageTemplate($checkboxesMessageTemplate); }
            if($clientMessageTemplate) { $gptQuestionRequest->setClientMessageTemplate($clientMessageTemplate); }
            if($raw) { $gptQuestionRequest->setRaw($raw); }

            $gptQuestionRequest->preparePrompt();

            $gptResponses = $this->AIService->questionChatRequest($gptService, $gptQuestionRequest);

            return new JsonResponse($gptResponses);
        } catch (Exception $e) {

            $requestParameters = $request->request->all();
            unset($requestParameters['gpt_api_key']);

            $this->logger->error(json_encode([
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request' => $requestParameters
            ], 1));

            return new JsonResponse([
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    public function assistantList(Request $request)
    {
        try {
            $constraintViolation = function(ValidatorInterface $validator, array $haystack):ConstraintViolationListInterface {
                $constraints = [new Collection([
                    'allowExtraFields' => false,
                    'fields' => [
                        'gpt_api_key' => [new Type(['type' => 'string'])],
                        'gpt_service' => [new Choice(AIService::list())]
                    ],
                ])];

                return $validator->validate($haystack, $constraints);
            };

            $errors = $constraintViolation($this->validator, $request->request->all());

            if(count($errors) > 0) {
                $messages = [];
                foreach ($errors as $violation) {
                    $messages[] = $violation->getPropertyPath().': '.$violation->getMessage();
                }

                throw new Exception('Validation failed: '. implode(' ', $messages));
            }

            $gptService = $request->request->get('gpt_service');
            $gptApiKey = $request->request->get('gpt_api_key');

            $response = $this->AIService->assistantList($gptService, $gptApiKey);

            return new JsonResponse($response);
        }
        catch (Exception $e) {

            $requestParameters = $request->request->all();
            unset($requestParameters['gpt_api_key']);

            $this->logger->error(json_encode([
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request' => $requestParameters
            ], 1));

            return new JsonResponse([
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * @param Request $request
     * @return JsonResponse
     * @throws Exception
     */
    public function assistantRequest(Request $request)
    {
        // Carriage return "\r" fix
        $request->request->add($this->carriageReturnFix($request->request->all()));

        try {
            $gptService = $request->request->get('gpt_service', 'openai');

            $errors = $this->validateAssistantRequest($this->validator, $gptService, $request->request->all());
            if(count($errors) > 0) {
                $messages = [];
                foreach ($errors as $violation) {
                    $messages[] = $violation->getPropertyPath().': '.$violation->getMessage();
                }

                throw new Exception('Validation failed: '. implode(' ', $messages));
            }

            $gptApiKey = $request->request->get('gpt_api_key');
            $gptAssistantId = $request->request->get('gpt_assistant_id');
            $gptModel = $request->request->get('gpt_model');
            $gptTemperature = $request->request->get('gpt_temperature');
            $gptTopP = $request->request->get('gpt_top_p');
            //$gptMaxTokens = $request->request->get('gpt_max_tokens', $this->gptMaxTokens);
            //$gptTokenLimit = $request->request->get('gpt_token_limit', $this->gptTokenLimit);
            $raw = $request->request->get('raw');
            $instructions = $request->request->get('instructions');
            $clientMessage = $request->request->get('client_message');
            $clientMessageTemplate = $request->request->get('client_message_template');

            // Assistant request

            $gptAssistantRequest = (new GptAssistantRequest())
                ->setApiKey($gptApiKey)
                ->setAssistantId($gptAssistantId)
                ->setClientMessage($clientMessage);
                //->setTokenLimit($gptTokenLimit)
                //->setMaxTokens($gptMaxTokens);

            if ($clientMessageTemplate) { $gptAssistantRequest->setClientMessageTemplate($clientMessageTemplate); }
            if ($gptModel) { $gptAssistantRequest->setModel($gptModel); }
            if ($gptTemperature) { $gptAssistantRequest->setModel($gptTemperature); }
            if ($gptTopP) { $gptAssistantRequest->setModel($gptTopP); }
            if ($instructions) { $gptAssistantRequest->setInstructions($instructions); }
            if ($raw) { $gptAssistantRequest->setRaw($raw); }

            $gptAssistantRequest->preparePrompt();
            $gptAssistantResponse = $this->AIService->assistantRequest($gptService, $gptAssistantRequest);

            return new JsonResponse($gptAssistantResponse);

        } catch (Exception $e) {
            $requestParameters = $request->request->all();
            unset($requestParameters['gpt_api_key']);

            $this->logger->error(json_encode([
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request' => $requestParameters
            ], 1));

            return new JsonResponse([
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function vectorize(Request $request)
    {
        try {
            $constraintViolation = function(ValidatorInterface $validator, array $haystack):ConstraintViolationListInterface {
                $constraints = [new Collection([
                    'allowExtraFields' => false,
                    'fields' => [
                        'account_id' => [new Optional([new Type(['type' => 'string'])])],
                        'gpt_api_key' => [new Optional([new Type(['type' => 'string'])])],
                        'gpt_service' => [new Choice(AIService::list())],
                        'gpt_embedding_model' => [new Optional([new Type(['type' => 'string'])])],
                        'index'=> [new Optional([ /*new Expression([
                            'expression' => 'gpt_service == cloudflare_service && null != value',
                            'values' => [
                                'gpt_service' => $haystack['gpt_service'],
                                'cloudflare_service' => CloudflareClient::SERVICE
                            ],
                            'message' => 'Field is required when GPT-Service is "[cloudflare_service]"',
                        ]),*/ new EntityExist(CloudflareIndex::class, 'name')])],
                        'gpt_max_tokens_per_chunk' => [new Optional([new Type(['type' => 'numeric'])])],
                    ],
                ])];

                return $validator->validate($haystack, $constraints);
            };

            $errors = $constraintViolation($this->validator, $request->request->all());

            if(count($errors) > 0) {
                $messages = [];
                foreach ($errors as $violation) {
                    $messages[] = $violation->getPropertyPath().': '.$violation->getMessage();
                }

                throw new Exception('Validation failed: '. implode(' ', $messages));
            }

            $gptService = $request->request->get('gpt_service');
            $accountId = $request->request->get('account_id');
            $gptApiKey = $request->request->get('gpt_api_key');
            $gptEmbeddingModel = $request->request->get('gpt_embedding_model', $this->gptEmbeddingModel);
            $index = $request->request->get('index');
            $gptMaxTokensPerChunk = $request->request->get('gpt_max_tokens_per_chunk', $this->gptMaxTokensPerChunk);

            $this->bus->dispatch(new Vectorize($gptService, $accountId, $gptApiKey, $gptEmbeddingModel, $index, $gptMaxTokensPerChunk));

            return new JsonResponse([
                'success' => true,
                'message' => 'The process of converting articles to embeddings has been started. Please wait for its completion.'
            ]);
        }
        catch (Exception $e) {

            $requestParameters = $request->request->all();
            unset($requestParameters['gpt_api_key']);

            $this->logger->error(json_encode([
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request' => $requestParameters
            ], 1));

            return new JsonResponse([
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * @param Request $request
     * @param EntityManagerInterface $entityManager
     * @param LoggerInterface $embeddingLogger
     * @return JsonResponse
     */
    public function search(Request $request, EntityManagerInterface $entityManager, LoggerInterface $embeddingLogger)
    {
        // Carriage return "\r" fix
        $request->request->add($this->carriageReturnFix($request->request->all()));

        try {
            $constraintViolation = function(ValidatorInterface $validator, array $haystack):ConstraintViolationListInterface {
                $constraints = [new Collection([
                    'allowExtraFields' => false,
                    'fields' => [
                        'gpt_api_key' => [new Type(['type' => 'string'])],
                        'account_id' => [new Optional([new Type(['type' => 'string'])])],
                        'gpt_service' => [new Choice(AIService::list())],
                        'gpt_embedding_model' => [new Optional([new Type(['type' => 'string'])])],
                        'gpt_model' => [new Optional([new Type(['type' => 'string'])])],
                        'index'=> [(CloudflareClient::SERVICE === $haystack['gpt_service']) ? new EntityExist(CloudflareIndex::class, 'name') : new Optional([new Type(['type' => 'string'])])],
                        'gpt_temperature' => [new Optional([new Type(['type' => 'numeric']), new Range(['min' => 0, 'max' => 2])])],
                        'gpt_max_tokens' => [new Optional([new Type(['type' => 'numeric'])])],
                        'gpt_token_limit' => [new Optional([new Type(['type' => 'numeric'])])],
                        'gpt_frequency_penalty' => [new Optional([new Type(['type' => 'numeric']), new Range(['min' => 0, 'max' => 2])])],
                        'gpt_presence_penalty' => [new Optional([new Type(['type' => 'numeric']), new Range(['min' => 0, 'max' => 2])])],
                        'vector_search_result_count' => [new Optional([new Type(['type' => 'numeric'])])],
                        'vector_search_distance_limit' => [new Optional([new Type(['type' => 'numeric'])])],
                        'user_message_template' => [new Optional([new Type(['type' => 'string'])])],
                        'question' => [new Optional([new Type(['type' => 'string'])])],
                        'system_message' => [new Optional([new Type(['type' => 'string'])])]
                    ],
                ])];

                return $validator->validate($haystack, $constraints);
            };

            $errors = $constraintViolation($this->validator, $request->request->all());

            if(count($errors) > 0) {
                $messages = [];
                foreach ($errors as $violation) {
                    $messages[] = $violation->getPropertyPath().': '.$violation->getMessage();
                }

                throw new Exception('Validation failed: '. implode(' ', $messages));
            }

            $gptService = $request->request->get('gpt_service');
            $gptApiKey = $request->request->get('gpt_api_key');
            $accountId = $request->request->get('account_id');
            $gptEmbeddingModel = $request->request->get('gpt_embedding_model', $this->gptEmbeddingModel);
            $gptModel = $request->request->get('gpt_model', $this->gptModel);
            $index = $request->request->get('index');
            $gptTemperature = $request->request->get('gpt_temperature', $this->gptTemperature);
            $gptMaxTokens = $request->request->get('gpt_max_tokens', $this->gptMaxTokens);
            $gptFrequencyPenalty = $request->request->get('gpt_frequency_penalty', $this->gptFrequencyPenalty);
            $gptPresencePenalty = $request->request->get('gpt_presence_penalty', $this->gptPresencePenalty);
            $gptResponseFormatType = $request->request->get('gpt_response_format_type', $this->gptResponseFormatType);

            $vectorSearchResultCount = $request->request->get('vector_search_result_count', 2);
            $vectorSearchDistanceLimit = $request->request->get('vector_search_distance_limit', 0.99);

            $question = $request->request->get('question');
            $systemMessage = $request->request->get('system_message', '');
            $userMessageTemplate = $request->request->get('user_message_template');

            // Gpt Request

            $promptEmbeddingRequest = (new GptEmbeddingRequest())
                ->setApiKey($gptApiKey)
                ->setAccountId($accountId)
                ->setModel($gptEmbeddingModel)
                ->setIndex($index)
                ->setPrompt($question);

            $promptEmbedding = $this->AIService->embedding($gptService, $promptEmbeddingRequest);
            $searchResult = $this->AIService->search($gptService, $promptEmbeddingRequest, $promptEmbedding, $vectorSearchResultCount, $vectorSearchDistanceLimit);

            /**
             * Already involved in content Article Paragraphs
             * @var array $articleParagraphIdCollection
             */
            $articleParagraphIdCollection = [];

            /** @var string|null $content */
            $content = null;

            foreach ($searchResult as $key => &$searchResponse) {
                switch($searchResponse->type) {
                    case Article::TYPE:
                        $qb = $entityManager->createQueryBuilder();
                        $qb->select('a')
                            ->from('App\Entity\Article', 'a')
                            ->where('a.id = :id')
                            ->andWhere('a.active = true')
                            ->setParameters([
                                'id' => $searchResponse->id
                            ]);

                        /** @var Article $article */
                        $article = $qb->getQuery()->getOneOrNullResult();

                        /** @var ArticleParagraph $articleParagraph */
                        $articleParagraph = $article ? $article->getParagraphs()->first() : null;

                        if(!$article || !$articleParagraph || in_array($articleParagraph->getId(), $articleParagraphIdCollection)) {
                            //unset($searchResult[$key]);
                            break;
                        }

                        //$content .= $article->getArticleContent().PHP_EOL;
                        $content .= $articleParagraph->getParagraphContent();

                        $articleJson = $this->serializer->serialize($article, 'json', [
                            AbstractNormalizer::ATTRIBUTES => [
                                'id',
                                'externalId',
                                'articleTitle',
                                /*'paragraphs' => [
                                    'id',
                                    'article',
                                    'paragraphTitle',
                                    'paragraphContent',
                                ]*/
                            ],
                            'circular_reference_handler' => function ($object) {
                                return $object->getId();
                            }
                        ]);

                        $searchResponse->entity = json_decode($articleJson, 1);

                        break;

                    case ArticleParagraph::TYPE:
                        $qb = $entityManager->createQueryBuilder();
                        $qb->select('ap')
                            ->from('App\Entity\ArticleParagraph', 'ap')
                            ->join('ap.article', 'a')
                            ->where('ap.id = :id')
                            ->andWhere('a.active = true')
                            ->setParameters([
                                'id' => $searchResponse->id
                            ]);

                        /** @var ArticleParagraph $articleParagraph */
                        $articleParagraph = $qb->getQuery()->getOneOrNullResult();

                        if(!$articleParagraph || in_array($articleParagraph->getId(), $articleParagraphIdCollection)) {
                            //unset($searchResult[$key]);
                            break;
                        }

                        $content .= $articleParagraph->getParagraphContent().PHP_EOL;

                        $articleParagraphJson = $this->serializer->serialize($articleParagraph, 'json', [
                            AbstractNormalizer::ATTRIBUTES => [
                                'id',
                                'paragraphTitle',
                                'article' => [
                                    'id',
                                    'externalId',
                                    'articleTitle'
                                ],
                            ],
                            AbstractNormalizer::CIRCULAR_REFERENCE_HANDLER => function ($object) {
                                return $object->getId();
                            }
                        ]);

                        $searchResponse->entity = json_decode($articleParagraphJson, 1);

                        break;

                    case Template::TYPE:
                        $qb = $entityManager->createQueryBuilder();
                        $qb->select('t')
                            ->from('App\Entity\Template', 't')
                            ->where('t.id = :id')
                            ->setParameters([
                                'id' => $searchResponse->id
                            ]);

                        $template = $qb->getQuery()->getOneOrNullResult();

                        if(!$template) {
                            //unset($searchResult[$key]);
                            break;
                        }

                        $content .= $template->getTemplateContent().PHP_EOL;

                        $templateJson = $this->serializer->serialize($template, 'json', [
                            AbstractNormalizer::ATTRIBUTES => [
                                'id',
                                'externalId',
                                'templateTitle',
                                'templateContent'
                            ],
                            'circular_reference_handler' => function ($object) {
                                return $object->getId();
                            }
                        ]);

                        $searchResponse->entity = json_decode($templateJson, 1);

                        break;
                }
            }

            $gptKnowledgebaseRequest = (new GptKnowledgebaseRequest())
                ->setApiKey($gptApiKey)
                ->setAccountId($accountId)
                ->setModel($gptModel)
                ->setTemperature($gptTemperature)
                ->setMaxTokens($gptMaxTokens)
                ->setFrequencyPenalty($gptFrequencyPenalty)
                ->setPresencePenalty($gptPresencePenalty)
                ->setResponseFormatType($gptResponseFormatType)

                ->setSystemMessage($systemMessage)
                ->setQuestion($question)
                ->setContent($content);

            if($userMessageTemplate){
                $gptKnowledgebaseRequest->setUserMessageTemplate($userMessageTemplate);
            }

            $gptKnowledgebaseRequest->preparePrompt();
            $gptResponse = $this->AIService->knowledgebaseChatRequest($gptService, $gptKnowledgebaseRequest);

            $embeddingLogger->debug('Question: '.$question);
            $embeddingLogger->debug('Search Result: '.json_encode($searchResult));
            $embeddingLogger->debug('Request Object: '.json_encode($gptKnowledgebaseRequest));
            $embeddingLogger->debug('Response Object: '.json_encode($gptResponse));
            $embeddingLogger->debug('---');

            return new JsonResponse([
                'gpt_response' => $gptResponse,
                'search_result' => $searchResult,
            ]);
        }
        catch (Exception $e) {
            $requestParameters = $request->request->all();
            unset($requestParameters['gpt_api_key']);

            $this->logger->error(json_encode([
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request' => $requestParameters
            ], 1));

            return new JsonResponse([
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * @param Request $request
     * @param MessageRepository $messageRepository
     * @return JsonResponse
     */
    public function summarize(Request $request, MessageRepository $messageRepository)
    {
        try {
            $constraintViolation = function(ValidatorInterface $validator, array $haystack):ConstraintViolationListInterface {
                $constraints = [new Collection([
                    'allowExtraFields' => false,
                    'fields' => [
                        'gpt_api_key' => [new Type(['type' => 'string'])],
                        'gpt_service' => [new Choice(AIService::list())],
                        'gpt_model' => [new Optional([new Type(['type' => 'string'])])],
                        'gpt_temperature' => [new Optional([new Type(['type' => 'numeric']), new Range(['min' => 0, 'max' => 2])])],
                        'gpt_max_tokens' => [new Optional([new Type(['type' => 'numeric'])])],
                        'gpt_token_limit' => [new Optional([new Type(['type' => 'numeric'])])],
                        'gpt_frequency_penalty' => [new Optional([new Type(['type' => 'numeric']), new Range(['min' => 0, 'max' => 2])])],
                        'gpt_presence_penalty' => [new Optional([new Type(['type' => 'numeric']), new Range(['min' => 0, 'max' => 2])])],
                        'main_prompt_template' => [new Optional(new Type(['type' => 'string']))],
                        'chunk_summarize_prompt_template' => [new Optional(new Type(['type' => 'string']))],
                        'summaries_summarize_prompt_template' => [new Optional(new Type(['type' => 'string']))],
                        'system_message' => [new Optional([new Type(['type' => 'string'])])],
                        'messages' => [new All([
                            new Type(['type' => 'numeric']),
                            new EntityExist(Message::class, 'id')
                        ])]
                    ],
                ])];

                return $validator->validate($haystack, $constraints);
            };

            $errors = $constraintViolation($this->validator, $request->request->all());

            if(count($errors) > 0) {
                $messages = [];
                foreach ($errors as $violation) {
                    $messages[] = $violation->getPropertyPath().': '.$violation->getMessage();
                }

                throw new Exception('Validation failed: '. implode(' ', $messages));
            }

            $gptService = $request->request->get('gpt_service');
            $gptApiKey = $request->request->get('gpt_api_key');
            $gptModel = $request->request->get('gpt_model', $this->gptModel);
            $gptTemperature = $request->request->get('gpt_temperature', $this->gptTemperature);
            $gptMaxTokens = $request->request->get('gpt_max_tokens', $this->gptMaxTokens);
            $gptTokenLimit = $request->request->get('gpt_token_limit', $this->gptTokenLimit);
            $gptFrequencyPenalty = $request->request->get('gpt_frequency_penalty', $this->gptFrequencyPenalty);
            $gptPresencePenalty = $request->request->get('gpt_presence_penalty', $this->gptPresencePenalty);

            $mainPromptTemplate = $request->request->get('main_prompt_template');
            $chunkSummarizePromptTemplate = $request->request->get('chunk_summarize_prompt_template');
            $summariesSummarizePromptTemplate = $request->request->get('summaries_summarize_prompt_template');
            $systemMessage = $request->request->get('system_message', '');
            $messageIds = $request->request->get('messages');

            // Gpt Request
            $gptSummarizeRequest = (new GptSummarizeRequest())
                ->setApiKey($gptApiKey)
                ->setModel($gptModel)
                ->setTemperature($gptTemperature)
                ->setMaxTokens($gptMaxTokens)
                ->setTokenLimit($gptTokenLimit)
                ->setFrequencyPenalty($gptFrequencyPenalty)
                ->setPresencePenalty($gptPresencePenalty)
                ->setSystemMessage($systemMessage);

            if($mainPromptTemplate)
                $gptSummarizeRequest->setMainPromptTemplate($mainPromptTemplate);

            if($chunkSummarizePromptTemplate)
                $gptSummarizeRequest->setChunkSummarizePromptTemplate($chunkSummarizePromptTemplate);

            if($summariesSummarizePromptTemplate)
                $gptSummarizeRequest->setSummariesSummarizePromptTemplate($summariesSummarizePromptTemplate);

            foreach ($messageIds as $messageId) {
                $message = $messageRepository->find($messageId);
                $gptSummarizeRequest->addMessage($message->getContent());
            }
            $gptSummarizeRequest->prepareMainPrompt();

            $gptResponses = $this->AIService->summarizeRequest($gptService, $gptSummarizeRequest);

            $response = [];

            // Store Messages & build response
            /** @var GptResponse $gptResponse */
            foreach($gptResponses as $gptResponse) {
                // Store Message
                $message = new Message();

                $message->setExternalUserId(0);
                $message->setExternalStaffId(0);
                $message->setContent($gptResponse->message);
                $message->setMessageType('reply_ai');
                $message->setSentAt(new \DateTime($gptResponse->datetime));

                $messageRepository->add($message);

                $response[] = [
                    'gpt_response' => $gptResponse,
                    'message' => json_decode($this->serializer->serialize($message, 'json'), 1),
                ];
            }

            return new JsonResponse($response);
        }
        catch (Exception $e) {
            $requestParameters = $request->request->all();
            unset($requestParameters['gpt_api_key']);

            $this->logger->error(json_encode([
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request' => $requestParameters
            ], 1));

            return new JsonResponse([
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * @param ValidatorInterface $validator
     * @param string $gptService
     * @param array $haystack
     * @return ConstraintViolationListInterface
     */
    private function validateGptRequest(ValidatorInterface $validator, string $gptService, array $haystack):ConstraintViolationListInterface
    {
        $raw = [];

        switch ($gptService) {
            case OpenAIClient::SERVICE:
                $raw = [new Optional([new Json(), new Callback([
                    'callback' => function ($json, $context) use($validator) {
                        $haystack = json_decode($json, 1);
                        $constraints = [new Collection([
                            'allowExtraFields' => false,
                            'fields' => [
                                'model' => [new Type(['type' => 'string'])],
                                'messages' => [new All([
                                    'constraints' => [
                                        new Collection([
                                            'allowExtraFields' => false,
                                            'fields' => [
                                                'role' => [new Choice(['system', 'user', 'assistant'])],
                                                'content' => [new Type(['type' => 'string'])]
                                            ]
                                        ])
                                    ],
                                ])],
                                'temperature' => [new Optional(new Type(['type' => 'numeric']))],
                                'max_tokens' => [new Optional(new Type(['type' => 'integer']))],
                                'frequency_penalty' => [new Optional(new Type(['type' => 'numeric']))],
                                'presence_penalty' => [new Optional(new Type(['type' => 'numeric']))],
                                'response_format' => [new Optional(new Collection([
                                    'allowExtraFields' => false,
                                    'fields' => ['type' => [new Optional([new Choice(['json_object', 'text'])])]],
                                ]))],
                            ],
                        ])];

                        $errors = $validator->validate($haystack, $constraints);

                        if(count($errors) > 0) {
                            $messages = [];
                            foreach ($errors as $violation) {
                                $messages[] = $violation->getPropertyPath().': '.$violation->getMessage();
                            }

                            $context->buildViolation('JSON validation failed: '. implode(' ', $messages))->addViolation();
                        }
                    }
                ])])];

                break;

            case YandexGptClient::SERVICE:
                $raw = [new Optional([new Json(), new Callback([
                    'callback' => function ($json, $context) use($validator) {
                        $haystack = json_decode($json, 1);
                        $constraints = [new Collection([
                            'allowExtraFields' => false,
                            'fields' => [
                                'modelUri' => [new Type(['type' => 'string'])],
                                'completionOptions' => [
                                    new Collection([
                                        'allowExtraFields' => false,
                                        'fields' => [
                                            'stream' => [new Optional(new Type(['type' => 'boolean']))],
                                            'temperature' => [new Optional(new Type(['type' => 'numeric']))],
                                            'maxTokens' => [new Optional()]
                                        ]
                                    ])
                                ],
                                'messages' => [new All([
                                    'constraints' => [
                                        new Collection([
                                            'allowExtraFields' => false,
                                            'fields' => [
                                                'role' => [new Choice(['system', 'user', 'assistant'])],
                                                'text' => [new Type(['type' => 'string'])]
                                            ]
                                        ])
                                    ],
                                ])]
                            ],
                        ])];

                        $errors = $validator->validate($haystack, $constraints);

                        if(count($errors) > 0) {
                            $messages = [];
                            foreach ($errors as $violation) {
                                $messages[] = $violation->getPropertyPath().': '.$violation->getMessage();
                            }

                            $context->buildViolation('JSON validation failed: '. implode(' ', $messages))->addViolation();
                        }
                    }
                ])])];

                break;

            case GeminiClient::SERVICE:

                $raw = [new Optional([new Json(), new Callback([
                    'callback' => function ($json, $context) use($validator) {
                        $haystack = json_decode($json, 1);
                        $constraints = [new Collection([
                            'allowExtraFields' => false,
                            'fields' => [
                                'model' => [new Type(['type' => 'string'])],
                                'contents' => [new All([
                                    'constraints' => [
                                        new Collection([
                                            'allowExtraFields' => false,
                                            'fields' => [
                                                'role' => [new Choice(['system', 'user', 'assistant'])],
                                                'parts' => [new Collection([
                                                    'allowExtraFields' => false,
                                                    'fields' => [
                                                        'text' => [new Type(['type' => 'string'])]
                                                    ]
                                                ])]
                                            ]
                                        ])
                                    ],
                                ])],
                                'generationConfig' => [new Collection([
                                    'allowExtraFields' => true,
                                    'fields' => [
                                        'responseMimeType' => [new Optional(new Type(['type' => 'string']))],
                                        'temperature' => [new Optional(new Type(['type' => 'numeric']))],
                                        'maxOutputTokens' => [new Optional(new Type(['type' => 'integer']))],
                                        'topP' => [new Optional([new Type(['type' => 'numeric'])/*, new Range(['min' => 0, 'max' => 1000])*/])],
                                        'topK' => [new Optional([new Type(['type' => 'integer'])/*, new Range(['min' => 0, 'max' => 1])*/])],
                                    ],
                                ])],
                            ],
                        ])];

                        $errors = $validator->validate($haystack, $constraints);

                        if(count($errors) > 0) {
                            $messages = [];
                            foreach ($errors as $violation) {
                                $messages[] = $violation->getPropertyPath().': '.$violation->getMessage();
                            }

                            $context->buildViolation('JSON validation failed: '. implode(' ', $messages))->addViolation();
                        }
                    }
                ])])];

                break;
        }

        $constraints = [new Collection([
            'allowExtraFields' => true,
            'fields' => [
                'gpt_api_key' => [new Type(['type' => 'string'])],
                'gpt_service' => [new Choice(AIService::list())],
                'gpt_model' => [new Optional([new Type(['type' => 'string'])])],
                'gpt_temperature' => [new Optional([new Type(['type' => 'numeric']), new Range(['min' => 0, 'max' => 2])])],
                'gpt_max_tokens' => [new Optional([new Type(['type' => 'numeric'])])],
                'gpt_token_limit' => [new Optional([new Type(['type' => 'numeric'])])],
                'gpt_frequency_penalty' => [new Optional([new Type(['type' => 'numeric']), new Range(['min' => 0, 'max' => 2])])],
                'gpt_presence_penalty' => [new Optional([new Type(['type' => 'numeric']), new Range(['min' => 0, 'max' => 2])])],
                'raw' => $raw,
                'entry_template' => [new Optional(new Type(['type' => 'string']))],
                'client_message' => [new Type(['type' => 'string'])],
                'client_message_template' => [new Optional(new Type(['type' => 'string']))],
                'lists' => [new Optional([new All([
                    'constraints' => [
                        new Type(['type' => 'string']),
                    ],
                ]), new Expression([
                    'expression' => 'lists_count == lists_values_count',
                    'values' => [
                        'lists_count' => isset($haystack['lists']) ? count($haystack['lists']) : 0,
                        'lists_values_count' => isset($haystack['lists_values']) ? count($haystack['lists_values']) : 0,
                    ],
                    'message' => 'The fields [lists] and [lists_values] should be the same length.',
                ])])],
                'lists_values' => [new Optional([new All([
                    'constraints' => [
                        new All([
                            'constraints' => [
                                new Type(['type' => 'string']),
                            ],
                        ]),
                    ],
                ]), new Expression([
                    'expression' => 'lists_count == lists_values_count',
                    'values' => [
                        'lists_count' => isset($haystack['lists']) ? count($haystack['lists']) : 0,
                        'lists_values_count' => isset($haystack['lists_values']) ? count($haystack['lists_values']) : 0,
                    ],
                    'message' => 'The fields [lists] and [lists_values] this should be the same length.',
                ])])],
                'lists_message_template' => [new Optional(new Type(['type' => 'string']))],
                'checkboxes' => new Optional([new All([
                    'constraints' => [
                        new Type(['type' => 'string']),
                    ],
                ])]),
                'checkboxes_message_template' => [new Optional(new Type(['type' => 'string']))],
                'system_message' => [new Optional([new Type(['type' => 'string'])])]
            ],
        ])];

        return $validator->validate($haystack, $constraints);
    }

    /**
     * @param ValidatorInterface $validator
     * @param string $gptService
     * @param array $haystack
     * @return ConstraintViolationListInterface
     */
    private function validateAssistantRequest(ValidatorInterface $validator, string $gptService, array $haystack):ConstraintViolationListInterface
    {
        $raw = [];

        switch ($gptService) {
            case OpenAIClient::SERVICE:
                $raw = [new Optional([new Json(), new Callback([
                    'callback' => function ($json, $context) use($validator) {
                        $haystack = json_decode($json, 1);
                        $constraints = [new Collection([
                            'allowExtraFields' => false,
                            'fields' => [
                                'assistant_id' => [new Type(['type' => 'string'])],
                                'model' => [new Optional([new Type(['type' => 'string'])])],
                                'instructions' => [new Optional([new Type(['type' => 'string'])])],
                                'thread' => [new Collection([
                                    'allowExtraFields' => false,
                                    'fields' => [
                                        'messages' => [new All([
                                            'constraints' => [new Collection([
                                                'allowExtraFields' => false,
                                                'fields' => [
                                                    'role' => [new Choice(['system', 'user', 'assistant'])],
                                                    'content' => [new Type(['type' => 'string'])]
                                                ]
                                            ])]
                                        ])]
                                    ]
                                ])],
                                'temperature' => [new Optional(new Type(['type' => 'numeric']))],
                                'top_p' => [new Optional(new Type(['type' => 'numeric']))],
                                'max_prompt_tokens' => [new Optional(new Type(['type' => 'integer']))],
                                'max_completion_tokens' => [new Optional(new Type(['type' => 'integer']))],
                                'response_format' => [new Optional(new Collection([
                                    'allowExtraFields' => false,
                                    'fields' => ['type' => [new Optional([new Choice(['json_object', 'text'])])]],
                                ]))],
                            ],
                        ])];

                        $errors = $validator->validate($haystack, $constraints);

                        if(count($errors) > 0) {
                            $messages = [];
                            foreach ($errors as $violation) {
                                $messages[] = $violation->getPropertyPath().': '.$violation->getMessage();
                            }

                            $context->buildViolation('JSON validation failed: '. implode(' ', $messages))->addViolation();
                        }
                    }
                ])])];

                break;
            case YandexGptClient::SERVICE:
                break;
            case GeminiClient::SERVICE:
                break;
        }

        $constraints = [new Collection([
            'allowExtraFields' => false,
            'fields' => [
                'gpt_api_key' => [new Type(['type' => 'string'])],
                'gpt_service' => [new Choice(AIService::list())],
                'gpt_model' => [new Optional([new Type(['type' => 'string'])])],
                'gpt_temperature' => [new Optional([new Type(['type' => 'numeric']), new Range(['min' => 0, 'max' => 2])])],
                'gpt_top_p' => [new Optional([new Type(['type' => 'numeric']), new Range(['min' => 0, 'max' => 1])])],
                'gpt_assistant_id' => [new Type(['type' => 'string'])],
                'raw' => $raw,
                'instructions' => [new Optional([new Type(['type' => 'string'])])],
                'client_message' => [new Type(['type' => 'string'])],
                'client_message_template' => [new Optional(new Type(['type' => 'string']))],
            ],
        ])];

        return $validator->validate($haystack, $constraints);
    }


    /**
     * @param array $haystack
     * @return array
     */
    private function carriageReturnFix(array $haystack):array
    {
        $json = json_encode($haystack);
        $data = json_decode(str_replace('\r', '', $json), 1);

        return $data;
    }
}
