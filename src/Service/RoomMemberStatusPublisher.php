<?php

namespace App\Service;

use App\Entity\Room;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;

readonly class RoomMemberStatusPublisher
{
    public function __construct(
        private HubInterface $hub,
    ) {
    }

    public function publish(Room $room): void
    {
        $this->hub->publish(
            new Update(
                $room->getUuid(),
                json_encode([
                    'target' => 'member_list',
                    'event' => 'member_list:update',
                    'url' => '/room/' . $room->getUuid() . '/member_list/update',
                ])
            )
        );
    }
}
