<?php

it('renders the login page', function () {
    $this->get('/login')
        ->assertOk()
        ->assertSeeInOrder(['File', 'Converter']);
});

it('renders the register page', function () {
    $this->get('/register')
        ->assertOk()
        ->assertSeeInOrder(['File', 'Converter']);
});
