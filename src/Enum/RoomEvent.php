<?php

namespace App\Enum;

enum RoomEvent: string
{
    case ROOM_CREATED = 'room_created';
    case ROOM_EDITED = 'room_edited';
    case ROOM_DELETED = 'room_deleted';

    case TICKET_CREATED = 'ticket_created';
    case TICKET_EDITED = 'ticket_edited';
    case TICKET_DELETED = 'ticket_deleted';

    case USER_JOINED = 'user_joined';
    case USER_LEAVE = 'user_leave';
    case USER_DELETED = 'user_deleted';
}
