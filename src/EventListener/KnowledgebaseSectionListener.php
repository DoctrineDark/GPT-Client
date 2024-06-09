<?php

namespace App\EventListener;

use App\Entity\KnowledgebaseSection;
use App\Repository\KnowledgebaseCategoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\PostPersistEventArgs;
use Doctrine\ORM\Event\PostUpdateEventArgs;

class KnowledgebaseSectionListener
{
    private $entityManager;
    private $knowledgebaseCategoryRepository;

    public function __construct(EntityManagerInterface $entityManager, KnowledgebaseCategoryRepository $knowledgebaseCategoryRepository)
    {
        $this->entityManager = $entityManager;
        $this->knowledgebaseCategoryRepository = $knowledgebaseCategoryRepository;
    }

    public function postPersist(KnowledgebaseSection $section, PostPersistEventArgs $event)
    {
        $this->updateSection($section);
        $this->updateArticle($section);
    }

    public function postUpdate(KnowledgebaseSection $section, PostUpdateEventArgs $event)
    {
        $this->updateSection($section);
        $this->updateArticle($section);
    }

    private function updateSection(KnowledgebaseSection $section)
    {
        $category = $this->knowledgebaseCategoryRepository->findOneBy(['externalId' => $section->getExternalCategoryId()]);

        if($category) {
            $section->setCategory($category);

            $this->entityManager->persist($section);
            $this->entityManager->flush();
        }
    }

    private function updateArticle(KnowledgebaseSection $section): void
    {
        $qb = $this->entityManager->createQueryBuilder()
            ->update('App\Entity\Article', 'a')
            ->where('a.external_section_id = :external_section_id')
            ->set('a.section', ':section')
            ->setParameters([
                'external_section_id' => $section->getExternalId(),
                'section' => $section
            ]);
        $qb->getQuery()->execute();
    }
}