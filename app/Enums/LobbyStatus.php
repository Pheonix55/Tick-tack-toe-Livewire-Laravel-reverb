<?php

namespace App\Enums;

enum LobbyStatus: string
{
    case WAITING = 'waiting';
    case FULL = 'full';
    case CLOSED = 'closed';
    case ACTIVE = 'active';
}
