# Supported Conversions — MVP Matrix

Version: v0.1.0-mvp  
Date: 2026-06-05

This document lists the official supported conversion pairs for the File Converter MVP.  
No other conversions are supported in this release.

---

## Conversion Pairs

| Source | Target | Converter Key | Credit Cost | Options | Notes |
|--------|--------|---------------|-------------|---------|-------|
| PNG | JPG | `png_to_jpg` | 1 credit | quality | Imagick |
| PNG | WEBP | `png_to_webp` | 1 credit | quality | Imagick |
| PNG | PDF | `png_to_pdf` | 2 credits | — | Single-page PDF via Imagick |
| JPG | PNG | `jpg_to_png` | 1 credit | quality | Imagick |
| JPG | WEBP | `jpg_to_webp` | 1 credit | quality | Imagick |
| JPG | PDF | `jpg_to_pdf` | 2 credits | — | Single-page PDF via Imagick |

---

## Options

### quality

Available for image-to-image conversions (PNG→JPG, PNG→WEBP, JPG→PNG, JPG→WEBP).

| Value | Description |
|-------|-------------|
| `low` | Lower file size, reduced quality |
| `medium` | Balanced quality and size |
| `high` | Best quality, larger file |

Default: `high`

---

## Credit Costs

| Conversion Type | Base Cost |
|-----------------|-----------|
| Image → Image | 1 credit |
| Image → PDF | 2 credits |

Credit costs are defined in `config/conversion_costs.php`.

---

## Not Supported in MVP

```txt
- Audio formats (MP3, WAV, AAC, OGG)
- Video formats (MP4, AVI, MOV, MKV)
- Office documents (DOCX, XLSX, PPTX)
- OCR (image to text)
- SVG, HEIC, TIFF, RAW camera formats
- Multi-page PDF to image
- Batch conversion
```

---

## Requirements

- PHP extension: `ext-imagick` (ImageMagick)
- ImageMagick version: 6.x or 7.x

---

## Registry

Supported pairs are registered in `app/Support/Converters/ConverterRegistry.php`  
and validated against `config/converters.php` → `mvp_capabilities`.
