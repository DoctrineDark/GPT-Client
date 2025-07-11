<?php

namespace App\Controller;

use App\Entity\GptRequestOption;
use App\Repository\GptRequestOptionRepository;
use App\Service\Gpt\AIService;
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

class GptRequestOptionController extends AbstractController
{
    private $validator;
    private $serializer;

    public function __construct(ValidatorInterface $validator, SerializerInterface $serializer)
    {
        $this->validator = $validator;
        $this->serializer = $serializer;
    }

    public function save(Request $request, GptRequestOptionRepository $gptRequestOptionRepository)
    {
        try {
            $constraintViolation = function(ValidatorInterface $validator, array $haystack) : ConstraintViolationListInterface {
                $constraints = [new Collection([
                    'allowExtraFields' => false,
                    'fields' => [
                        'gpt_service' => [new Choice(AIService::list())],
                        'gpt_model' => [new Optional([new Type(['type' => 'string'])])],
                        'gpt_temperature' => [new Optional([new Type(['type' => 'numeric']), new Range(['min' => 0, 'max' => 2])])],
                        'gpt_max_tokens' => [new Optional([new Type(['type' => 'numeric'])])],
                        'gpt_token_limit' => [new Optional([new Type(['type' => 'numeric'])])],
                        'gpt_frequency_penalty' => [new Optional([new Type(['type' => 'numeric']), new Range(['min' => 0, 'max' => 2])])],
                        'gpt_presence_penalty' => [new Optional([new Type(['type' => 'numeric']), new Range(['min' => 0, 'max' => 2])])],
                        'gpt_response_content_type' => [new Optional(new Type(['type' => 'string']))],
                        'gpt_top_p' => [new Optional([new Type(['type' => 'numeric'])/*, new Range(['min' => 0, 'max' => 1])*/])],
                        'gpt_top_k' => [new Optional([new Type(['type' => 'digit'])/*, new Range(['min' => 0, 'max' => 1])*/])],
                        'system_message' => [new Optional([new Type(['type' => 'string'])])],
                        'entry_template' => [new Optional(new Type(['type' => 'string']))],
                        'lists_message_template' => [new Optional(new Type(['type' => 'string']))],
                        'checkboxes_message_template' => [new Optional(new Type(['type' => 'string']))],
                        'client_message_template' => [new Optional(new Type(['type' => 'string']))],
                        'raw' => [new Optional(new Type(['type' => 'string']))],
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

            $clientMessageTemplate = $request->request->get('client_message_template');
            $rawRequestTemplate = $request->request->get('raw');

            $option = $gptRequestOptionRepository->findOneBy([]) ?? new GptRequestOption();

            $option->setGptService($request->request->get('gpt_service'));
            $option->setModel($request->request->get('gpt_model'));
            $option->setTemperature($request->request->get('gpt_temperature'));
            $option->setMaxTokens($request->request->get('gpt_max_tokens'));
            $option->setPromptTokenLimit($request->request->get('gpt_token_limit'));
            $option->setFrequencyPenalty($request->request->get('gpt_frequency_penalty'));
            $option->setResponseContentType($request->request->get('gpt_response_content_type'));
            $option->setTopP($request->request->get('gpt_top_p'));
            $option->setTopK($request->request->get('gpt_top_k'));
            $option->setPresencePenalty($request->request->get('gpt_presence_penalty'));
            $option->setSystemMessage($request->request->get('system_message'));
            $option->setEntryTemplate($request->request->get('entry_template'));
            $option->setListsMessageTemplate($request->request->get('lists_message_template'));
            $option->setCheckboxesMessageTemplate($request->request->get('checkboxes_message_template'));

            if($clientMessageTemplate)
                $option->setClientMessageTemplate($request->request->get('client_message_template'));

            if($rawRequestTemplate)
                $option->setRawRequestTemplate($request->request->get('raw'));

            $gptRequestOptionRepository->add($option);

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
