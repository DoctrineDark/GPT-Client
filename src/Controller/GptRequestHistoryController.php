<?php

namespace App\Controller;

use App\Entity\GptRequestHistory;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class GptRequestHistoryController extends AbstractController
{
    public function index(EntityManagerInterface $entityManager, Request $request): Response
    {
        $limit = max($request->get('limit', 50), 1);
        $page = max($request->get('page', 1), 1);

        $dql = 'select r from App\Entity\GptRequestHistory r ORDER BY r.datetime DESC';
        $query = $entityManager->createQuery($dql);

        $paginator = new Paginator($query);
        $paginator->getQuery()
            ->setFirstResult($limit * ($page - 1))
            ->setMaxResults($limit);
        $total = $paginator->count();
        $lastPage = (int) ceil($total / $limit);

        return $this->render('gpt_request_history/index.html.twig', [
            'title' => 'GPT Request History',
            'paginator' => $paginator,
            'total' => $total,
            'lastPage' => $lastPage,
            'page' => $page,
        ]);
    }

    public function show(GptRequestHistory $gptRequestHistory) : Response
    {
        return $this->render('gpt_request_history/show.html.twig', [
            'title' => 'GptRequestHistory#'.$gptRequestHistory->getId(),
            'gptRequestHistory' => $gptRequestHistory
        ]);
    }
}
