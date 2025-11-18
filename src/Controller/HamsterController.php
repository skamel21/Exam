<?php

namespace App\Controller;

use App\Entity\Hamster;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class HamsterController extends AbstractController
{
    #[Route('/api/hamsters', name: 'hamster_list', methods: ['GET'])]
    public function list(EntityManagerInterface $em): JsonResponse
    {
        /** @var User|null $user */
        $user = $this->getUser();

        if (!$user instanceof User) {
            return $this->json(['error' => 'Utilisateur non connecté'], Response::HTTP_UNAUTHORIZED);
        }

        $hamsters = $em->getRepository(Hamster::class)->findBy(['owner' => $user]);

        $data = [];
        foreach ($hamsters as $h) {
            $data[] = [
                'id'     => $h->getId(),
                'name'   => $h->getName(),
                'genre'  => $h->getGenre(),
                'age'    => $h->getAge(),
                'hunger' => $h->getHunger(),
                'active' => $h->isActive(),
            ];
        }

        return $this->json($data, Response::HTTP_OK);
    }

    #[Route('/api/hamsters/{id}', name: 'hamsters_by_id', methods: ['GET'])]
    public function getHamstersById(Hamster $hamsters): JsonResponse
    {
        return $this->json([
            'hamsters' => $hamsters,
        ], Response::HTTP_OK, [], ['groups' => 'AllHamsters']);
    }

    #[Route('/api/hamsters/reproduce', name: 'hamster_reproduce', methods: ['POST'])]
    public function reproduce(Request $request, EntityManagerInterface $em): JsonResponse
    {
        /** @var User|null $user */
        $user = $this->getUser();

        if (!$user instanceof User) {
            return $this->json(['error' => 'Utilisateur non connecté'], Response::HTTP_UNAUTHORIZED);
        }

        $data = json_decode($request->getContent(), true);
        $id1  = $data['idHamster1'] ?? null;
        $id2  = $data['idHamster2'] ?? null;

        if (!$id1 || !$id2 || $id1 === $id2) {
            return $this->json(
                ['error' => 'idHamster1 et idHamster2 doivent être deux IDs différents'],
                Response::HTTP_BAD_REQUEST
            );
        }

        $repo = $em->getRepository(Hamster::class);

        /** @var Hamster|null $h1 */
        $h1 = $repo->find($id1);
        /** @var Hamster|null $h2 */
        $h2 = $repo->find($id2);

        if (!$h1 || !$h2) {
            return $this->json(['error' => 'Hamster(s) introuvable(s)'], Response::HTTP_NOT_FOUND);
        }
        
        if (
            !$this->isGranted('ROLE_ADMIN') &&
            ($h1->getOwner() !== $user || $h2->getOwner() !== $user)
        ) {
            return $this->json(['error' => 'Les hamsters doivent vous appartenir'], Response::HTTP_FORBIDDEN);
        }

        if (!$h1->isActive() || !$h2->isActive()) {
            return $this->json(['error' => 'Les deux hamsters doivent être actifs'], Response::HTTP_BAD_REQUEST);
        }

        $faker  = \Faker\Factory::create('fr_FR');
        $baby   = new Hamster();
        $baby->setOwner($user);
        $baby->setName($faker->firstName());
        $baby->setGenre($faker->randomElement(['m', 'f']));
        $baby->setAge(0);
        $baby->setHunger(100);
        $baby->setActive(true);

        $em->persist($baby);
        $em->flush();

        return $this->json([
            'id'     => $baby->getId(),
            'name'   => $baby->getName(),
            'genre'  => $baby->getGenre(),
            'age'    => $baby->getAge(),
            'hunger' => $baby->getHunger(),
            'active' => $baby->isActive(),
        ], Response::HTTP_CREATED);
    }


    #[Route('/api/hamsters/{id}/feed', name: 'hamster_feed', methods: ['POST'])]
    public function feed(Hamster $hamster, EntityManagerInterface $em): JsonResponse
    {
        /** @var User|null $user */
        $user = $this->getUser();

        if (!$user instanceof User) {
            return $this->json(['error' => 'Utilisateur non connecté'], Response::HTTP_UNAUTHORIZED);
        }

        // Admin peut nourrir n'importe quel hamster, sinon il faut être owner
        if (!$this->isGranted('ROLE_ADMIN') && $hamster->getOwner() !== $user) {
            return $this->json(['error' => 'Ce hamster ne vous appartient pas'], Response::HTTP_FORBIDDEN);
        }

        if (!$hamster->isActive()) {
            return $this->json(['error' => 'Ce hamster n\'est plus actif'], Response::HTTP_BAD_REQUEST);
        }

        $currentHunger = $hamster->getHunger();
        if ($currentHunger >= 100) {
            return $this->json(['error' => 'Ce hamster a déjà 100 de hunger'], Response::HTTP_BAD_REQUEST);
        }

        $cost = 100 - $currentHunger;

        if ($user->getGold() === null || $user->getGold() < $cost) {
            return $this->json(
                ['error' => 'Pas assez de gold pour nourrir (coût = '.$cost.')'],
                Response::HTTP_BAD_REQUEST
            );
        }

        $hamster->setHunger(100);
        $user->setGold($user->getGold() - $cost);

        $em->persist($hamster);
        $em->persist($user);
        $em->flush();

        return $this->json([
            'gold'    => $user->getGold(),
            'hamster' => [
                'id'     => $hamster->getId(),
                'name'   => $hamster->getName(),
                'genre'  => $hamster->getGenre(),
                'age'    => $hamster->getAge(),
                'hunger' => $hamster->getHunger(),
                'active' => $hamster->isActive(),
            ],
        ], Response::HTTP_OK);
    }


    #[Route('/api/hamsters/{id}/sell', name: 'hamster_sell', methods: ['POST'])]
    public function sell(Hamster $hamster, EntityManagerInterface $em): JsonResponse
    {
        /** @var User|null $user */
        $user = $this->getUser();

        if (!$user instanceof User) {
            return $this->json(['error' => 'Utilisateur non connecté'], Response::HTTP_UNAUTHORIZED);
        }

        if (!$this->isGranted('ROLE_ADMIN') && $hamster->getOwner() !== $user) {
            return $this->json(['error' => 'Ce hamster ne vous appartient pas'], Response::HTTP_FORBIDDEN);
        }

        $currentGold = $user->getGold() ?? 0;
        $user->setGold($currentGold + 300);

        $em->remove($hamster);
        $em->persist($user);
        $em->flush();

        return $this->json([
            'message' => 'Hamster vendu',
            'gold'    => $user->getGold(),
        ], Response::HTTP_OK);
    }

    #[Route('/api/hamster/sleep/{nbDays}', name: 'hamster_sleep', methods: ['POST'])]
    public function sleep(int $nbDays, EntityManagerInterface $em): JsonResponse
    {
        /** @var User|null $user */
        $user = $this->getUser();

        if (!$user instanceof User) {
            return $this->json(['error' => 'Utilisateur non connecté'], Response::HTTP_UNAUTHORIZED);
        }

        if ($nbDays <= 0) {
            return $this->json(['error' => 'nbDays doit être > 0'], Response::HTTP_BAD_REQUEST);
        }

        $hamsters = $user->getHamsters();

        foreach ($hamsters as $hamster) {
            if (!$hamster->isActive()) {
                continue;
            }

            $newAge    = $hamster->getAge() + $nbDays;
            $newHunger = $hamster->getHunger() - $nbDays;

            $hamster->setAge($newAge);
            $hamster->setHunger($newHunger);

            if ($newAge > 500 || $newHunger < 0) {
                $hamster->setActive(false);
            }

            $em->persist($hamster);
        }

        $em->flush();

        return $this->json([
            'message' => 'Tous les hamsters ont dormi '.$nbDays.' jours',
        ], Response::HTTP_OK);
    }

    #[Route('/api/hamsters/{id}/rename', name: 'hamster_rename', methods: ['PUT'])]
    public function rename(Hamster $hamster, Request $request, EntityManagerInterface $em): JsonResponse
    {
        /** @var User|null $user */
        $user = $this->getUser();

        if (!$user instanceof User) {
            return $this->json(['error' => 'Utilisateur non connecté'], Response::HTTP_UNAUTHORIZED);
        }

        if (!$this->isGranted('ROLE_ADMIN') && $hamster->getOwner() !== $user) {
            return $this->json(['error' => 'Ce hamster ne vous appartient pas'], Response::HTTP_FORBIDDEN);
        }

        $data = json_decode($request->getContent(), true);
        $name = $data['name'] ?? null;

        if (!$name || strlen($name) < 2) {
            return $this->json(['error' => 'Le nom doit faire au moins 2 caractères'], Response::HTTP_BAD_REQUEST);
        }

        $hamster->setName($name);
        $em->persist($hamster);
        $em->flush();

        return $this->json([
            'id'     => $hamster->getId(),
            'name'   => $hamster->getName(),
            'genre'  => $hamster->getGenre(),
            'age'    => $hamster->getAge(),
            'hunger' => $hamster->getHunger(),
            'active' => $hamster->isActive(),
        ], Response::HTTP_OK);
    }
}
