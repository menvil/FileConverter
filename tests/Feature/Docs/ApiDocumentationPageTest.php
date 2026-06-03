<?php

declare(strict_types=1);

it('renders api documentation page', function () {
    $this->get('/docs/api')
        ->assertOk()
        ->assertSee('File Converter API');
});
