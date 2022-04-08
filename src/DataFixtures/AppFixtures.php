<?php

namespace App\DataFixtures;

use App\Entity\Article;
use DateTime;
use DateTimeImmutable;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;

class AppFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        // $product = new Product();
        // $manager->persist($product);
        
        $faker = Factory::create();

        for ($i=0; $i < 20; $i++) { 
            $article = new Article();
            $article->setName($faker->title())
            ->setDescription($faker->paragraph())
            ->setCreatedAt(DateTimeImmutable::createFromMutable($faker->dateTimeThisMonth()));
            $manager->persist($article);
        }

        $manager->flush();
    }
}
