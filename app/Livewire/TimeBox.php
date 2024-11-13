<?php

namespace App\Livewire;

use App\Models\TimeDiary;
use Livewire\Component;

class TimeBox extends Component
{
    public ?TimeDiary $activeTime = null;
    public ?int $current_seconds = null;
    public function render()
    {
        return view('filament.time-box');
    }

    public function getActiveTime()
    {
        $last = $this->activeTime ?? $this->activeTime = auth()->user()->activeTime();

        if($last) {
            $this->current_seconds = $last->start_at->diffInSeconds(now());
        }

        return $this->activeTime;
    }

    public function start()
    {
        $this->activeTime = auth()
            ->user()
            ->timeDiaries()
            ->create(['start_at' => now()]);
    }

    public function stop()
    {
        $this->activeTime->update(['end_at' => now()]);
        $this->activeTime = null;
        $this->current_seconds = null;
    }

    public function toggle()
    {
        if ($this->activeTime) {
            $this->stop();
        } else {
            $this->start();
        }
    }
}
