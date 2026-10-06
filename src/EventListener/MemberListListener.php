<?php

namespace App\EventListener;

use App\Event\MemberListEvent;
use App\Repository\RoundRepository;
use App\Repository\VoteRepository;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;
use Twig\Environment;

#[AsEventListener]
class MemberListListener
{
    public function __construct(
        private HubInterface    $hub,
        private Environment     $twig,
        private RoundRepository $roundRepository,
        private VoteRepository  $voteRepository,
    ) {}

    public function __invoke(MemberListEvent $event): void
    {
        $room = $event->getRoom();
        $members = $room->getMembers();

        $activeRound = $this->roundRepository->findActiveRoundByRoom($room);

        $votedUserIds = [];
        if($activeRound) {
            $votedUserIds = $this->voteRepository->findVotedUserIds($activeRound);
        }

        $memberListUpdate = $this->twig->render('room/stream/_member_list.html.twig', [
            'members' => $members,
            'votedUserIds' => $votedUserIds,
        ]);

        $this->hub->publish(new Update($room->getUuid(), $memberListUpdate));
    }
}

