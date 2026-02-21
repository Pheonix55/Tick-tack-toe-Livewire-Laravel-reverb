<?php

namespace App\Enums;

enum GameTurn: string
{
    case X = 'X';
    case O = 'O';
    case None = '';
}
