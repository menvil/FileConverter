<?php

return [

    /*
    |--------------------------------------------------------------------------
    | MVP Converter Capabilities
    |--------------------------------------------------------------------------
    |
    | The whitelist of supported conversion directions for the Phase 04 MVP.
    | Format: "source:target". Only converters listed here may be registered
    | in the ConverterRegistry during the MVP phase.
    |
    */

    'mvp_capabilities' => [
        'png:jpg',
        'png:webp',
        'png:pdf',
        'jpg:png',
        'jpg:webp',
        'jpg:pdf',
    ],

];
