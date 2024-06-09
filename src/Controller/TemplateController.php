<?php

namespace App\Controller;

use App\Entity\Template;
use App\Service\VectorSearch\RedisSearcher;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class TemplateController extends AbstractController
{
    private $redisSearcher;

    public function __construct(RedisSearcher $redisSearcher)
    {
        $this->redisSearcher = $redisSearcher;
    }

    public function index(EntityManagerInterface $entityManager, Request $request) : Response
    {
        $dql = 'select t from App\Entity\Template t';
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

        return $this->render('template/index.html.twig', [
            'redisSearcher' => $this->redisSearcher,
            'title' => 'Templates',
            'paginator' => $paginator,
            'total' => $total,
            'lastPage' => $lastPage,
            'page' => $page,
        ]);
    }

    public function show(Template $template) : Response
    {
        return $this->render('template/show.html.twig', [
            'title' => $template->getTemplateTitle() ?? 'Template#'.$template->getId(),
            'template' => $template,
            'redisSearcher' => $this->redisSearcher,
        ]);
    }
}
