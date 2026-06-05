# Supported Conversions — MVP Matrix

Version: v0.1.0-mvp  
Date: 2026-06-05

This document lists the official supported conversion pairs for the File Converter MVP.  
No other conversions are supported in this release.

---

## Conversion Pairs

| Source | Target | Converter Key | Credit Cost | Options | Notes |
|--------|--------|---------------|-------------|---------|-------|
| PNG | JPG | `png:jpg` | 1 credit | quality | Imagick |
| PNG | WEBP | `png:webp` | 1 credit | quality | Imagick |
| PNG | PDF | `png:pdf` | 2 credits | — | Single-page PDF via Imagick |
| JPG | PNG | `jpg:png` | 1 credit | resize, remove_metadata | Imagick |
| JPG | WEBP | `jpg:webp` | 1 credit | quality | Imagick |
| JPG | PDF | `jpg:pdf` | 2 credits | — | Single-page PDF via Imagick |

---

## Options

### quality

Available for PNG→JPG, PNG→WEBP, JPG→WEBP conversions.

| Value | Description |
|-------|-------------|
| `medium` | Balanced quality and size |
| `high` | Good quality |
| `best` | Highest quality, largest file |

Default: `high`

### resize (JPG→PNG only)

| Value | Description |
|-------|-------------|
| `original` | Keep original dimensions |
| `1920` | Resize to max 1920 px on the longest side |
| `1280` | Resize to max 1280 px on the longest side |
| `custom` | Custom resize (via UI) |

Default: `original`

### remove_metadata (JPG→PNG only)

Removes EXIF and other embedded metadata. Default: `true`.

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
Converter keys are colon-separated `source:target` (e.g. `png:jpg`), distinct from
the `source_format`/`target_format` stored on conversion job records.
