<?php

declare(strict_types=1);

it('renders buy credits cta component', function () {
    $view = $this->blade('<x-billing.buy-credits-cta />');

    $view->assertSee('Buy credits')
        ->assertSee('Get more conversion credits');
});

it('renders buy credits cta in compact variant', function () {
    $view = $this->blade('<x-billing.buy-credits-cta variant="compact" />');

    $view->assertSee('Buy credits');
});
