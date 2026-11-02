<?php

namespace App\Enum;

enum RoomMemberRole: string
{
    case OWNER = 'owner';
    case MEMBER = 'member';
}
