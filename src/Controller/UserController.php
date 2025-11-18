<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\Hamster;
use Doctrine\ORM\EntityManagerInterface;
use Faker\Factory;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

class UserController extends AbstractController
{

    #[Route('/api/register', name: 'api_register', methods: ['POST'])]
    public function register(
        Request $request,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $passwordHasher
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);

    $email    = $data['email'] ?? null;
    $password = $data['password'] ?? null;

    if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return $this->json(['error' => 'Email invalide'], Response::HTTP_BAD_REQUEST);
    }

    if (!$password || strlen($password) < 8) {
        return $this->json(['error' => 'Mot de passe trop court (min 8 caractères)'], Response::HTTP_BAD_REQUEST);
    }

    $existing = $em->getRepository(User::class)->findOneBy(['email' => $email]);
    if ($existing) {
        return $this->json(['error' => 'Cet email est déjà utilisé'], Response::HTTP_BAD_REQUEST);
    }

    $user = new User();
    $user->setEmail($email);
    $hashedPassword = $passwordHasher->hashPassword($user, $password);
    $user->setPassword($hashedPassword);
    $user->setRoles(['ROLE_USER']);
    $user->setGold(500);

    $em->persist($user);

    $faker  = \Faker\Factory::create('fr_FR');
    $genres = ['m', 'm', 'f', 'f'];

    foreach ($genres as $g) {
        $hamster = new Hamster();
        $hamster->setName($faker->firstName());
        $hamster->setGenre($g);
        $hamster->setAge(0);
        $hamster->setHunger(100);
        $hamster->setActive(true);

        $user->addHamster($hamster);
        $em->persist($hamster);
    }

    $em->flush();

    $hamstersArray = [];
    foreach ($user->getHamsters() as $h) {
        $hamstersArray[] = [
            'id'     => $h->getId(),
            'name'   => $h->getName(),
            'genre'  => $h->getGenre(),
            'age'    => $h->getAge(),
            'hunger' => $h->getHunger(),
            'active' => $h->isActive(),
        ];
    }

    $responseData = [
        'id'       => $user->getId(),
        'email'    => $user->getEmail(),
        'gold'     => $user->getGold(),
        'roles'    => $user->getRoles(),
        'hamsters' => $hamstersArray,
    ];

    return $this->json($responseData, Response::HTTP_CREATED);
    }

    #[Route('/api/delete/{id}', name: 'user_delete', methods: ['DELETE'])]
    public function deleteUser(
        User $user,
        EntityManagerInterface $em
    ): JsonResponse {
        if (!$this->isGranted('ROLE_ADMIN')) {
            return $this->json(
                ['error' => 'Accès refusé'],
                Response::HTTP_FORBIDDEN
            );
        }

        foreach ($user->getHamsters() as $hamster) {
            $em->remove($hamster);
        }

        $em->remove($user);
        $em->flush();

        return $this->json(
            ['message' => 'Utilisateur et hamsters supprimés'],
            Response::HTTP_OK
        );
    }

    
    #[Route('/api/user', name: 'api_current_user', methods: ['GET'])]
    public function getCurrentUserInfo(): JsonResponse
    {
        /** @var User|null $user */
        $user = $this->getUser();

        if (!$user instanceof User) {
            return $this->json(
                ['error' => 'Utilisateur non connecté'],
                Response::HTTP_UNAUTHORIZED
            );
        }

        $hamstersArray = [];
        foreach ($user->getHamsters() as $h) {
            $hamstersArray[] = [
                'id'     => $h->getId(),
                'name'   => $h->getName(),
                'genre'  => $h->getGenre(),
                'age'    => $h->getAge(),
                'hunger' => $h->getHunger(),
                'active' => $h->isActive(),
            ];
        }

        $data = [
            'id'       => $user->getId(),
            'email'    => $user->getEmail(),
            'gold'     => $user->getGold(),
            'roles'    => $user->getRoles(),
            'hamsters' => $hamstersArray,
        ];

        return $this->json($data, Response::HTTP_OK);
    }
}
