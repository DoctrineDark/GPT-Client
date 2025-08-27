<?php

namespace App\Controller;

use App\Entity\Article;
use App\Entity\ArticleParagraph;
use App\Repository\CloudflareIndexRepository;
use App\Repository\OpenSearchIndexRepository;
use App\Service\VectorSearch\RedisSearcher;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

class ArticleParagraphController extends AbstractController
{
    private $redisSearcher;

    public function __construct(RedisSearcher $redisSearcher)
    {
        $this->redisSearcher = $redisSearcher;
    }

    public function show(Article $article, ArticleParagraph $articleParagraph, CloudflareIndexRepository $cloudflareIndexRepository, OpenSearchIndexRepository $openSearchIndexRepository) : Response
    {
        if($article->getId() !== $articleParagraph->getArticle()->getId()) {
            throw $this->createNotFoundException();
        }

        return $this->render('article_paragraph/show.html.twig', [
            'articleParagraph' => $articleParagraph,
            'title' => $articleParagraph->getParagraphTitle() ?? 'ArticleParagraph#'.$articleParagraph->getId(),
            'redisSearcher' => $this->redisSearcher,
            'cloudflareIndexes' => $cloudflareIndexRepository->findAll(),
            'openSearchIndexes' => $openSearchIndexRepository->findAll(),
        ]);
    }
}
