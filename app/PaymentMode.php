<?php

namespace App;

enum PaymentMode: string
{
    case Drankkaart = 'drankkaart';
    case Scanner = 'scanner';
    case Beide = 'beide';

    public function label(): string
    {
        return match ($this) {
            self::Drankkaart => 'Enkel drankkaart',
            self::Scanner => 'Enkel kaartenscanner',
            self::Beide => 'Drankkaart + scanner',
        };
    }

    public function allowsDrankkaart(): bool
    {
        return $this !== self::Scanner;
    }

    public function allowsScanner(): bool
    {
        return $this !== self::Drankkaart;
    }
}
