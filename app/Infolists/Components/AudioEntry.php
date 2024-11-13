<?php

namespace App\Infolists\Components;

use Filament\Infolists\Components\Concerns\CanFormatState;
use Filament\Infolists\Components\Entry;

class AudioEntry extends Entry
{
    use CanFormatState;

    protected string $view = 'filament.infolists.entries.audio-entry';

    protected bool|\Closure|null $autoplay = false;

    protected bool|\Closure|null $controls = true;

    public function autoplay(bool|\Closure|null $condition = true): static
    {
        $this->autoplay = $condition;

        return $this;
    }

    public function getAutoplay(): bool
    {
        return $this->evaluate($this->autoplay);
    }

    public function controls(bool|\Closure|null $condition = true): static
    {
        $this->controls = $condition;

        return $this;
    }

    public function showControls(): bool
    {
        return $this->evaluate($this->controls);
    }
}
