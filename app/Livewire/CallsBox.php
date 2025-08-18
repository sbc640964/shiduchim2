<?php

namespace App\Livewire;

use App\Models\Call;
use App\Models\Family;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Component;

class CallsBox extends Component implements HasActions, HasForms
{
    use InteractsWithActions;
    use InteractsWithForms;

    public bool $isOpen = false;

    /**
     * @var Collection<Call>|null
     */
    protected ?Collection $call = null;

    protected ?int $countPersonProposals = null;

    public bool $show = false;

    public function toggle(): void
    {
        $this->isOpen = !$this->isOpen;
    }

    public function render()
    {
        return view('filament.calls-box');
    }

    public function getListeners(): array
    {
        return [
            'echo-private:extension.'.auth()->user()->ext.',.update-call' => '$refresh',
        ];
    }

    public function getCall(): ?Call
    {
        if ($this->call == null) {
            $this->call = Call::activeCall(false);
        }

        if(! $this->call) {
            return null;
        }

        //if there are a call that started, return this call
        //else return the first call
        return $this->call->whereNotNull('started_at')
            ->first() ?? $this->call->first();
    }

    public function hasCall(): bool
    {
        return $this->getCall() !== null;
    }

    public function getDialName()
    {
        return $this->getCall()?->getDialName();
    }

    public function forceEndTheCall(): Action
    {
        return Action::make('forceEndTheCall')
            ->requiresConfirmation()
            ->iconButton()
            ->icon('heroicon-c-stop')
            ->tooltip('סיים שיחה')
            ->hidden(fn () => ! $this->hasCall())
            ->modalHeading('סיום שיחה שנתקעה')
            ->modalDescription(str($this->getForceDialogMessage())->toHtmlString())
            ->action(fn () => $this->getCall()?->forceEnd() ?? null);
    }

    public function getForceDialogMessage(): string
    {
        $callStartedAt = $this->getCall()->started_at?->format('H:i') ?? '00:00';
        $duration = $this->getCall()->started_at?->diffInMinutes(now()) ?? '0';

        return <<<HTML
           <div>
           שים לב! השיחה לא תסתיים במכשיר, פעולה זו נועדה לעדכן את המערכת על סיום מחייגת/נכנסת שנתקעה ולא עודכנה על סיומה.
            </div>
            <div>
            שיחה זו התחילה ב - $callStartedAt ונמשכת כבר $duration דקות.
        </div>
        <div>
        האם אתה בטוח שברצונך להכריח את סיום השיחה?
        </div>
HTML;

    }

    public function getStatusLabel(): ?string
    {
        return $this->getCall()?->getStatusLabel();
    }

    public function getPersons(): Collection
    {
        $call = $this->getCall();

        if (! $call) {
            return new Collection();
        }

        $model = $call->phoneModel?->model;

        if (! $model) {
            return new Collection();
        }

        if ($model instanceof Family) {
            return $model->people;
        }

        return new Collection([
            $call->phoneModel?->model,
        ]);
    }
}
