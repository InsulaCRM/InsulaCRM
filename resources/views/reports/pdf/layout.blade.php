<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title') - {{ $tenant->name }}</title>
    <style>
        /* ── Reset & base ───────────────────────────────── */
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            font-size: 13px;
            line-height: 1.5;
            color: #1e293b;
            background: #f8fafc;
            padding: 0;
        }

        /* ── Screen wrapper ─────────────────────────────── */
        .print-wrapper {
            max-width: 900px;
            margin: 0 auto;
            padding: 24px;
        }

        /* ── Toolbar (hidden on print) ──────────────────── */
        .print-toolbar {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 24px;
            padding: 12px 16px;
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
        }

        .print-toolbar .btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 20px;
            font-size: 14px;
            font-weight: 600;
            color: #fff;
            background: #0054a6;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            text-decoration: none;
        }

        .print-toolbar .btn:hover {
            background: #003d7a;
        }

        .print-toolbar .btn-outline {
            color: #475569;
            background: transparent;
            border: 1px solid #cbd5e1;
        }

        .print-toolbar .btn-outline:hover {
            background: #f1f5f9;
        }

        .print-toolbar .spacer {
            flex: 1;
        }

        /* ── Report page ────────────────────────────────── */
        .report-page {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 40px;
        }

        /* ── Header ─────────────────────────────────────── */
        .report-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            border-bottom: 2px solid #0054a6;
            padding-bottom: 16px;
            margin-bottom: 28px;
        }

        .report-header .company-info h1 {
            font-size: 22px;
            font-weight: 700;
            color: #0054a6;
            margin-bottom: 2px;
        }

        .report-header .company-info .report-type {
            font-size: 16px;
            font-weight: 500;
            color: #475569;
        }

        .report-header .report-meta {
            text-align: right;
            font-size: 12px;
            color: #64748b;
        }

        .report-header .report-meta .date-range {
            font-size: 13px;
            font-weight: 600;
            color: #1e293b;
        }

        /* ── Logo placeholder ───────────────────────────── */
        .company-logo {
            width: 48px;
            height: 48px;
            background: #e2e8f0;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            font-weight: 700;
            color: #0054a6;
            margin-right: 12px;
            flex-shrink: 0;
        }

        .header-left {
            display: flex;
            align-items: flex-start;
        }

        /* ── Section ────────────────────────────────────── */
        .report-section {
            margin-bottom: 28px;
        }

        .report-section h2 {
            font-size: 15px;
            font-weight: 700;
            color: #0054a6;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 1px solid #e2e8f0;
            padding-bottom: 6px;
            margin-bottom: 12px;
        }

        /* ── Tables ─────────────────────────────────────── */
        .report-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
        }

        .report-table thead th {
            background: #f1f5f9;
            color: #475569;
            font-weight: 600;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 8px 12px;
            text-align: left;
            border-bottom: 2px solid #e2e8f0;
        }

        .report-table thead th.text-right,
        .report-table tbody td.text-right {
            text-align: right;
        }

        .report-table thead th.text-center,
        .report-table tbody td.text-center {
            text-align: center;
        }

        .report-table tbody td {
            padding: 8px 12px;
            border-bottom: 1px solid #f1f5f9;
            vertical-align: middle;
        }

        .report-table tbody tr:nth-child(even) {
            background: #fafbfc;
        }

        .report-table tbody tr:last-child td {
            border-bottom: none;
        }

        .report-table tfoot td {
            padding: 10px 12px;
            font-weight: 700;
            border-top: 2px solid #e2e8f0;
            background: #f8fafc;
        }

        /* ── Summary cards ──────────────────────────────── */
        .summary-cards {
            display: flex;
            gap: 16px;
            margin-bottom: 24px;
            flex-wrap: wrap;
        }

        .summary-card {
            flex: 1;
            min-width: 140px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 14px 16px;
            text-align: center;
        }

        .summary-card .card-value {
            font-size: 24px;
            font-weight: 700;
            color: #0054a6;
            line-height: 1.2;
        }

        .summary-card .card-label {
            font-size: 11px;
            font-weight: 600;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-top: 4px;
        }

        /* ── Rank badge ─────────────────────────────────── */
        .rank-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 24px;
            height: 24px;
            border-radius: 50%;
            font-size: 11px;
            font-weight: 700;
            color: #fff;
        }

        .rank-1 { background: #f59e0b; }
        .rank-2 { background: #94a3b8; }
        .rank-3 { background: #b45309; }
        .rank-default { background: #cbd5e1; color: #475569; }

        /* ── Footer ─────────────────────────────────────── */
        .report-footer {
            margin-top: 32px;
            padding-top: 12px;
            border-top: 1px solid #e2e8f0;
            display: flex;
            justify-content: space-between;
            font-size: 11px;
            color: #94a3b8;
        }

        /* ── Print styles ───────────────────────────────── */
        @media print {
            body {
                background: #fff;
                padding: 0;
                font-size: 11px;
            }

            .print-toolbar {
                display: none !important;
            }

            .print-wrapper {
                max-width: none;
                padding: 0;
            }

            .report-page {
                border: none;
                border-radius: 0;
                padding: 0;
                box-shadow: none;
            }

            .summary-card .card-value {
                font-size: 20px;
            }

            .report-table {
                font-size: 11px;
            }

            .report-table thead th {
                background: #f1f5f9 !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            .report-table tbody tr:nth-child(even) {
                background: #fafbfc !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            .summary-card {
                background: #f8fafc !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            .rank-badge {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            .report-footer {
                position: fixed;
                bottom: 0;
                left: 0;
                right: 0;
                padding: 8px 0;
            }

            @page {
                size: A4;
                margin: 15mm 12mm;
            }
        }
    </style>
</head>
<body>
    <div class="print-wrapper">
        {{-- Toolbar --}}
        <div class="print-toolbar">
            <button class="btn" onclick="window.print()">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 6 2 18 2 18 9"></polyline><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path><rect x="6" y="14" width="12" height="8"></rect></svg>
                Print / Save as PDF
            </button>
            <a href="{{ route('reports.index') }}" class="btn btn-outline">Back to Reports</a>
            <span class="spacer"></span>
            <span style="color:#64748b; font-size:12px;">Use "Save as PDF" in the print dialog to export.</span>
        </div>

        {{-- Report content --}}
        <div class="report-page">
            {{-- Header --}}
            <div class="report-header">
                <div class="header-left">
                    <div class="company-logo">
                        {{ strtoupper(substr($tenant->name, 0, 1)) }}
                    </div>
                    <div class="company-info">
                        <h1>{{ $tenant->name }}</h1>
                        <div class="report-type">@yield('report-title')</div>
                    </div>
                </div>
                <div class="report-meta">
                    <div class="date-range">{{ \Carbon\Carbon::parse($from)->format('M d, Y') }} &mdash; {{ \Carbon\Carbon::parse($to)->format('M d, Y') }}</div>
                    <div>Generated on {{ now()->format('M d, Y \a\t g:i A') }}</div>
                </div>
            </div>

            {{-- Body --}}
            @yield('content')

            {{-- Footer --}}
            <div class="report-footer">
                <span>{{ $tenant->name }} &mdash; @yield('report-title')</span>
                <span>Generated on {{ now()->format('M d, Y \a\t g:i A') }}</span>
            </div>
        </div>
    </div>
</body>
</html>
