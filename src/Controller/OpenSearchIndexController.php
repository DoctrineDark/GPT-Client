<?php

namespace App\Controller;

use App\Entity\OpenSearchIndex;
use App\Service\OpenSearch\Client as OpenSearchClient;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Exception;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Constraints\Choice;
use Symfony\Component\Validator\Constraints\Collection;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Type;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class OpenSearchIndexController extends AbstractController
{
    private $entityManager;
    private $validator;
    private $openSearchClient;
    private $serializer;
    private $logger;

    public function __construct(EntityManagerInterface $entityManager, ValidatorInterface $validator, OpenSearchClient $openSearchClient, SerializerInterface $serializer, LoggerInterface $logger)
    {
        $this->entityManager = $entityManager;
        $this->validator = $validator;
        $this->openSearchClient = $openSearchClient;
        $this->serializer = $serializer;
        $this->logger = $logger;
    }

    public function index(Request $request)
    {
        $limit = max($request->get('limit', 50), 1);
        $page = max($request->get('page', 1), 1);

        $dql = 'select osi from App\Entity\OpenSearchIndex osi';
        $query = $this->entityManager->createQuery($dql);

        $paginator = new Paginator($query);
        $paginator->getQuery()
            ->setFirstResult($limit * ($page - 1))
            ->setMaxResults($limit);
        $total = $paginator->count();
        $lastPage = (int) ceil($total / $limit);

        return $this->render('opensearch_index/index.html.twig', [
            'title' => 'OpenSearch Indexes',
            'paginator' => $paginator,
            'total' => $total,
            'lastPage' => $lastPage,
            'page' => $page,
        ]);
    }

    public function store(Request $request)
    {
        try {
            $constraintViolation = function(ValidatorInterface $validator, array $haystack): ConstraintViolationListInterface {
                $constraints = [new Collection([
                    'allowExtraFields' => false,
                    'fields' => [
                        'name'=> [new NotBlank(), new Type(['type' => 'string'])],
                        'dimensions' => [new Type(['type' => 'numeric'])],
                        'analyzer' => [new Choice(['standard', 'simple', 'english', 'russian', 'keyword'])]
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

                throw new Exception('Validation failed: '.implode(' ', $messages));
            }

            $name = $request->request->get('name');
            $dimensions = $request->request->get('dimensions');
            $analyzer = $request->request->get('analyzer');

            // Store OpenSearchIndex
            $openSearchIndex = new OpenSearchIndex();

            $openSearchIndex->setName($name);
            $openSearchIndex->setDimensions($dimensions);
            $openSearchIndex->setAnalyzer($analyzer);

            // Create Index in OpenSearch
            $response = $this->openSearchClient->createIndex($name, $openSearchIndex->buildOptions());
            $response = json_decode($response, 1);

            $this->logger->debug(json_encode($response));

            if (isset($response['error'])) {
                throw new Exception($response['error']['reason']);
            }

            //
            $this->entityManager->persist($openSearchIndex);
            $this->entityManager->flush();

            $this->addFlash('success', "OpenSearch Index \"{$openSearchIndex->getName()}\" has been saved successfully.");
        }
        catch (Exception $e) {
            $this->addFlash('error', $e->getMessage());
        }

        return $this->redirectToRoute('opensearch_indexes');
    }

    public function delete(OpenSearchIndex $index, Request $request)
    {
        try {
            $constraintViolation = function(ValidatorInterface $validator, array $haystack): ConstraintViolationListInterface {
                $constraints = [new Collection([
                    'allowExtraFields' => true,
                    'fields' => [],
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

            $indexName = $index->getName();

            // Delete Index in OpenSearch
            $response = $this->openSearchClient->deleteIndex($indexName);
            $response = json_decode($response, 1);

            if (isset($response['error'])) {
                throw new Exception($response['error']['reason']);
            }

            // Delete OpenSearchIndex from DB
            $this->entityManager->remove($index);
            $this->entityManager->flush();

            $this->addFlash('success', "OpenSearch Index \"{$indexName}\" has been deleted successfully.");
        }
        catch (Exception $e) {
            $this->addFlash('error', $e->getMessage());
        }

        return $this->redirectToRoute('opensearch_indexes');
    }
}
