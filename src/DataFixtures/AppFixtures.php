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
        // 1) Normal user
        $user = $this->createUser('user@sf.com', 'password', ['ROLE_USER']);
        $manager->persist($user);

        // 2) Admin user
        $admin = $this->createUser('admin@sf.com', 'admin', ['ROLE_ADMIN']);
        $manager->persist($admin);

        // 3) Create some hamsters for the normal user
        for ($i = 0; $i < 4; $i++) {
            $hamster = $this->createHamster($user);
            $manager->persist($hamster);
        }

        // 4) Create some hamsters for the admin
        for ($i = 0; $i < 4; $i++) {
            $hamster = $this->createHamster($admin);
            $manager->persist($hamster);
        }

        // flush une seule fois à la fin, c’est mieux
        $manager->flush();
    }

    private function createHamster(User $owner): Hamster
    {
    $faker = Factory::create('fr_FR');

    $hamster = new Hamster();
    $hamster->setOwner($owner);
    $hamster->setName($faker->firstName());
    $hamster->setGenre($faker->randomElement(['m', 'f']));
    $hamster->setAge($faker->numberBetween(0, 500));
    $hamster->setHunger($faker->numberBetween(0, 100));
    $hamster->setActive(true);

    return $hamster;
    }

    private function createUser(string $email, string $password, array $roles): User
    {
        $user = new User();
        $user->setEmail($email);
        $user->setRoles($roles);
        $user->setPassword($this->hasher->hashPassword($user, $password));

        // si tu as un champ gold:
        // $user->setGold(500);

        return $user;
    }
}
