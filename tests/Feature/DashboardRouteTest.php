<?php

it('renders dashboard placeholder page', function () {
    $this->get('/dashboard')
        ->assertOk()
        ->assertSee('File Converter Dashboard');
});
