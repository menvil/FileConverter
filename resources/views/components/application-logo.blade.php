@props([
    'size' => 'text-2xl',
])

<span
    {{ $attributes->merge(['class' => $size.' font-black tracking-tight text-gray-900']) }}
    style="font-family:'Geist',ui-sans-serif,system-ui,sans-serif;letter-spacing:-1px;"
>File<span class="bg-gradient-to-r from-pink-500 to-purple-500 bg-clip-text text-transparent">Converter</span></span>
