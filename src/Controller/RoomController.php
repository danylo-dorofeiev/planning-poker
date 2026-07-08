<?php

namespace App\Controller;

use App\Entity\Room;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;

final class RoomController extends AbstractController
{
    #[Route('/room/create', name: 'room_create')]
    public function create(EntityManagerInterface $entityManager): Response
    {
        $room = new Room();

        $entityManager->persist($room);
        $entityManager->flush();

        return $this->redirectToRoute(
            'room_show',
            [
                'uuid'=>$room->getUuid(),
                'room'=>$room,
            ]
        );
    }

    #[Route('/room/{uuid}', name: 'room_show')]
    public function show(
        #[MapEntity(mapping: ['uuid' => 'uuid'])] Room $room): Response {
        return $this->render('room/show.html.twig',
            [
                'room'=>$room,
            ]
        );
    }

    #[Route('/room/{uuid}/join', name: 'room_join')]
    public function join(Room $room, HubInterface $hub): Response {
        $update = new Update(
            $room->getMercureTopic(),
            json_encode([
                'type' => 'join',
                'message' => 'New player joined the room',
            ])
        );
        $hub->publish($update);

        return new Response('Joined');
    }
}
