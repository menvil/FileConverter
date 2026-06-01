<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { margin: {{ $margin }}; }
    .image-container {
        width: 100%;
        height: 100%;
        @if ($fitMode === 'contain')
        display: flex;
        align-items: center;
        justify-content: center;
        @endif
    }
    img {
        @if ($fitMode === 'contain')
        max-width: 100%;
        max-height: 100%;
        object-fit: contain;
        @elseif ($fitMode === 'fill')
        width: 100%;
        height: 100%;
        object-fit: fill;
        @else
        display: block;
        @endif
    }
</style>
</head>
<body>
<div class="image-container">
    <img src="{{ $imageDataUri }}" alt="">
</div>
</body>
</html>
