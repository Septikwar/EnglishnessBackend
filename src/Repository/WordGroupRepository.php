<?php

namespace App\Repository;

use App\Entity\Word;
use App\Entity\WordGroup;
use App\Entity\WordGroupWord;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method WordGroup|null find($id, $lockMode = null, $lockVersion = null)
 * @method WordGroup|null findOneBy(array $criteria, array $orderBy = null)
 * @method WordGroup[]    findAll()
 * @method WordGroup[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class WordGroupRepository extends EntityRepository
{
    public function __construct(EntityManagerInterface $em)
    {
        parent::__construct($em, $em->getClassMetadata(WordGroup::class));
    }

    public function findById(int $id, $object = false)
    {
        $qb = $this->_em->createQueryBuilder();

        $qb
            ->select('wg', 'w')
            ->from(WordGroup::class, 'wg')
            ->leftJoin('wg.words', 'w')
            ->where('wg.id = :id')
            ->setParameter('id', $id);

        if ($object === true) {
            try {
                return $qb->getQuery()->getSingleResult();
            } catch (NoResultException $e) {
            } catch (NonUniqueResultException $e) {
            }
        }
        return $qb->getQuery()->getArrayResult();
    }

    public function findAllGroups(int $pagesize = 10, int $page = 1)
    {
        $qb = $this->_em->createQueryBuilder();

        $qb
            ->select('wg')
            ->from(WordGroup::class, 'wg')
            ->setFirstResult($pagesize * ($page - 1))
            ->setMaxResults($pagesize);

        return $qb->getQuery()->getArrayResult();
    }

    public function deleteWordGroup($id)
    {
        $qb = $this->_em->createQueryBuilder();

        $qb
            ->delete(WordGroup::class, 'wg')
            ->where('wg.id = :id')
            ->setParameter('id', $id);

        return $qb->getQuery()->execute();
    }
}
