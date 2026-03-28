<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $documentName ?? __('Document') }}</title>
    <style>
        /* ── Base Typography ──────────────────────── */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Georgia, 'Times New Roman', Times, serif;
            font-size: 14px;
            line-height: 1.6;
            color: #1a1a1a;
            background: #fff;
            padding: 0;
        }

        /* ── Screen-only controls ─────────────────── */
        .print-controls {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            background: #f8f9fa;
            border-bottom: 2px solid #dee2e6;
            padding: 12px 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            z-index: 9999;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        .print-controls .btn {
            display: inline-block;
            padding: 8px 20px;
            border-radius: 4px;
            text-decoration: none;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            border: 1px solid transparent;
        }

        .print-controls .btn-primary {
            background: #0054a6;
            color: #fff;
            border-color: #0054a6;
        }

        .print-controls .btn-primary:hover {
            background: #004085;
        }

        .print-controls .btn-secondary {
            background: #e9ecef;
            color: #495057;
            border-color: #dee2e6;
        }

        .print-controls .btn-secondary:hover {
            background: #dee2e6;
        }

        .print-controls .doc-title {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            font-size: 14px;
            color: #495057;
        }

        /* ── Document Container ──────────────────── */
        .document-container {
            max-width: 8.5in;
            margin: 70px auto 40px;
            padding: 1in;
            background: #fff;
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
            min-height: 11in;
        }

        /* ── Content Styles ──────────────────────── */
        .document-content h1 {
            font-size: 22px;
            margin-bottom: 10px;
        }

        .document-content h2 {
            font-size: 18px;
            margin-bottom: 8px;
            margin-top: 20px;
        }

        .document-content h3 {
            font-size: 16px;
            margin-bottom: 6px;
            margin-top: 16px;
        }

        .document-content p {
            margin-bottom: 10px;
        }

        .document-content table {
            width: 100%;
            border-collapse: collapse;
            margin: 10px 0;
        }

        .document-content table td,
        .document-content table th {
            padding: 8px;
            border: 1px solid #ddd;
            text-align: left;
        }

        .document-content ul, .document-content ol {
            margin: 10px 0;
            padding-left: 30px;
        }

        .document-content li {
            margin-bottom: 4px;
        }

        .document-content hr {
            border: none;
            border-top: 1px solid #ccc;
            margin: 20px 0;
        }

        /* ── Print Styles ────────────────────────── */
        @media print {
            .print-controls {
                display: none !important;
            }

            body {
                padding: 0;
                background: #fff;
            }

            .document-container {
                margin: 0;
                padding: 0;
                box-shadow: none;
                max-width: none;
                min-height: auto;
            }

            /* Page setup */
            @page {
                size: letter;
                margin: 0.75in;
            }

            /* Avoid breaking inside important elements */
            h1, h2, h3, h4 {
                page-break-after: avoid;
            }

            table, figure, img {
                page-break-inside: avoid;
            }

            p {
                orphans: 3;
                widows: 3;
            }
        }
    </style>
</head>
<body>
    {{-- Screen-only toolbar --}}
    <div class="print-controls">
        <span class="doc-title">{{ $documentName ?? __('Document') }}</span>
        <div>
            <button class="btn btn-secondary" onclick="window.close()">{{ __('Close') }}</button>
            <button class="btn btn-primary" onclick="window.print()">{{ __('Print / Save PDF') }}</button>
        </div>
    </div>

    <div class="document-container">
        <div class="document-content">
            {!! $content !!}
        </div>
    </div>

    <script>
        // Auto-trigger print dialog after a brief delay for rendering
        window.addEventListener('load', function() {
            setTimeout(function() {
                window.print();
            }, 500);
        });
    </script>
</body>
</html>
