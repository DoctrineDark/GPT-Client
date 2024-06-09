<?php

namespace App\EventListener;

use App\Entity\Article;
use App\Repository\KnowledgebaseSectionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\PostPersistEventArgs;
use Doctrine\ORM\Event\PostUpdateEventArgs;

class ArticleListener
{
    private $entityManager;
    private $knowledgebaseSectionRepository;

    public function __construct(EntityManagerInterface $entityManager, KnowledgebaseSectionRepository $knowledgebaseSectionRepository)
    {
        $this->entityManager = $entityManager;
        $this->knowledgebaseSectionRepository = $knowledgebaseSectionRepository;
    }

    public function postPersist(Article $article, PostPersistEventArgs $event)
    {
        $this->updateArticle($article);
    }

    public function postUpdate(Article $article, PostUpdateEventArgs $event)
    {
        $this->updateArticle($article);
    }

    private function updateArticle(Article $article)
    {
        $section = $this->knowledgebaseSectionRepository->findOneBy(['externalId' => $article->getExternalSectionId()]);

        if($section) {
            $article->setSection($section);

            $this->entityManager->persist($article);
            $this->entityManager->flush();
        }
    }
}