<?php

namespace App\Controller;

use App\Service\Gpt\GptRequest;
use App\Service\OpenAI\Client;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\Constraints\All;
use Symfony\Component\Validator\Constraints\Choice;
use Symfony\Component\Validator\Constraints\Collection;
use Symfony\Component\Validator\Constraints\Count;
use Symfony\Component\Validator\Constraints\Expression;
use Symfony\Component\Validator\Constraints\Json;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Optional;
use Symfony\Component\Validator\Constraints\Type;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class AppController extends AbstractController
{
    private $validator;
    private $client;
    private $gptServices;

    public function __construct(ValidatorInterface $validator, Client $client, iterable $gptServices)
    {
        $this->client = $client;
        $this->validator = $validator;
        $this->gptServices = $gptServices;
    }

    public function index(): Response
    {
        return $this->render('app/index.html.twig', [
            'actions' => [],
        ]);
    }

    public function request(Request $request)
    {
        //try {

            // Request validation
            $errors = $this->validate($this->validator, $request->request->all());

            if(count($errors) > 0) {
                $messages = [];
                foreach ($errors as $violation) {
                    $messages[] = $violation->getPropertyPath().': '.$violation->getMessage();
                }

                throw new \Exception('Validation failed: '. implode('. ', $messages));
            }

            // Gpt Request
            foreach ($this->gptServices as $gptClient) {
                if ($gptClient->supports($request->get('gpt_service_name'))) {
                    $gptRequest = new GptRequest('sk-custom-key');
                    $gptRequest
                        ->setRaw($request->get('raw'))
                        ->setClientMessage($request->get('client_message'))
                        ->setLists($request->get('list', []), $request->get('list_values', []))
                        ->setCheckboxes($request->get('checkboxes', []))
                        ->setCustomMessageTemplate($request->get('custom_message_template'));
                    $response = $gptClient->request($gptRequest);

                    return new JsonResponse($response);
                }
            }

        return new JsonResponse([
            'success' => false,
            'message' => 'Gpt-service not found.',
        ], 400);

        /*}
        catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }*/
    }
    

    private function validate(ValidatorInterface $validator, array $haystack) : ConstraintViolationListInterface
    {
        $constraints = [new Collection([
            'allowExtraFields' => true,
            'fields' => [
                'gpt_service_name' => new Choice(['openai']),
                'raw' => [new Optional(new Json())],
                'client_message' => new Type(['type' => 'string']),
                'custom_message_template' => [new Optional(new Type(['type' => 'string']))],
                'list' => [new Optional([new All([
                    'constraints' => [
                        new Type(['type' => 'string']),
                    ],
                ])])],
                'list_values' => [new Optional([new All([
                    'constraints' => [
                        new All([
                            'constraints' => [
                                new Type(['type' => 'string']),
                            ],
                        ]),
                    ],
                ])])],
                'checkboxes' => new Optional([new All([
                    'constraints' => [
                        new Type(['type' => 'string']),
                    ],
                ])]),
            ],
        ])];

        return $validator->validate($haystack, $constraints);
    }
}
