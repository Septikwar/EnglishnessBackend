<?php

namespace App\Repository;

use App\Entity\Word;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;

/**
 * @method Word|null find($id, $lockMode = null, $lockVersion = null)
 * @method Word|null findOneBy(array $criteria, array $orderBy = null)
 * @method Word[]    findAll()
 * @method Word[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class WordRepository extends EntityRepository
{
    public function __construct(EntityManagerInterface $em)
    {
        parent::__construct($em, $em->getClassMetadata(Word::class));
    }

    public function findById(int $id, $object = false)
    {
        $qb = $this->_em->createQueryBuilder();

        $qb
            ->select('w', 'g')
            ->from(Word::class, 'w')
            ->leftJoin('w.groups', 'g')
            ->where('w.id = :id')
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

    public function findAllWords(int $pagesize = 10, int $page = 1)
    {
        $qb = $this->_em->createQueryBuilder();

        $qb
            ->select('w', 'g')
            ->from(Word::class, 'w')
            ->leftJoin('w.groups', 'g')
            ->setFirstResult($pagesize * ($page - 1))
            ->setMaxResults($pagesize);

        return $qb->getQuery()->getArrayResult();
    }

    public function findAllWordsInGroups(array $ids)
    {
        $qb = $this->_em->createQueryBuilder();

        $qb
            ->select('w')
            ->from(Word::class, 'w')
            ->leftJoin('w.groups', 'g')
            ->where('g.id IN (:ids)')
            ->setParameter('ids', $ids)
            ->orderBy('w.en');

        return $qb->distinct()->getQuery()->getArrayResult();
    }

    public function deleteWord($id)
    {
        $qb = $this->_em->createQueryBuilder();

        $qb
            ->delete(Word::class, 'w')
            ->where('w.id = :id')
            ->setParameter('id', $id);

        return $qb->getQuery()->execute();
    }
}
