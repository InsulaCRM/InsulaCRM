<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $template->subject }}</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; margin: 0; padding: 20px; background: #f4f6fa; }
        .email-wrapper { max-width: 600px; margin: 0 auto; background: #fff; border-radius: 8px; overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
        .email-header { background: #206bc4; color: #fff; padding: 20px 30px; }
        .email-header h2 { margin: 0; font-size: 18px; }
        .email-body { padding: 30px; line-height: 1.6; color: #333; }
        .email-footer { padding: 15px 30px; background: #f8f9fa; font-size: 12px; color: #999; text-align: center; }
        .toolbar { max-width: 600px; margin: 0 auto 15px; text-align: right; }
        .toolbar button { background: #206bc4; color: #fff; border: none; padding: 8px 16px; border-radius: 4px; cursor: pointer; }
        @media print { .toolbar { display: none; } }
    </style>
</head>
<body>
    <div class="toolbar">
        <button onclick="window.print()">{{ __('Print / Save as PDF') }}</button>
    </div>
    <div class="email-wrapper">
        <div class="email-header">
            <h2>{{ $tenant->name }}</h2>
        </div>
        <div class="email-body">
            <p><strong>{{ __('Subject') }}:</strong> {{ $template->subject }}</p>
            <hr>
            <iframe srcdoc="{{ e(strip_tags($template->body, '<p><br><h1><h2><h3><h4><h5><h6><strong><em><b><i><u><a><ul><ol><li><table><tr><td><th><thead><tbody><hr><div><span><blockquote>')) }}" sandbox="" style="width:100%;min-height:300px;border:none;"></iframe>
        </div>
        <div class="email-footer">
            &copy; {{ date('Y') }} {{ $tenant->name }}
        </div>
    </div>
</body>
</html>
