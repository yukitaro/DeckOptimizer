<?php

namespace App\Enums;

enum Visibility: string
{
    case Public = 'public';
    case Private = 'private';
    case Granted = 'granted';
    // case FriendsOnly = 'friends_only'; // easy to add more later
}