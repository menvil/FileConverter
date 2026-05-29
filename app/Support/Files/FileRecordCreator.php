<?php

namespace App\Support\Files;

use App\Models\FileRecord;

class FileRecordCreator
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function create(array $attributes): FileRecord
    {
        return FileRecord::query()->create($attributes);
    }
}
