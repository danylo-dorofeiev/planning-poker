<?php

namespace App\Enum;

enum TicketSource: string
{
    case LOCAL = 'local';
    case JIRA = 'jira';
}
