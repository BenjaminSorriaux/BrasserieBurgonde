<?php

namespace App\Repository;

use App\Entity\Command;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Command|null find($id, $lockMode = null, $lockVersion = null)
 * @method Command|null findOneBy(array $criteria, array $orderBy = null)
 * @method Command[]    findAll()
 * @method Command[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CommandRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Command::class);
    }

    /**
     * @return Command[]
     */
    public function findByUserAndTitle($user, $title)
    {
        return $this->createQueryBuilder('c')
            ->join('c.status', 's')
            ->where('c.user = :user')
            ->setParameter('user', $user)
            ->andWhere('s.title = :title')
            ->setParameter('title', $title)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Command[]
     */
    public function findByTitle($title)
    {
        return $this->createQueryBuilder('c')
            ->join('c.status', 's')
            ->andWhere('s.title = :title')
            ->setParameter('title', $title)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Command[]
     */
    public function findByUser($user)
    {
        return $this->createQueryBuilder('c')
            ->where('c.user = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->getResult();
    }

    // /**
    //  * @return Command[] Returns an array of Command objects
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
    public function findOneBySomeField($value): ?Command
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
