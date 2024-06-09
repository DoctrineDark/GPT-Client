<?php

namespace App\Controller;

use App\Entity\Message;
use App\Repository\MessageRepository;
use App\Validator\EntityExist;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use JsonMachine\Exception\SyntaxErrorException;
use JsonMachine\Items;
use JsonMachine\JsonDecoder\ExtJsonDecoder;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Constraints\All;
use Symfony\Component\Validator\Constraints\Choice;
use Symfony\Component\Validator\Constraints\Collection;
use Symfony\Component\Validator\Constraints\Count;
use Symfony\Component\Validator\Constraints\DateTime;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\Optional;
use Symfony\Component\Validator\Constraints\Type;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class MessageController extends AbstractController
{
    private $entityManager;
    private $validator;
    private $serializer;
    private $logger;

    public function __construct(EntityManagerInterface $entityManager, ValidatorInterface $validator, SerializerInterface $serializer, LoggerInterface $logger)
    {
        $this->entityManager = $entityManager;
        $this->validator = $validator;
        $this->logger = $logger;
        $this->serializer = $serializer;
    }

    public function store(Request $request)
    {
        try {
            $constraintViolation = function(ValidatorInterface $validator, array $haystack) : ConstraintViolationListInterface {
                $constraints = [new Collection([
                    'allowExtraFields' => false,
                    'fields' => [
                        'id' => [new Optional([new Type(['type' => 'numeric'])])],
                        'content' => [new Type(['type' => 'string'])],
                        'content_html' => [new Optional([new Type(['type' => 'string'])])],
                        'message_type' => [new Choice(['reply_user', 'reply_staff', 'reply_ai'])],
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

                throw new \Exception('Validation failed: '. implode(' ', $messages));
            }

            switch ($request->request->get('message_type')) {
                case 'reply_staff':
                    $externalUserId = 0;
                    $externalStaffId = $request->request->get('id', 0);
                    break;
                default:
                    $externalUserId = $request->request->get('id', 0);
                    $externalStaffId = 0;
            }

            $message = new Message();

            $message->setExternalUserId($externalUserId);
            $message->setExternalStaffId($externalStaffId);
            $message->setContent($request->request->get('content'));
            $message->setContentHtml($request->request->get('content_html'));
            $message->setMessageType($request->request->get('message_type'));
            $message->setSentAt(new \DateTime());

            $this->entityManager->persist($message);
            $this->entityManager->flush();

            return new JsonResponse(
                json_decode($this->serializer->serialize($message, 'json'), true)
            );
        }
        catch (\Exception $e) {
            return new JsonResponse([
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    public function upload(Request $request)
    {
        try {
            $constraintViolation = function(ValidatorInterface $validator, array $haystack) : ConstraintViolationListInterface {
                $constraints = [new Collection([
                    'allowExtraFields' => false,
                    'fields' => [
                        'messages' => [new Optional([
                            new All([
                                'constraints' => [
                                    new File([
                                        'maxSize' => '16M',
                                        'mimeTypes' => ['txt' => 'text/*'],
                                    ])
                                ],
                            ]),
                            new Count(['max' => 5])
                        ])],
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

                throw new \Exception('Validation failed: '. implode(' ', $messages));
            }

            $messageFiles = $request->files->get('messages', []);

            $messagesCount = 0;

            $this->entityManager->wrapInTransaction(function(EntityManager $entityManager) use ($messageFiles, &$messagesCount) {

                /** @var UploadedFile $messageFile */
                foreach ($messageFiles as $messageFile) {
                    try {
                        $messageItems = Items::fromFile($messageFile->getPathname(), ['decoder' => new ExtJsonDecoder(true)]);

                        foreach($messageItems as $rawMessage) {
                            if(is_array($rawMessage)) {
                                $rawMessage = $rawMessage['message'];
                                // Validate Message
                                $constraintViolation = function(ValidatorInterface $validator, array $haystack) : ConstraintViolationListInterface {
                                    $constraints = [new Collection([
                                        'allowExtraFields' => false,
                                        'fields' => [
                                            'message_id' => [new Optional([new Type(['type' => 'integer'])])],
                                            'user_id' => [new Optional([new Type(['type' => 'integer'])])],
                                            'staff_id' => [new Optional([new Type(['type' => 'integer'])])],
                                            'content' => [new Optional([new Type(['type' => 'string'])])],
                                            'content_html' => [new Optional([new Type(['type' => 'string'])])],
                                            'attachments' => [new Optional([new All(['constraints' => []])])],
                                            'note' => [new Optional([new Type(['type' => 'boolean'])])],
                                            'message_type' => [new Choice(['reply_user', 'reply_staff'])],
                                            'sent_via_rule' => [new Optional([new Type(['type' => 'boolean'])])],
                                            'created_at' => [new Optional([new DateTime(['format' => 'D, d M Y H:i:s O'])])],
                                            'sent_at' => [new Optional([new DateTime(['format' => 'D, d M Y H:i:s O'])])],
                                            'changed_email_subject' => [new Optional([new Type(['type' => 'string'])])],
                                            'full_name' => [new Optional([new Type(['type' => 'string'])])],
                                            'thumbnail' => [new Optional([new Type(['type' => 'string'])])],
                                            'parent_id' => [new Optional([new Type(['type' => 'integer'])])],
                                            'is_viewed' => [new Optional([new Type(['type' => 'boolean'])])],
                                            'request_id' => [new Optional([new Type(['type' => 'string'])])],
                                        ],
                                    ])];

                                    return $validator->validate($haystack, $constraints);
                                };

                                $errors = $constraintViolation($this->validator, $rawMessage);

                                if(count($errors) > 0) {
                                    $messages = [];
                                    foreach ($errors as $violation) {
                                        $messages[] = $violation->getPropertyPath().': '.$violation->getMessage();
                                    }

                                    throw new \Exception('File \''.$messageFile->getClientOriginalName().'\' validation failed: '. implode(' ', $messages));
                                }

                                $message = $entityManager->getRepository(Message::class)->findOneBy(['external_id' => $rawMessage['message_id']]) ??
                                    new Message();

                                $message->setExternalId($rawMessage['message_id']);
                                $message->setExternalUserId($rawMessage['user_id']);
                                $message->setExternalStaffId($rawMessage['staff_id']);
                                $message->setContent($rawMessage['content'] ? $rawMessage['content'] : (new Crawler($rawMessage['content_html']))->text());
                                $message->setContentHtml($rawMessage['content_html']);
                                $message->setMessageType($rawMessage['message_type']);
                                $message->setCreatedAt(new \DateTime($rawMessage['created_at']));
                                $message->setSentAt(new \DateTime($rawMessage['sent_at']));

                                $entityManager->persist($message);

                                // if Entity was changed
                                $uow = $entityManager->getUnitOfWork();
                                $uow->computeChangeSets();

                                if($uow->isEntityScheduled($message)) {
                                    $messagesCount++;
                                }
                            }
                        }
                    } catch (SyntaxErrorException $e) {
                        throw new \Exception('File \'' . $messageFile->getClientOriginalName() . '\' parsing failed: The content of the file should be valid JSON.');
                    }
                }
            });

            $this->entityManager->flush();

            return new JsonResponse([
                'success' => true,
                'message' => 'The data has been successfully saved or updated.',
                'additions' => [
                    'messages_count'=> $messagesCount
                ],
            ]);
        }
        catch (\Exception $e) {
            return new JsonResponse([
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * @param EntityManagerInterface $entityManager
     * @param Request $request
     * @return JsonResponse
     */
    public function deleteSelected(EntityManagerInterface $entityManager, Request $request)
    {
        try {
            $constraintViolation = function(ValidatorInterface $validator, array $haystack) : ConstraintViolationListInterface {
                $constraints = [new Collection([
                    'allowExtraFields' => true,
                    'fields' => [
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

            $builder = $entityManager->createQueryBuilder()
                ->delete(Message::class, 'm')
                ->where('m.id IN (:messages)')
                ->setParameter('messages', $request->request->get('messages', []));
            $builder->getQuery()->execute();

            return new JsonResponse(['success' => true]);
        }
        catch (Exception $e) {
            return new JsonResponse([
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * @param EntityManagerInterface $entityManager
     * @return JsonResponse
     */
    public function deleteAll(EntityManagerInterface $entityManager)
    {
        try {
            $builder = $entityManager->createQueryBuilder()->delete(Message::class, 'm');
            $builder->getQuery()->execute();

            return new JsonResponse(['success' => true]);
        }
        catch (Exception $e) {
            return new JsonResponse([
                'error' => $e->getMessage(),
            ], 400);
        }
    }
}
