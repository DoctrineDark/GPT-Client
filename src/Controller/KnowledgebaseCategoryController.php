<?php

namespace App\Controller;

use App\Entity\KnowledgebaseCategory;
use App\Repository\KnowledgebaseCategoryRepository;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use JsonMachine\Exception\SyntaxErrorException;
use JsonMachine\Items;
use JsonMachine\JsonDecoder\ExtJsonDecoder;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Constraints\All;
use Symfony\Component\Validator\Constraints\Collection;
use Symfony\Component\Validator\Constraints\Count;
use Symfony\Component\Validator\Constraints\DateTime;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\Optional;
use Symfony\Component\Validator\Constraints\Type;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class KnowledgebaseCategoryController extends AbstractController
{
    private $validator;
    private $entityManager;
    private $serializer;

    public function __construct(ValidatorInterface $validator, EntityManagerInterface $entityManager, SerializerInterface $serializer)
    {
        $this->validator = $validator;
        $this->entityManager = $entityManager;
        $this->serializer = $serializer;
    }

    public function index(KnowledgebaseCategoryRepository $categoryRepository)
    {
        $categories = $categoryRepository->findAll();

        return new JsonResponse(json_decode($this->serializer->serialize($categories, 'json', [
            AbstractNormalizer::ATTRIBUTES => [
                'id',
                'externalId',
                'categoryTitle',
                'active'
            ],
            'circular_reference_handler' => function ($object) {
                return $object->getId();
            }
        ]), 1));
    }

    public function upload(Request $request)
    {
        try {
            $constraintViolation = function(ValidatorInterface $validator, array $haystack) : ConstraintViolationListInterface {
                $constraints = [new Collection([
                    'allowExtraFields' => false,
                    'fields' => [
                        'categories' => [new Optional([
                            new All([
                                'constraints' => [
                                    new File([
                                        'maxSize' => '16M',
                                        'mimeTypes' => ['txt' => 'text/*'],
                                    ])
                                ],
                            ]),
                            new Count(['max' => 10])
                        ])]
                    ],
                ])];
                return $validator->validate($haystack, $constraints);
            };

            $errors = $constraintViolation($this->validator, array_merge($request->request->all(), $request->files->all()));

            if(count($errors) > 0) {
                $messages = [];
                foreach ($errors as $violation) {
                    $messages[] = $violation->getPropertyPath().': '.$violation->getMessage();
                }

                throw new \Exception('Validation failed: '. implode(' ', $messages));
            }

            $categoryFiles = $request->files->get('categories', []);
            $categoriesCount = 0;

            $this->entityManager->wrapInTransaction(function(EntityManager $entityManager) use ($categoryFiles, &$categoriesCount) {

                /** @var UploadedFile $categoryFile */
                foreach ($categoryFiles as $categoryFile) {
                    try {
                        $categoryItems = Items::fromFile($categoryFile->getPathname(), ['decoder' => new ExtJsonDecoder(true)]);

                        foreach ($categoryItems as $rawCategory) {
                            if(is_array($rawCategory)) {
                                // Validate category
                                $constraintViolation = function(ValidatorInterface $validator, array $haystack) : ConstraintViolationListInterface {
                                    $constraints = [new Collection([
                                        'allowExtraFields' => false,
                                        'fields' => [
                                            'kb_category' => [new Collection([
                                                'allowExtraFields' => false,
                                                'fields' => [
                                                    'category_id' => [new Optional(new Type(['type' => 'integer']))],
                                                    'category_title' => [new Optional(new Type(['type' => 'string']))],
                                                    'active' => [new Optional(new Type(['type' => 'boolean']))],
                                                    'created_at' => [new Optional([new DateTime(['format' => 'D, d M Y H:i:s O'])])],
                                                    'updated_at' => [new Optional([new DateTime(['format' => 'D, d M Y H:i:s O'])])],
                                                ]
                                            ])]
                                        ],
                                    ])];

                                    return $validator->validate($haystack, $constraints);
                                };

                                $errors = $constraintViolation($this->validator, $rawCategory);

                                if(count($errors) > 0) {
                                    $messages = [];
                                    foreach ($errors as $violation) {
                                        $messages[] = $violation->getPropertyPath().': '.$violation->getMessage();
                                    }

                                    throw new \Exception('File \''.$categoryFile->getClientOriginalName().'\' validation failed: '. implode(' ', $messages));
                                }

                                $category = $entityManager->getRepository(KnowledgebaseCategory::class)->findOneBy(['externalId' => $rawCategory['kb_category']['category_id']]) ??
                                    new KnowledgebaseCategory();

                                $category->setExternalId($rawCategory['kb_category']['category_id']);
                                $category->setCategoryTitle($rawCategory['kb_category']['category_title']);
                                $category->setActive($rawCategory['kb_category']['active']);
                                $category->setCreatedAt(new \DateTime($rawCategory['kb_category']['created_at']));

                                $entityManager->persist($category);

                                $categoriesCount++;
                            }
                        }
                    }
                    catch (SyntaxErrorException $e) {
                        throw new \Exception('File \''.$categoryFile->getClientOriginalName().'\' parsing failed: The content of the file should be valid JSON.');
                    }
                }
            });

            // Flush data
            $this->entityManager->flush();

            return new JsonResponse([
                'success' => true,
                'message' => 'The data has been successfully saved or updated.',
                'additions' => [
                    'knowledgebase_categories_count'=> $categoriesCount,
                ],
            ]);
        }
        catch (\Exception $e) {
            return new JsonResponse([
                'error' => $e->getMessage(),
            ], 400);
        }
    }
}
