<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; line-height: 1.6; color: #1e293b; margin: 0; padding: 0; background: #f1f5f9; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .card { background: #fff; border-radius: 8px; padding: 24px; margin-bottom: 16px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
        .header { text-align: center; padding: 20px 0; }
        .header h1 { margin: 0; font-size: 22px; color: #0054a6; }
        .section { margin-bottom: 20px; }
        .section h2 { font-size: 16px; color: #475569; margin: 0 0 12px 0; padding-bottom: 8px; border-bottom: 1px solid #e2e8f0; }
        .section ul { margin: 0; padding-left: 20px; }
        .section li { margin-bottom: 6px; font-size: 14px; }
        .badge { display: inline-block; padding: 2px 8px; border-radius: 4px; font-size: 12px; font-weight: 600; }
        .badge-red { background: #fef2f2; color: #dc2626; }
        .badge-yellow { background: #fffbeb; color: #d97706; }
        .badge-blue { background: #eff6ff; color: #2563eb; }
        .footer { text-align: center; font-size: 12px; color: #94a3b8; padding: 16px 0; }
    </style>
</head>
<body>
    <div class="container">
        <div class="card header">
            <h1>{{ $digestTitle }}</h1>
            @if($recipientName)
                <p style="color: #64748b; margin: 8px 0 0;">{{ __('Hello :name', ['name' => $recipientName]) }}</p>
            @endif
        </div>

        @foreach($sections as $section)
            <div class="card section">
                <h2>{{ $section['title'] }}</h2>
                @if(!empty($section['items']))
                    <ul>
                        @foreach($section['items'] as $item)
                            <li>{!! $item !!}</li>
                        @endforeach
                    </ul>
                @elseif(!empty($section['html']))
                    {!! $section['html'] !!}
                @else
                    <p style="color: #94a3b8; font-size: 14px;">{{ __('Nothing to report.') }}</p>
                @endif
            </div>
        @endforeach

        <div class="footer">
            <p>{{ __('This is an automated digest from your CRM.') }}</p>
        </div>
    </div>
</body>
</html>
