<?php

namespace App\Controller;

use App\Entity\Article;
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

    public function index(EntityManagerInterface $entityManager, Request $request) : Response
    {
        $dql = 'select a from App\Entity\Article a';
        $query = $entityManager->createQuery($dql)
            ->setFirstResult(0)
            ->setMaxResults(7);

        $limit = 50;
        $page = $request->get('page', 1);
        $paginator = new Paginator($query);
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
        ]);
    }

    public function show(Article $article) : Response
    {
        return $this->render('article/show.html.twig', [
            'title' => $article->getArticleTitle() ?? 'Article#'.$article->getId(),
            'article' => $article,
            'redisSearcher' => $this->redisSearcher,
        ]);
    }
}
