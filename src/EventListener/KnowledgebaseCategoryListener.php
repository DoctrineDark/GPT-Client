<?php

namespace App\EventListener;

use App\Entity\KnowledgebaseCategory;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\PostPersistEventArgs;
use Doctrine\ORM\Event\PostUpdateEventArgs;

class KnowledgebaseCategoryListener
{
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function postPersist(KnowledgebaseCategory $category, PostPersistEventArgs $event)
    {
        $this->updateSection($category);
    }

    public function postUpdate(KnowledgebaseCategory $category, PostUpdateEventArgs $event)
    {
        $this->updateSection($category);
    }

    private function updateSection(KnowledgebaseCategory $category): void
    {
        $qb = $this->entityManager->createQueryBuilder()
            ->update('App\Entity\KnowledgebaseSection', 'ks')
            ->where('ks.externalCategoryId = :external_category_id')
            ->set('ks.category', ':category')
            ->setParameters([
                'external_category_id' => $category->getExternalId(),
                'category' => $category
            ]);
        $qb->getQuery()->execute();
    }
}