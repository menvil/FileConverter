<?php

use App\Support\Files\UploadedFileRules;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Validator;

it('provides upload validation rules for mvp formats', function () {
    $rules = UploadedFileRules::rules();
    $joined = implode('|', $rules);

    expect($rules)->toContain('file');
    expect($rules)->toContain('required');
    expect($joined)->toContain('mimes:png,jpg,jpeg,webp,pdf');
    expect($joined)->toContain('max:');
});

it('accepts a valid png upload', function () {
    $validator = Validator::make(
        ['file' => UploadedFile::fake()->image('image.png')],
        ['file' => UploadedFileRules::rules()],
    );

    expect($validator->passes())->toBeTrue();
});

it('rejects an unsupported uploaded file', function () {
    $validator = Validator::make(
        ['file' => UploadedFile::fake()->create('note.txt', 1, 'text/plain')],
        ['file' => UploadedFileRules::rules()],
    );

    expect($validator->fails())->toBeTrue();
});
