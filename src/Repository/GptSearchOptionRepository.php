<?php

namespace App\Repository;

use App\Entity\GptSearchOption;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<GptSearchOption>
 *
 * @method GptSearchOption|null find($id, $lockMode = null, $lockVersion = null)
 * @method GptSearchOption|null findOneBy(array $criteria, array $orderBy = null)
 * @method GptSearchOption[]    findAll()
 * @method GptSearchOption[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class GptSearchOptionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, GptSearchOption::class);
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function add(GptSearchOption $entity, bool $flush = true): void
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
    public function remove(GptSearchOption $entity, bool $flush = true): void
    {
        $this->_em->remove($entity);
        if ($flush) {
            $this->_em->flush();
        }
    }

    // /**
    //  * @return GptSearchOption[] Returns an array of GptSearchOption objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('g')
            ->andWhere('g.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('g.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?GptSearchOption
    {
        return $this->createQueryBuilder('g')
            ->andWhere('g.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
