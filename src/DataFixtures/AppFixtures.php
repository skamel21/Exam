<?php

namespace App\DataFixtures;

use App\Entity\User;
use App\Entity\Hamster;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    private UserPasswordHasherInterface $hasher;

    public function __construct(UserPasswordHasherInterface $hasher)
    {
        $this->hasher = $hasher;
    }

    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create('fr_FR');

        $user = $this->createUser('user@sf.com', 'password', ['ROLE_USER']);
        $manager->persist($user);
        $this->createFourHamstersForUser($user, $manager, $faker);

        $admin = $this->createUser('admin@sf.com', 'admin', ['ROLE_ADMIN']);
        $manager->persist($admin);
        $this->createFourHamstersForUser($admin, $manager, $faker);

        $manager->flush();
    }

    private function createFourHamstersForUser(User $owner, ObjectManager $manager, \Faker\Generator $faker): void
    {
        $genres = ['m', 'm', 'f', 'f'];

        foreach ($genres as $g) {
            $hamster = new Hamster();
            $hamster->setOwner($owner);
            $hamster->setName($faker->firstName());
            $hamster->setGenre($g);
            $hamster->setAge(0);
            $hamster->setHunger(100);
            $hamster->setActive(true);
            $manager->persist($hamster);
        }
    }

    private function createUser(string $email, string $password, array $roles): User
    {
        $user = new User();
        $user->setEmail($email);
        $user->setRoles($roles);
        $user->setPassword($this->hasher->hashPassword($user, $password));
        $user->setGold(500);

        return $user;
    }
}