<?php

namespace App\Controller;

use App\Repository\CloudflareIndexRepository;
use App\Service\Cloudflare\Vectorize\Client as VectorizeClient;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class CloudflareVectorController extends AbstractController
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

    public function index(Request $request, CloudflareIndexRepository $cloudflareIndexRepository)
    {
        $limit = max($request->get('limit', 150), 1);
        $page = max($request->get('page', 1), 1);
        $index = $cloudflareIndexRepository->findOneBy(['name' => $request->get('index')]);

        $qb = $this->entityManager->createQueryBuilder();
        $qb->select('cv', 'a', 'ap')
            ->from('App\Entity\CloudflareVector', 'cv')
            ->join('cv.article', 'a')
            ->join('cv.articleParagraph', 'ap');

        if ($index) {
            $qb->where('cv.cloudflareIndex = :index');
            $qb->setParameter('index', $index);
        }

        $paginator = new Paginator($qb);
        $paginator->getQuery()
            ->setFirstResult($limit * ($page - 1))
            ->setMaxResults($limit);
        $total = $paginator->count();
        $lastPage = (int) ceil($total / $limit);

        return $this->render('cloudflare_vector/index.html.twig', [
            'title' => 'Cloudflare Vectors',
            'paginator' => $paginator,
            'total' => $total,
            'lastPage' => $lastPage,
            'page' => $page,
            'cloudflareIndexes' => $cloudflareIndexRepository->findAll(),
        ]);
    }
}
