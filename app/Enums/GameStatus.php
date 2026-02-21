<?php

namespace App\Enums;

enum GameStatus: string
{
    case IN_PROGRESS = 'in_progress';
    case DRAW = 'draw';
    case X_WON = 'X_won';
    case O_WON = 'O_won';
    case ABANDONED = 'abandoned';
}
