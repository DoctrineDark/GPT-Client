<?php

namespace App\Controller;

use App\Entity\KnowledgebaseSection;
use App\Repository\KnowledgebaseSectionRepository;
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

class KnowledgebaseSectionController extends AbstractController
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

    public function index(KnowledgebaseSectionRepository $sectionRepository)
    {
        $sections = $sectionRepository->findAll();

        return new JsonResponse(json_decode($this->serializer->serialize($sections, 'json', [
            AbstractNormalizer::ATTRIBUTES => [
                'id',
                'externalId',
                'sectionTitle',
                'sectionDescription',
                'category' => [
                    'id',
                    'externalId',
                    'categoryTitle',
                    'active'
                ],
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
                        'sections' => [new Optional([
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

            $sectionFiles = $request->files->get('sections', []);
            $sectionsCount = 0;

            $this->entityManager->wrapInTransaction(function(EntityManager $entityManager) use ($sectionFiles, &$sectionsCount) {

                /** @var UploadedFile $sectionFile */
                foreach ($sectionFiles as $sectionFile) {
                    try {
                        $sectionItems = Items::fromFile($sectionFile->getPathname(), ['decoder' => new ExtJsonDecoder(true)]);

                        foreach ($sectionItems as $rawSection) {
                            if(is_array($rawSection)) {
                                // Validate section
                                $constraintViolation = function(ValidatorInterface $validator, array $haystack) : ConstraintViolationListInterface {
                                    $constraints = [new Collection([
                                        'allowExtraFields' => false,
                                        'fields' => [
                                            'kb_section' => [new Collection([
                                                'allowExtraFields' => false,
                                                'fields' => [
                                                    'section_id' => [new Optional(new Type(['type' => 'integer']))],
                                                    'category_id' => [new Optional(new Type(['type' => 'integer']))],
                                                    'section_title' => [new Optional(new Type(['type' => 'string']))],
                                                    'section_description' => [new Optional(new Type(['type' => 'string']))],
                                                    'active' => [new Optional(new Type(['type' => 'boolean']))],
                                                    'created_at' => [new Optional([new DateTime(['format' => 'D, d M Y H:i:s O'])])],
                                                    'updated_at' => [new Optional([new DateTime(['format' => 'D, d M Y H:i:s O'])])],
                                                ]
                                            ])]
                                        ],
                                    ])];

                                    return $validator->validate($haystack, $constraints);
                                };

                                $errors = $constraintViolation($this->validator, $rawSection);

                                if(count($errors) > 0) {
                                    $messages = [];
                                    foreach ($errors as $violation) {
                                        $messages[] = $violation->getPropertyPath().': '.$violation->getMessage();
                                    }

                                    throw new \Exception('File \''.$sectionFile->getClientOriginalName().'\' validation failed: '. implode(' ', $messages));
                                }

                                $section = $entityManager->getRepository(KnowledgebaseSection::class)->findOneBy(['externalId' => $rawSection['kb_section']['section_id']]) ??
                                    new KnowledgebaseSection();

                                $section->setExternalId($rawSection['kb_section']['section_id']);
                                $section->setExternalCategoryId($rawSection['kb_section']['category_id']);
                                $section->setSectionTitle($rawSection['kb_section']['section_title']);
                                $section->setSectionDescription($rawSection['kb_section']['section_description']);
                                $section->setActive($rawSection['kb_section']['active']);
                                $section->setCreatedAt(new \DateTime($rawSection['kb_section']['created_at']));

                                $entityManager->persist($section);

                                $sectionsCount++;
                            }
                        }
                    }
                    catch (SyntaxErrorException $e) {
                        throw new \Exception('File \''.$sectionFile->getClientOriginalName().'\' parsing failed: The content of the file should be valid JSON.');
                    }
                }
            });

            // Flush data
            $this->entityManager->flush();

            return new JsonResponse([
                'success' => true,
                'message' => 'The data has been successfully saved or updated.',
                'additions' => [
                    'knowledgebase_sections_count'=> $sectionsCount,
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
