<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>File Converter API — Documentation</title>
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; padding: 0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: #fff; color: #1a202c; }
        .quickstart { max-width: 900px; margin: 0 auto; padding: 48px 32px 32px; }
        .quickstart h1 { font-size: 2rem; font-weight: 700; margin-bottom: 8px; }
        .quickstart .subtitle { color: #718096; margin-bottom: 40px; font-size: 1.05rem; }
        .quickstart h2 { font-size: 1.25rem; font-weight: 600; margin: 32px 0 16px; border-bottom: 1px solid #e2e8f0; padding-bottom: 8px; }
        .quickstart ol { padding-left: 20px; line-height: 1.8; }
        .quickstart ol li { margin-bottom: 6px; }
        .quickstart code { background: #f7fafc; border: 1px solid #e2e8f0; border-radius: 4px; padding: 2px 6px; font-size: 0.9em; font-family: 'SFMono-Regular', Consolas, monospace; }
        .quickstart pre { background: #1a202c; color: #e2e8f0; border-radius: 8px; padding: 20px; overflow-x: auto; font-size: 0.875rem; line-height: 1.6; margin: 12px 0 24px; font-family: 'SFMono-Regular', Consolas, monospace; }
        .quickstart pre code { background: none; border: none; padding: 0; color: inherit; }
        .quickstart .note { background: #ebf8ff; border-left: 4px solid #3182ce; padding: 12px 16px; border-radius: 0 6px 6px 0; margin: 16px 0; font-size: 0.95rem; }
        .divider { border: none; border-top: 2px solid #e2e8f0; margin: 40px 0 0; }
    </style>
</head>
<body>

<div class="quickstart">
    <h1>File Converter API</h1>
    <p class="subtitle">Convert files programmatically using the File Converter REST API.</p>

    <h2>Developer quickstart</h2>
    <p>Follow these steps to go from authentication to downloading a converted file.</p>

    <ol>
        <li><strong>Obtain an API key</strong> — Generate an API key from your account settings.</li>
        <li><strong>Upload a file</strong> — <code>POST /api/v1/files</code> with <code>multipart/form-data</code>.</li>
        <li><strong>List target formats</strong> — <code>GET /api/v1/files/{file}/targets</code> to see available conversions.</li>
        <li><strong>Estimate credit cost</strong> — <code>POST /api/v1/conversions/estimate</code> to preview the cost.</li>
        <li><strong>Create a conversion</strong> — <code>POST /api/v1/conversions</code> to start the job.</li>
        <li><strong>Poll status</strong> — <code>GET /api/v1/conversions/{conversion}</code> until <code>status</code> is <code>completed</code> or <code>failed</code>.</li>
        <li><strong>Download the result</strong> — <code>GET /api/v1/conversions/{conversion}/download</code>.</li>
    </ol>

    <div class="note">
        All requests require <code>Authorization: Bearer &lt;your-api-key&gt;</code>. API access requires a Pro or Max plan.
    </div>

    <h2>curl examples</h2>

    <p><strong>1. Upload a file</strong></p>
    <pre><code>curl -X POST "{{ url('/api/v1/files') }}" \
  -H "Authorization: Bearer fc_live_xxx" \
  -F "file=@product-photo.png"</code></pre>

    <p><strong>2. List target formats</strong></p>
    <pre><code>curl -X GET "{{ url('/api/v1/files/file_01HX7abc/targets') }}" \
  -H "Authorization: Bearer fc_live_xxx"</code></pre>

    <p><strong>3. Estimate conversion cost</strong></p>
    <pre><code>curl -X POST "{{ url('/api/v1/conversions/estimate') }}" \
  -H "Authorization: Bearer fc_live_xxx" \
  -H "Content-Type: application/json" \
  -d '{"file_id": "file_01HX7abc", "target_format": "jpg"}'</code></pre>

    <p><strong>4. Create a conversion</strong></p>
    <pre><code>curl -X POST "{{ url('/api/v1/conversions') }}" \
  -H "Authorization: Bearer fc_live_xxx" \
  -H "Content-Type: application/json" \
  -d '{
    "file_id": "file_01HX7abc",
    "target_format": "jpg",
    "options": {
      "quality": "high",
      "background": "#ffffff"
    }
  }'</code></pre>

    <p><strong>5. Poll conversion status</strong></p>
    <pre><code>curl -X GET "{{ url('/api/v1/conversions/conv_01HX8abc') }}" \
  -H "Authorization: Bearer fc_live_xxx"</code></pre>

    <p><strong>6. Download the result</strong></p>
    <pre><code>curl -L -X GET "{{ url('/api/v1/conversions/conv_01HX8abc/download') }}" \
  -H "Authorization: Bearer fc_live_xxx" \
  -o result.jpg</code></pre>

    <hr class="divider">
</div>

<redoc spec-url="{{ route('docs.api.openapi') }}"></redoc>
<script src="https://cdn.redoc.ly/redoc/2.x/bundles/redoc.standalone.js"></script>

</body>
</html>
