<?php

namespace App\Repository;

use App\Entity\CloudflareVector;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<CloudflareVector>
 *
 * @method CloudflareVector|null find($id, $lockMode = null, $lockVersion = null)
 * @method CloudflareVector|null findOneBy(array $criteria, array $orderBy = null)
 * @method CloudflareVector[]    findAll()
 * @method CloudflareVector[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CloudflareVectorRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CloudflareVector::class);
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function add(CloudflareVector $entity, bool $flush = true): void
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
    public function remove(CloudflareVector $entity, bool $flush = true): void
    {
        $this->_em->remove($entity);
        if ($flush) {
            $this->_em->flush();
        }
    }

    // /**
    //  * @return CloudflareVector[] Returns an array of CloudflareVector objects
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
    public function findOneBySomeField($value): ?CloudflareVector
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
