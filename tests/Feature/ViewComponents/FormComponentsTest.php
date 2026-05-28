<?php

it('renders text input with label', function () {
    $view = $this->blade('<x-form.input name="filename" label="File name" />');

    $view->assertSee('File name');
    $view->assertSee('name="filename"', false);
});

it('renders select with label and options', function () {
    $view = $this->blade(
        '<x-form.select name="quality" label="Quality"><option>High</option></x-form.select>'
    );

    $view->assertSee('Quality');
    $view->assertSee('High');
});

it('renders toggle control', function () {
    $view = $this->blade('<x-form.toggle name="strip" label="Strip metadata" />');

    $view->assertSee('Strip metadata');
    $view->assertSee('role="switch"', false);
});

it('renders segmented control', function () {
    $view = $this->blade(
        '<x-form.segmented name="fit" :options="[\'cover\' => \'Cover\', \'contain\' => \'Contain\']" />'
    );

    $view->assertSee('Cover');
    $view->assertSee('Contain');
});

it('renders color picker control', function () {
    $view = $this->blade('<x-form.color name="bg" label="Background" />');

    $view->assertSee('Background');
    $view->assertSee('type="color"', false);
});

it('shows error and hint on input', function () {
    $view = $this->blade(
        '<x-form.input name="x" label="X" hint="Tip text" error="Bad value" />'
    );

    $view->assertSee('Tip text');
    $view->assertSee('Bad value');
});
