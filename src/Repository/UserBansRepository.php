<?php

namespace App\Repository;

use App\Entity\UserBans;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method UserBans|null find($id, $lockMode = null, $lockVersion = null)
 * @method UserBans|null findOneBy(array $criteria, array $orderBy = null)
 * @method UserBans[]    findAll()
 * @method UserBans[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UserBansRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserBans::class);
    }

    // /**
    //  * @return UserBans[] Returns an array of UserBans objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('u.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?UserBans
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
