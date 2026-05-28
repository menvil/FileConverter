<?php

it('renders the login page', function () {
    $this->get('/login')
        ->assertOk()
        ->assertSee('File Converter');
});

it('renders the register page', function () {
    $this->get('/register')
        ->assertOk()
        ->assertSee('File Converter');
});
