<?php

namespace App\Controller;

use App\Entity\CloudflareIndex;
use App\Entity\GptSearchOption;
use App\Repository\CloudflareIndexRepository;
use App\Repository\GptSearchOptionRepository;
use App\Service\Gpt\AIService;
use App\Service\Gpt\CloudflareClient;
use App\Validator\EntityExist;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Constraints\Choice;
use Symfony\Component\Validator\Constraints\Collection;
use Symfony\Component\Validator\Constraints\Expression;
use Symfony\Component\Validator\Constraints\Optional;
use Symfony\Component\Validator\Constraints\Range;
use Symfony\Component\Validator\Constraints\Type;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class GptSearchOptionController extends AbstractController
{
    private $validator;
    private $serializer;
    private $normalizer;

    public function __construct(ValidatorInterface $validator, SerializerInterface $serializer, NormalizerInterface $normalizer)
    {
        $this->validator = $validator;
        $this->serializer = $serializer;
        $this->normalizer = $normalizer;
    }

    public function save(Request $request, GptSearchOptionRepository $gptSearchOptionRepository, CloudflareIndexRepository $cloudflareIndexRepository)
    {
        try {
            $constraintViolation = function(ValidatorInterface $validator, array $haystack): ConstraintViolationListInterface {
                $constraints = [new Collection([
                    'allowExtraFields' => false,
                    'fields' => [
                        'gpt_service' => [new Choice(AIService::list())],
                        'gpt_embedding_model' => [new Optional([new Type(['type' => 'string'])])],
                        'gpt_model' => [new Optional([new Type(['type' => 'string'])])],
                        'index' => [new Optional([ /*new Expression([
                            'expression' => 'gpt_service == cloudflare_service && null != value',
                            'values' => [
                                'gpt_service' => $haystack['gpt_service'],
                                'cloudflare_service' => CloudflareClient::SERVICE
                            ],
                            'message' => 'Field is required when GPT-Service is "[cloudflare_service]"',
                        ]),*/ new EntityExist(CloudflareIndex::class, 'name')])],
                        'gpt_temperature' => [new Optional([new Type(['type' => 'numeric']), new Range(['min' => 0, 'max' => 2])])],
                        'gpt_max_tokens' => [new Optional([new Type(['type' => 'numeric'])])],
                        'gpt_token_limit' => [new Optional([new Type(['type' => 'numeric'])])],
                        'gpt_frequency_penalty' => [new Optional([new Type(['type' => 'numeric']), new Range(['min' => 0, 'max' => 2])])],
                        'gpt_presence_penalty' => [new Optional([new Type(['type' => 'numeric']), new Range(['min' => 0, 'max' => 2])])],
                        'vector_search_result_count' => [new Optional([new Type(['type' => 'numeric'])])],
                        'vector_search_distance_limit' => [new Optional([new Type(['type' => 'numeric'])])],
                        'user_message_template' => [new Optional([new Type(['type' => 'string'])])],
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

            $cloudflareIndex = $cloudflareIndexRepository->findOneBy(['name' => $request->request->get('index')]);

            $option = $gptSearchOptionRepository->findOneBy([]) ?? new GptSearchOption();

            $option->setGptService($request->request->get('gpt_service'));
            $option->setEmbeddingModel($request->request->get('gpt_embedding_model'));
            $option->setCloudflareIndex($cloudflareIndex);
            $option->setVectorSearchResultCount($request->request->get('vector_search_result_count'));
            $option->setVectorSearchDistanceLimit($request->request->get('vector_search_distance_limit'));
            $option->setChatModel($request->request->get('gpt_model'));
            $option->setTemperature($request->request->get('gpt_temperature'));
            $option->setMaxTokens($request->request->get('gpt_max_tokens'));
            $option->setFrequencyPenalty($request->request->get('gpt_frequency_penalty'));
            $option->setPresencePenalty($request->request->get('gpt_presence_penalty'));
            $option->setSystemMessage($request->request->get('system_message'));
            $option->setUserMessageTemplate($request->request->get('user_message_template'));

            $gptSearchOptionRepository->add($option);

            return new JsonResponse(
                json_decode($this->serializer->serialize($option, 'json', [
                        AbstractNormalizer::ATTRIBUTES => [
                            'id',
                            'gptService',
                            'embeddingModel',
                            'vectorSearchResultCount',
                            'vectorSearchDistanceLimit',
                            'chatModel',
                            'temperature',
                            'maxTokens',
                            'frequencyPenalty',
                            'presencePenalty',
                            'systemMessage',
                            'userMessageTemplate',
                            'cloudflareIndex' => [
                                'id',
                                'name',
                                'description',
                                'dimensions',
                                'metric',
                                'createdAt',
                                'updatedAt'
                            ],
                        ],
                        AbstractNormalizer::CIRCULAR_REFERENCE_HANDLER => function ($object) {
                            return $object->getId();
                        }
                    ]
                ), true)
            );
        }
        catch (Exception $e) {
            return new JsonResponse([
                'error' => $e->getMessage(),
            ], 400);
        }
    }
}
