<?php

namespace App\Repository;

use App\Entity\Product;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Product|null find($id, $lockMode = null, $lockVersion = null)
 * @method Product|null findOneBy(array $criteria, array $orderBy = null)
 * @method Product[]    findAll()
 * @method Product[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ProductRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Product::class);
    }

    /**
     * @return Product[]
     */
    public function findByCategory($id)
    {
        return $this->createQueryBuilder('p')
            ->join('p.category', 'c')
            ->where('c.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Product[]
     */
    public function findByCategoryExceptOne($id, $productId)
    {
        return $this->createQueryBuilder('p')
            ->join('p.category', 'c')
            ->where('c.id = :id')
            ->setParameter('id', $id)
            ->andWhere('p.id != :productId')
            ->setParameter('productId', $productId)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Product[] Returns an array of product objects with a size depending on the number given
     */
    public function findByDate($number)
    {
        return $this->createQueryBuilder('p')
            ->orderBy('p.createdAt','DESC')
            ->setMaxResults($number)
            ->getQuery()
            ->getResult();
        ;
    }

    // /**
    //  * @return Product[] Returns an array of Product objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('p.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Product
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
