<?php

namespace App\Enum;

enum RoomEvent: string
{
    case ROOM_CREATED = 'room_created';
    case ROOM_EDITED = 'room_edited';
    case TICKET_CREATED = 'ticket_created';
    case TICKET_EDITED = 'ticket_edited';
    case TICKET_DELETED = 'ticket_deleted';
    case USER_JOINED = 'user_joined';
}
