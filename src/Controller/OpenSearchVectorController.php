<?php

namespace App\Controller;

use App\Repository\OpenSearchIndexRepository;
use App\Service\OpenSearch\Client as OpenSearchClient;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class OpenSearchVectorController extends AbstractController
{
    private $entityManager;
    private $validator;
    private $vectorizeClient;
    private $serializer;
    private $logger;

    public function __construct(EntityManagerInterface $entityManager, ValidatorInterface $validator, OpenSearchClient $vectorizeClient, SerializerInterface $serializer, LoggerInterface $logger)
    {
        $this->entityManager = $entityManager;
        $this->validator = $validator;
        $this->vectorizeClient = $vectorizeClient;
        $this->serializer = $serializer;
        $this->logger = $logger;
    }

    public function index(Request $request, OpenSearchIndexRepository $openSearchIndexRepository)
    {
        $limit = max($request->get('limit', 150), 1);
        $page = max($request->get('page', 1), 1);
        $index = $openSearchIndexRepository->findOneBy(['name' => $request->get('index')]);

        $qb = $this->entityManager->createQueryBuilder();
        $qb->select('osv', 'a', 'ap')
            ->from('App\Entity\OpenSearchVector', 'osv')
            ->join('osv.article', 'a')
            ->join('osv.articleParagraph', 'ap');

        if ($index) {
            $qb->where('osv.openSearchIndex = :index');
            $qb->setParameter('index', $index);
        }

        $paginator = new Paginator($qb);
        $paginator->getQuery()
            ->setFirstResult($limit * ($page - 1))
            ->setMaxResults($limit);
        $total = $paginator->count();
        $lastPage = (int) ceil($total / $limit);

        return $this->render('opensearch_vector/index.html.twig', [
            'title' => 'OpenSearch Vectors',
            'paginator' => $paginator,
            'total' => $total,
            'lastPage' => $lastPage,
            'page' => $page,
            'openSearchIndexes' => $openSearchIndexRepository->findAll(),
        ]);
    }
}
