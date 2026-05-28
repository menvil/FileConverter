<?php

it('renders dashboard inside the app layout', function () {
    $this->get('/dashboard')
        ->assertOk()
        ->assertSee('ConvertAI')
        ->assertSee('File Converter Dashboard')
        ->assertSee('Privacy Policy');
});
