<?php

namespace App\Filament\Clusters\Settings\Resources\CallsDiaries\Schemas;

use App\Infolists\Components\AudioEntry;
use App\Livewire\ShowTranscriptionCall;
use App\Models\Call;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Livewire;
use Filament\Schemas\Schema;

class CallRecordSchema
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                Group::make([
                    TextEntry::make('duration')
                        ->label('משך הקלטה')
                        ->formatStateUsing(fn (Call $call) => gmdate('H:i:s', $call->duration))
                        ->inlineLabel(),

                    AudioEntry::make('audio_url')
                        ->state(fn (Call $call) => urldecode((string) $call->audio_url))
                        ->autoplay()
                        ->hiddenLabel(),
                ]),
                    Group::make([
                        Livewire::make(ShowTranscriptionCall::class)->lazy(),
                    ])

//                    InfolistComponents\TextEntry::make('text_call')
//                        ->label('טקסט שיחה')
//                        ->hintAction(Action::make('refresh_call_text')
//                            ->icon('heroicon-o-arrow-path')
//                            ->iconButton()
//                            ->tooltip('נתח מחדש את הטקסט של השיחה')
//                            ->color('gray')
//                            ->hidden(fn (Call $call) => $call->transcription)
//                            ->successNotificationTitle('ההקלטה נשלחה לניתוח ע"י המערכת, ככל הנראה התמלול יהיה מוכן בקרוב, נסה להיכנס לכאן בעוד כמה דקות שוב :)')
//                            ->action(function (Call $call, Action $action) {
//                                TranscriptionCallJob::dispatch($call->id);
//                                $action->success();
//                            })
//                            ->visible(auth()->user()->can('ai_beta'))
//                        )
//                        ->html()
            ]);
    }
}
