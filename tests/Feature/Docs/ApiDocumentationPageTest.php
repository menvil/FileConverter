<?php

declare(strict_types=1);

it('renders api documentation page', function () {
    $this->get('/docs/api')
        ->assertOk()
        ->assertSee('File Converter API');
});

it('shows developer quickstart on api docs page', function () {
    $this->get('/docs/api')
        ->assertOk()
        ->assertSee('Developer quickstart')
        ->assertSee('Upload a file')
        ->assertSee('Create a conversion')
        ->assertSee('Download the result');
});
