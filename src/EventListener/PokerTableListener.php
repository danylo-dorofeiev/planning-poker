<?php

namespace App\EventListener;

use App\Event\PokerTableEvent;
use App\Repository\VoteRepository;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;
use Twig\Environment;

#[AsEventListener]
class PokerTableListener
{
    public function __construct(
        private HubInterface    $hub,
        private Environment     $twig,
        private VoteRepository   $voteRepository,
    ) {}

    public function __invoke(PokerTableEvent $event): void
    {
        $round = $event->getRound();
        $ticket = $round->getTicket();
        $room = $ticket->getRoom();

        $votedUserIds = $this->voteRepository->findVotedUserIds($round);

        $pokerTableUpdate = $this->twig->render('room/stream/_poker_table.html.twig', [
            'activeRound' => $round,
            'votedUserIds' => $votedUserIds,
        ]);

        $this->hub->publish(new Update($room->getUuid(), $pokerTableUpdate));
    }
}

