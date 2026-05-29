<?php

use App\Enums\FileStatus;
use App\Models\FileRecord;

it('casts file status to enum', function () {
    $file = FileRecord::factory()->create([
        'status' => FileStatus::Analyzed,
    ]);

    expect($file->fresh()->status)->toBe(FileStatus::Analyzed);
});

it('exposes all expected file statuses', function () {
    $values = array_map(fn (FileStatus $s) => $s->value, FileStatus::cases());

    expect($values)->toEqual(['uploaded', 'analyzed', 'failed', 'expired', 'deleted']);
});
