<?php

namespace App\Repository;

use App\Entity\KnowledgebaseSection;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<KnowledgebaseSection>
 *
 * @method KnowledgebaseSection|null find($id, $lockMode = null, $lockVersion = null)
 * @method KnowledgebaseSection|null findOneBy(array $criteria, array $orderBy = null)
 * @method KnowledgebaseSection[]    findAll()
 * @method KnowledgebaseSection[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class KnowledgebaseSectionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, KnowledgebaseSection::class);
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function add(KnowledgebaseSection $entity, bool $flush = true): void
    {
        $this->_em->persist($entity);
        if ($flush) {
            $this->_em->flush();
        }
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function remove(KnowledgebaseSection $entity, bool $flush = true): void
    {
        $this->_em->remove($entity);
        if ($flush) {
            $this->_em->flush();
        }
    }

    // /**
    //  * @return KnowledgebaseSection[] Returns an array of KnowledgebaseSection objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('k')
            ->andWhere('k.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('k.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?KnowledgebaseSection
    {
        return $this->createQueryBuilder('k')
            ->andWhere('k.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
