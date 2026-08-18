<?php

namespace App\Security\Voter;

use App\Entity\Room;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class RoomVoter extends Voter
{
    public const START = 'ROUND_START';
    public const REVEAL = 'ROUND_REVEAL';
    public const EDIT = 'ROOM_EDIT';
    public const DELETE = 'ROOM_DELETE';

    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, [
                self::START,
                self::REVEAL,
                self::EDIT,
                self::DELETE,
            ])
            && $subject instanceof Room;
    }

    protected function voteOnAttribute(
        string $attribute,
        mixed $subject,
        TokenInterface $token
    ): bool {
        $user = $token->getUser();
        if (!$user instanceof User) {
            return false;
        }

        $room = $subject;
        return match ($attribute) {
            self::START, self::REVEAL, self::EDIT, self::DELETE =>
                $room->getOwner() === $user,
            default => false,
        };
    }
}
