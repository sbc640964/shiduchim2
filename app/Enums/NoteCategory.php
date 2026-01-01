<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;

enum NoteCategory: string implements HasColor, HasIcon, HasLabel
{
    case Note = 'note';
    case Call = 'call';
    case Meeting = 'meet';

    public function getLabel(): string|Htmlable|null
    {
        return match ($this) {
            self::Note => 'הערה',
            self::Call => 'שיחה',
            self::Meeting => 'פגישה',
        };
    }

    public function getIcon(): string|Heroicon|Htmlable|null
    {
        return match ($this) {
            self::Note => Heroicon::DocumentText,
            self::Call => Heroicon::Phone,
            self::Meeting => Heroicon::CalendarDays,
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Note => 'gray',
            self::Call => 'info',
            self::Meeting => 'success',
        };
    }
}
