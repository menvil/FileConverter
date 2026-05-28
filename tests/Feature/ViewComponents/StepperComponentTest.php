<?php

it('renders converter stepper with active step', function () {
    $view = $this->blade(
        '<x-stepper :steps="[\'File\', \'Format\', \'Settings\', \'Convert\']" active="Format" />'
    );

    $view->assertSee('File');
    $view->assertSee('Format');
    $view->assertSee('Settings');
    $view->assertSee('Convert');
});

it('marks active step visually', function () {
    $view = $this->blade(
        '<x-stepper :steps="[\'File\', \'Format\']" active="Format" />'
    );

    $view->assertSee('aria-current="step"', false);
});
