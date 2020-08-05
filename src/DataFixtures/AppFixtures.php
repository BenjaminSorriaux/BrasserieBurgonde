<?php

namespace App\DataFixtures;

use App\Entity\News;
use App\Entity\Role;
use App\Entity\User;
use App\Entity\Status;
use App\Entity\Product;
use App\Entity\Category;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\Persistence\ObjectManager;

class AppFixtures extends Fixture
{
    public function load(ObjectManager $manager)
    {
        $faker = \Faker\Factory::create('fr_FR');

        // Creation of 10 news
        for ($i=1; $i <= 10 ; $i ++) { 
            $news = new News();
            $news->setTitle($faker->sentence())
                ->setContent($faker->paragraph())
                ->setPicture($faker->imageUrl())
                ->setCreatedAt($faker->dateTimeBetween('-1 year'));

            $manager->persist($news);
        }

        // Creation of 5 categories
        for($i = 1; $i <= 5; $i++)
        {
            $category = new Category();
            $category->setTitle($faker->sentence(1))
                ->setCreatedAt($faker->dateTimeBetween('-1 year'));

            $manager->persist($category);
        }

        // Creation of user role
        $userRole = new Role();
        $userRole->setName("USER")
            ->setCreatedAt(new \DateTime());

        $manager->persist($userRole);

        // Creation of admin role
        $adminRole = new Role();
        $adminRole->setName("ADMIN")
            ->setCreatedAt(new \DateTime());

        $manager->persist($adminRole);

        // Creation of temporary status
        $tempStatus = new Status();
        $tempStatus->setTitle('TEMPORARY')
            ->setCreatedAt(new \DateTime());

        $manager->persist($tempStatus);

        // Creation of waiting status
        $waitingStatus = new Status();
        $waitingStatus->setTitle('WAITING')
            ->setCreatedAt(new \DateTime());
        
        $manager->persist($waitingStatus);

        // Creation of confirmed status
        $confirmedStatus = new Status();
        $confirmedStatus->setTitle('CONFIRMED')
            ->setCreatedAt(new \DateTime());

        $manager->persist($confirmedStatus);
        

        // Creation of 10 products
        for ($i=1; $i <= 10 ; $i++) { 
            $product = new Product();
            $product->setName($faker->sentence(2))
                ->setDescription($faker->paragraph())
                ->setImage($faker->imageUrl())
                ->setQuantity($faker->numberBetween(5, 100))
                ->setPrice($faker->randomFloat(2, 0, 5))
                ->setCreatedAt($faker->dateTimeBetween('-1 year'))
                ->setCategory($category);

            $manager->persist($product);
        }

        $manager->flush();
    }
}
