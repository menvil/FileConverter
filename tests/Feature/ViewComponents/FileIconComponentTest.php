<?php

it('renders a png file icon', function () {
    $view = $this->blade('<x-file-icon format="png" />');

    $view->assertSee('PNG');
});

it('normalizes jpeg to JPG label', function () {
    $view = $this->blade('<x-file-icon format="jpeg" />');

    $view->assertSee('JPG');
});

it('renders a webp file icon', function () {
    $view = $this->blade('<x-file-icon format="webp" />');

    $view->assertSee('WEBP');
});

it('renders a pdf file icon', function () {
    $view = $this->blade('<x-file-icon format="pdf" />');

    $view->assertSee('PDF');
});

it('renders an unknown file icon fallback', function () {
    $view = $this->blade('<x-file-icon format="unknown" />');

    $view->assertSee('FILE');
});
