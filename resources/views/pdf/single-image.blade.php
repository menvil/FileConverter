<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<style>
    * { margin: 0; padding: 0; }
    body { margin: {{ $margin }}; }
    @if ($fitMode === 'contain')
    table { width: 100%; height: 100%; border-collapse: collapse; }
    td { text-align: center; vertical-align: middle; }
    img { max-width: 100%; max-height: 100%; }
    @elseif ($fitMode === 'cover')
    img { width: 100%; height: 100%; }
    @else
    img { display: block; }
    @endif
</style>
</head>
<body>
@if ($fitMode === 'contain')
<table><tr><td><img src="{{ $imageDataUri }}" alt=""></td></tr></table>
@else
<img src="{{ $imageDataUri }}" alt="">
@endif
</body>
</html>
