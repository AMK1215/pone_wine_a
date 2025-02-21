<?php

namespace App\Enums;

enum UserType: int
{
    case Senior = 10;
    case Owner = 20;
    case Master = 30;
    case Agent = 40;
    case Player = 50;
    case SystemWallet = 60;
    case subAgent = 70;

    public static function usernameLength(UserType $type)
    {
        return match ($type) {
            self::Senior => 1,
            self::Owner => 2,
            self::Master => 3,
            self::Agent => 4,
            self::Player => 5,
            self::SystemWallet => 6,
            self::subAgent => 7
        };
    }

    public static function childUserType(UserType $type)
    {
        return match ($type) {
            self::Senior => self::Owner,
            self::Owner => self::Master,
            self::Master => self::Agent,
            self::Agent => self::subAgent,
            self::Agent => self::Player,
            self::Player => self::Player
        };
    }
}
