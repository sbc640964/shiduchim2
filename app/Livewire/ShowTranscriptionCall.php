<?php

namespace App\Livewire;

use App\Jobs\TranscriptionCallJob;
use App\Models\Call;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Support\Enums\Size;
use Livewire\Component;

class ShowTranscriptionCall extends Component implements HasActions, HasSchemas
{
    use InteractsWithActions;
    use InteractsWithSchemas;


    public ?Call $record = null;

    public function mount(?Call $record = null): void
    {
//
    }

    public function render()
    {
        return view('livewire.show-transaction-call');
    }

    public function parseTranscription(): Action
    {
        $record = $this->record;

        return Action::make('parseTranscription')
            ->icon('heroicon-o-arrow-path')
            ->label('תמלל שיחה')
            ->color('gray')
            ->hidden(fn () => $record->transcription)
            ->successNotificationTitle('ההקלטה נשלחה לניתוח ע"י המערכת, ככל הנראה התמלול יהיה מוכן בקרוב, נסה להיכנס לכאן בעוד כמה דקות שוב :)')
            ->action(function (Action $action) use ($record) {
                TranscriptionCallJob::dispatch($record->id);
                $action->success();
            })
            ->visible(auth()->user()->can('ai_beta'));
    }

    public function reTranscriptionChunk(): Action
    {
        $record = $this->record;

        return Action::make('reTranscriptionChunk')
            ->label('נתח מחדש קטע זה')
            ->icon('heroicon-o-arrow-path')
            ->color('gray')
            ->nestingIndex(1)
            ->tooltip('נתח מחדש את הקטע הזה בשיחה')
            ->action(function (Action $action) {
                dump(100);
            })
//            ->action(function (array $arguments, Action $action) use ($record) {
//                dump($arguments);
//                $transcription = $record->transcription()->exists();
//
//                if (!$transcription) {
//                    $action->failureNotificationTitle('לא ניתן לנתח מחדש קטע זה, כי אין תמלול קיים לשיחה זו');
//                    $action->failure();
//                    return;
//                }
//
//                if(!isset($arguments['chunk_index'])){
//                    $action->failureNotificationTitle('לא ניתן לנתח מחדש קטע זה, כי לא נבחר קטע לניתוח מחדש');
//                    $action->failure();
//                    return;
//                }
//
//                TranscriptionCallJob::dispatch($record->id, $arguments['chunk_index']);
//
//                $action->successNotificationTitle('הקטע נשלח לניתוח ע"י המערכת, ככל הנראה התמלול יהיה מוכן בקרוב, נסה להיכנס לכאן בעוד כמה דקות שוב :)');
//                $action->success();
//            })
            ->size(Size::Small)
            ->visible(fn () => $record->transcription && auth()->user()->can('ai_beta'));
        // Dispatch job to reprocess the
    }

    public function placeholder()
    {
        return <<<'HTML'
        <div>
            <!-- Loading spinner... -->
            <svg>...</svg>
        </div>
        HTML;
    }
}
