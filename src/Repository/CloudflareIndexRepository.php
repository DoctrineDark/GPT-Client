<?php

namespace App\Repository;

use App\Entity\CloudflareIndex;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<CloudflareIndex>
 *
 * @method CloudflareIndex|null find($id, $lockMode = null, $lockVersion = null)
 * @method CloudflareIndex|null findOneBy(array $criteria, array $orderBy = null)
 * @method CloudflareIndex[]    findAll()
 * @method CloudflareIndex[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CloudflareIndexRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CloudflareIndex::class);
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function add(CloudflareIndex $entity, bool $flush = true): void
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
    public function remove(CloudflareIndex $entity, bool $flush = true): void
    {
        $this->_em->remove($entity);
        if ($flush) {
            $this->_em->flush();
        }
    }

    // /**
    //  * @return CloudflareIndex[] Returns an array of CloudflareIndex objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('c.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?CloudflareIndex
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
