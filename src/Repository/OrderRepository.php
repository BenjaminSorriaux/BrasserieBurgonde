<?php

namespace App\Repository;

use App\Entity\Order;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Order|null find($id, $lockMode = null, $lockVersion = null)
 * @method Order|null findOneBy(array $criteria, array $orderBy = null)
 * @method Order[]    findAll()
 * @method Order[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class OrderRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Order::class);
    }

    /**
     * @return Order[]
     */
    public function findByCommandAndProduct($command, $product)
    {
        return $this->createQueryBuilder('o')
            ->where('o.command = :command')
            ->setParameter('command', $command)
            ->andWhere(':product MEMBER OF o.product')
            ->setParameter('product', $product)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Order[]
     */
    public function findByCommandId($id)
    {
        return $this->createQueryBuilder('o')
            ->join('o.command', 'c')
            ->where('c.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Order[]
     */
    public function findByProductId($id)
    {
        return $this->createQueryBuilder('o')
            ->join('o.product', 'p')
            ->where('p.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getResult();
    }

    // /**
    //  * @return Order[] Returns an array of Order objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('o')
            ->andWhere('o.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('o.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Order
    {
        return $this->createQueryBuilder('o')
            ->andWhere('o.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
