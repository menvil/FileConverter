<?php

it('renders a card component with content', function () {
    $view = $this->blade('<x-card>Card content</x-card>');

    $view->assertSee('Card content');
});

it('renders an elevated card variant', function () {
    $view = $this->blade('<x-card variant="elevated">Panel</x-card>');

    $view->assertSee('Panel');
    $view->assertSee('shadow', false);
});

it('renders an interactive card variant', function () {
    $view = $this->blade('<x-card variant="interactive">Click me</x-card>');

    $view->assertSee('Click me');
    $view->assertSee('cursor-pointer', false);
});

it('respects padding prop', function () {
    $view = $this->blade('<x-card padding="none">Tight</x-card>');

    $view->assertSee('Tight');
    $view->assertDontSee('p-6', false);
});
