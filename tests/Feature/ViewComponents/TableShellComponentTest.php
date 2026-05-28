<?php

it('renders table shell with header and content', function () {
    $view = $this->blade(
        '<x-table.shell title="Recent Conversions">Empty</x-table.shell>'
    );

    $view->assertSee('Recent Conversions');
    $view->assertSee('Empty');
});

it('renders table shell with description', function () {
    $view = $this->blade(
        '<x-table.shell title="History" description="Your conversion history" />'
    );

    $view->assertSee('History');
    $view->assertSee('Your conversion history');
});

it('renders an action button when label is given', function () {
    $view = $this->blade(
        '<x-table.shell title="History" actionLabel="View all" actionUrl="#" />'
    );

    $view->assertSee('View all');
});

it('renders empty state component', function () {
    $view = $this->blade(
        '<x-table.empty-state title="No conversions yet" message="Upload a file to start" />'
    );

    $view->assertSee('No conversions yet');
    $view->assertSee('Upload a file to start');
});
