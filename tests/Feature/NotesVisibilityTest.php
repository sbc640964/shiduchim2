<?php

use App\Enums\NoteVisibility;
use App\Models\Note;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('allows any user to view public notes', function () {
    $owner = User::factory()->create();
    $viewer = User::factory()->create();

    $note = Note::query()->create([
        'owner_id' => $owner->id,
        'visibility' => NoteVisibility::Public,
        'content' => '<p>Public</p>',
    ]);

    expect($note->isVisibleTo($viewer))->toBeTrue();

    $visibleNotes = Note::query()->visibleTo($viewer)->pluck('id')->all();

    expect($visibleNotes)->toContain($note->id);
});

it('allows only owner (or shared users) to view private notes', function () {
    $owner = User::factory()->create();
    $sharedUser = User::factory()->create();
    $otherUser = User::factory()->create();

    $note = Note::query()->create([
        'owner_id' => $owner->id,
        'visibility' => NoteVisibility::Private,
        'content' => '<p>Private</p>',
    ]);

    expect($note->isVisibleTo($owner))->toBeTrue();
    expect($note->isVisibleTo($sharedUser))->toBeFalse();
    expect($note->isVisibleTo($otherUser))->toBeFalse();

    $note->sharedWithUsers()->sync([$sharedUser->id]);

    expect($note->fresh()->isVisibleTo($sharedUser))->toBeTrue();
    expect(Note::query()->visibleTo($sharedUser)->pluck('id')->all())->toContain($note->id);
    expect(Note::query()->visibleTo($otherUser)->pluck('id')->all())->not->toContain($note->id);
});
