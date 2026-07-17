<?php

namespace App\EventSubscriber;

use App\Entity\User;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Http\Event\CheckPassportEvent;

class CheckVerifiedSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            CheckPassportEvent::class => 'onCheckPassport',
        ];
    }

    public function onCheckPassport(CheckPassportEvent $event): void
    {
        $user = $event->getPassport()->getUser();
        if ($user instanceof User && !$user->isVerified()) {
            throw new CustomUserMessageAuthenticationException(
                'Please verify your email before logging in.'
            );
        }
    }
}
