<?php

namespace App\Enums;

enum UserAddressLabelEnum: int
{
    case Home = 1;
    case Work = 2;
    case Billing = 3;
    case Shipping = 4;
    case Other = 99;

    public function label(): string
    {
        return match ($this) {
            self::Home => 'Home',
            self::Work => 'Work',
            self::Billing => 'Billing',
            self::Shipping => 'Shipping',
            self::Other => 'Other',
        };
    }

    /** @return array<int, int> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /** @return array<int, string> */
    public static function options(): array
    {
        return array_reduce(
            self::cases(),
            fn (array $carry, self $case) => $carry + [$case->value => $case->label()],
            [],
        );
    }
}
