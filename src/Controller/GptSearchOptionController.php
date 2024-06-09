<?php

namespace App\Controller;

use App\Entity\GptSearchOption;
use App\Repository\GptSearchOptionRepository;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Constraints\Choice;
use Symfony\Component\Validator\Constraints\Collection;
use Symfony\Component\Validator\Constraints\Optional;
use Symfony\Component\Validator\Constraints\Range;
use Symfony\Component\Validator\Constraints\Type;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class GptSearchOptionController extends AbstractController
{
    private $validator;
    private $serializer;

    public function __construct(ValidatorInterface $validator, SerializerInterface $serializer)
    {
        $this->validator = $validator;
        $this->serializer = $serializer;
    }

    public function save(Request $request, GptSearchOptionRepository $gptSearchOptionRepository)
    {
        try {
            $constraintViolation = function(ValidatorInterface $validator, array $haystack) : ConstraintViolationListInterface {
                $constraints = [new Collection([
                    'allowExtraFields' => false,
                    'fields' => [
                        'gpt_service' => [new Choice(['openai', 'yandex-gpt'])],
                        'gpt_embedding_model' => [new Optional([new Type(['type' => 'string'])])],
                        'gpt_model' => [new Optional([new Type(['type' => 'string'])])],
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

            $option = $gptSearchOptionRepository->findOneBy(['gptService' => $request->request->get('gpt_service')]) ?? new GptSearchOption();

            $option->setGptService($request->request->get('gpt_service'));
            $option->setEmbeddingModel($request->request->get('gpt_embedding_model'));
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
                json_decode($this->serializer->serialize($option, 'json'), true)
            );
        }
        catch (Exception $e) {
            return new JsonResponse([
                'error' => $e->getMessage(),
            ], 400);
        }
    }
}
