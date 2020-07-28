<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @method User|null find($id, $lockMode = null, $lockVersion = null)
 * @method User|null findOneBy(array $criteria, array $orderBy = null)
 * @method User[]    findAll()
 * @method User[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UserRepository extends ServiceEntityRepository implements PasswordUpgraderInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    /**
     * Used to upgrade (rehash) the user's password automatically over time.
     */
    public function upgradePassword(UserInterface $user, string $newEncodedPassword): void
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', \get_class($user)));
        }

        $user->setPassword($newEncodedPassword);
        $this->_em->persist($user);
        $this->_em->flush();
    }

    // /**
    //  * @return User[] Returns an array of User objects
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
    public function findOneBySomeField($value): ?User
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */

    public function getAll()
    {
        return $this->createQueryBuilder('u')
            ->getQuery()
            ->getResult();
    }

    public function findUserByEmail($account)
    {
        $qb = $this->_em->createQueryBuilder();
        try {
            return $qb->select('u')
                ->where('u.email = :account')
                ->from(User::class, 'u')
                ->setParameter('account', $account)
                ->getQuery()
                ->getSingleResult();
        } catch (NoResultException $e) {
            return new JsonResponse([
                'data' => [],
                'errorCode' => 0,
                'errorMsgs' => 'Пользователь не найден'
            ], 400);
        } catch (NonUniqueResultException $e) {
            return new JsonResponse([
                'data' => [],
                'errorCode' => 0,
                'errorMsgs' => 'Найдено более одного пользователя'
            ], 400);
        }
    }

    public function findAllUsers(int $pagesize = 10, int $page = 1)
    {
        $qb = $this->_em->createQueryBuilder();

        $qb
            ->select('u')
            ->from(User::class, 'u')
            ->setFirstResult($pagesize * ($page - 1))
            ->setMaxResults($pagesize);

        return $qb->getQuery()->getResult();
    }
}
