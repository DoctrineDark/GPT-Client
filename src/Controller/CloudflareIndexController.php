<?php

namespace App\Controller;

use App\Entity\CloudflareIndex;
use App\Service\Cloudflare\Vectorize\Client as VectorizeClient;
use App\Validator\EntityExist;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Exception;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Constraints\Choice;
use Symfony\Component\Validator\Constraints\Collection;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Optional;
use Symfony\Component\Validator\Constraints\Type;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class CloudflareIndexController extends AbstractController
{
    private $entityManager;
    private $validator;
    private $vectorizeClient;
    private $serializer;
    private $logger;

    public function __construct(EntityManagerInterface $entityManager, ValidatorInterface $validator, VectorizeClient $vectorizeClient, SerializerInterface $serializer, LoggerInterface $logger)
    {
        $this->entityManager = $entityManager;
        $this->validator = $validator;
        $this->vectorizeClient = $vectorizeClient;
        $this->serializer = $serializer;
        $this->logger = $logger;
    }

    public function index(Request $request)
    {
        $limit = max($request->get('limit', 50), 1);
        $page = max($request->get('page', 1), 1);

        $dql = 'select ci from App\Entity\CloudflareIndex ci';
        $query = $this->entityManager->createQuery($dql);

        $paginator = new Paginator($query);
        $paginator->getQuery()
            ->setFirstResult($limit * ($page - 1))
            ->setMaxResults($limit);
        $total = $paginator->count();
        $lastPage = (int) ceil($total / $limit);

        return $this->render('cloudflare_index/index.html.twig', [
            'title' => 'Cloudflare Indexes',
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
                        'api_key' => [new NotBlank(), new Type(['type' => 'string'])],
                        'account_id' => [new NotBlank(), new Type(['type' => 'string'])],
                        'name'=> [new NotBlank(), new Type(['type' => 'string'])],
                        'description' => [new Optional([new Type(['type' => 'string'])])],
                        'dimensions' => [new Type(['type' => 'numeric'])],
                        'metric' => [new Choice(['cosine', 'euclidean', 'dot-product'])]
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

            $apiKey = $request->request->get('api_key');
            $accountId = $request->request->get('account_id');
            $name = $request->request->get('name');
            $description = $request->request->get('description');
            $dimensions = $request->request->get('dimensions');
            $metric = $request->request->get('metric');

            // Create Index in Cloudflare Vectorize
            $this->vectorizeClient->setApiKey($apiKey)->setAccountId($accountId);
            $response = $this->vectorizeClient->createIndex([
                'name' => $name,
                'description' => $description,
                'config' => [
                    'dimensions' => (int) $dimensions,
                    'metric' => $metric
                ]
            ]);
            $response = json_decode($response, 1);

            $this->logger->debug(json_encode($response));

            if (false === $response['success']) {
                throw new Exception(implode(' ', array_column($response['errors'], 'message')));
            }

            // Store CloudflareIndex
            $cloudflareIndex = new CloudflareIndex();

            $cloudflareIndex->setName($response['result']['name']);
            $cloudflareIndex->setDescription($response['result']['description']);
            $cloudflareIndex->setDimensions($response['result']['config']['dimensions']);
            $cloudflareIndex->setMetric($response['result']['config']['metric']);
            $cloudflareIndex->setCreatedAt(new \DateTime($response['result']['created_on']));
            $cloudflareIndex->setUpdatedAt(new \DateTime($response['result']['modified_on']));

            $this->entityManager->persist($cloudflareIndex);
            $this->entityManager->flush();

            $this->addFlash('success', "Index \"{$cloudflareIndex->getName()}\" has been saved successfully.");
        }
        catch (Exception $e) {
            $this->addFlash('error', $e->getMessage());
        }

        return $this->redirectToRoute('cloudflare_indexes');
    }

    public function delete(CloudflareIndex $index, Request $request)
    {
        try {
            $constraintViolation = function(ValidatorInterface $validator, array $haystack): ConstraintViolationListInterface {
                $constraints = [new Collection([
                    'allowExtraFields' => true,
                    'fields' => [
                        'api_key' => [new NotBlank(), new Type(['type' => 'string'])],
                        'account_id' => [new NotBlank(), new Type(['type' => 'string'])]
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

            $apiKey = $request->request->get('api_key');
            $accountId = $request->request->get('account_id');
            $indexName = $index->getName();

            // Delete Index in Cloudflare Vectorize
            $this->vectorizeClient->setApiKey($apiKey)->setAccountId($accountId);
            $response = $this->vectorizeClient->deleteIndex($indexName);
            $response = json_decode($response, 1);

            if (false === $response['success']) {
                throw new Exception(implode(' ', array_column($response['errors'], 'message')));
            }

            // Delete Index from DB
            $this->entityManager->remove($index);
            $this->entityManager->flush();

            $this->addFlash('success', "Index \"{$indexName}\" has been deleted successfully.");
        }
        catch (Exception $e) {
            $this->addFlash('error', $e->getMessage());
        }

        return $this->redirectToRoute('cloudflare_indexes');
    }
}
