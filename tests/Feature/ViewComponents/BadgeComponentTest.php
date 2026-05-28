<?php

it('renders a success badge', function () {
    $view = $this->blade('<x-badge variant="success">Completed</x-badge>');

    $view->assertSee('Completed');
});

it('renders a gradient badge', function () {
    $view = $this->blade('<x-badge variant="gradient">PRO</x-badge>');

    $view->assertSee('PRO');
    $view->assertSee('ca-gradient', false);
});

it('renders a small badge', function () {
    $view = $this->blade('<x-badge size="sm">New</x-badge>');

    $view->assertSee('New');
});
