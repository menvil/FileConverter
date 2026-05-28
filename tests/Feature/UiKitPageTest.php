<?php

it('renders the UI kit showcase page', function () {
    $this->get('/ui-kit')
        ->assertOk()
        ->assertSee('UI Kit')
        ->assertSee('Buttons')
        ->assertSee('Cards')
        ->assertSee('Badges')
        ->assertSee('File Icons')
        ->assertSee('Stepper')
        ->assertSee('Form Controls')
        ->assertSee('Table Shell');
});
