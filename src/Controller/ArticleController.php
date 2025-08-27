<?php

namespace App\Controller;

use App\Entity\Article;
use App\Entity\ArticleParagraph;
use App\Entity\CloudflareVector;
use App\Repository\CloudflareIndexRepository;
use App\Repository\OpenSearchIndexRepository;
use App\Service\VectorSearch\RedisSearcher;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ArticleController extends AbstractController
{
    private $redisSearcher;

    public function __construct(RedisSearcher $redisSearcher)
    {
        $this->redisSearcher = $redisSearcher;
    }

    public function index(EntityManagerInterface $entityManager, Request $request, CloudflareIndexRepository $cloudflareIndexRepository, OpenSearchIndexRepository $openSearchIndexRepository): Response
    {
        $limit = max($request->get('limit', 50), 1);
        $page = max($request->get('page', 1), 1);
        //$index = $cloudflareIndexRepository->findOneBy(['name' => $request->get('index')]);

        $qb = $entityManager->createQueryBuilder();
        $qb->select('a')
            ->from('App\Entity\Article', 'a')
            ->orderBy('a.id', 'ASC');
            //->join('a.paragraphs', 'ap')
            //->join('ap.cloudflareVectors', 'cv');

        $paginator = new Paginator($qb);
        $paginator->getQuery()
            ->setFirstResult($limit * ($page - 1))
            ->setMaxResults($limit);
        $total = $paginator->count();
        $lastPage = (int) ceil($total / $limit);

        return $this->render('article/index.html.twig', [
            'redisSearcher' => $this->redisSearcher,
            'title' => 'Articles',
            'paginator' => $paginator,
            'total' => $total,
            'lastPage' => $lastPage,
            'page' => $page,
            'cloudflareIndexes' => $cloudflareIndexRepository->findAll(),
            'openSearchIndexes' => $openSearchIndexRepository->findAll(),
        ]);
    }

    public function show(Article $article, CloudflareIndexRepository $cloudflareIndexRepository, OpenSearchIndexRepository $openSearchIndexRepository): Response
    {
        return $this->render('article/show.html.twig', [
            'title' => $article->getArticleTitle() ?? 'Article#'.$article->getId(),
            'article' => $article,
            'redisSearcher' => $this->redisSearcher,
            'cloudflareIndexes' => $cloudflareIndexRepository->findAll(),
            'openSearchIndexes' => $openSearchIndexRepository->findAll(),
        ]);
    }
}
