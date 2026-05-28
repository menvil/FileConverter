<?php

it('renders a primary button component', function () {
    $view = $this->blade('<x-button variant="primary">Upload</x-button>');

    $view->assertSee('Upload');
    $view->assertSee('type="button"', false);
});

it('renders a gradient button variant', function () {
    $view = $this->blade('<x-button variant="gradient">Convert Now</x-button>');

    $view->assertSee('Convert Now');
    $view->assertSee('ca-gradient', false);
});

it('renders a disabled button', function () {
    $view = $this->blade('<x-button :disabled="true">Off</x-button>');

    $view->assertSee('disabled', false);
});

it('merges custom attributes onto the button', function () {
    $view = $this->blade('<x-button id="cta">Go</x-button>');

    $view->assertSee('id="cta"', false);
});
