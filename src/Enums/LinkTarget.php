<?php

declare(strict_types=1);

namespace Datlechin\FilamentMenuBuilder\Enums;

use Filament\Support\Contracts\HasLabel;

enum LinkTarget: string implements HasLabel
{
    case Self = '_self';

    case Blank = '_blank';

    case Parent = '_parent';

    case Top = '_top';


    public function getLabel(): ?string
    {
        return match ($this) {
            self::Self => 'Cùng tab',
            self::Blank => 'Tab mới',
            self::Parent => 'Tab cha',
            self::Top => 'Tab trên cùng',
        };
    }
}
