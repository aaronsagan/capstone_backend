<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>@yield('title', 'CharityHub Report')</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 10pt;
            color: #333;
            line-height: 1.6;
            padding: 20px;
        }
        
        .header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 3px solid #10b981;
        }
        
        .logo {
            font-size: 24pt;
            font-weight: bold;
            color: #10b981;
            margin-bottom: 5px;
        }
        
        .subtitle {
            font-size: 9pt;
            color: #6b7280;
        }
        
        .report-title {
            font-size: 18pt;
            font-weight: bold;
            color: #1f2937;
            margin: 20px 0 10px 0;
            text-align: center;
        }
        
        .meta-info {
            background: #f3f4f6;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            font-size: 9pt;
        }
        
        .meta-info table {
            width: 100%;
        }
        
        .meta-info td {
            padding: 4px 0;
        }
        
        .meta-info td:first-child {
            font-weight: bold;
            width: 35%;
            color: #4b5563;
        }
        
        .content {
            margin: 20px 0;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
            font-size: 9pt;
        }
        
        table thead {
            background: #10b981;
            color: white;
        }
        
        table th {
            padding: 10px 8px;
            text-align: left;
            font-weight: bold;
        }
        
        table td {
            padding: 8px;
            border-bottom: 1px solid #e5e7eb;
        }
        
        table tbody tr:nth-child(even) {
            background: #f9fafb;
        }
        
        table tbody tr:hover {
            background: #f3f4f6;
        }
        
        .text-right {
            text-align: right;
        }
        
        .text-center {
            text-align: center;
        }
        
        .summary-box {
            background: #ecfdf5;
            border: 2px solid #10b981;
            border-radius: 5px;
            padding: 15px;
            margin: 20px 0;
        }
        
        .summary-box h3 {
            color: #10b981;
            margin-bottom: 10px;
            font-size: 12pt;
        }
        
        .summary-grid {
            display: table;
            width: 100%;
        }
        
        .summary-item {
            display: table-cell;
            padding: 10px;
            text-align: center;
            border-right: 1px solid #10b981;
        }
        
        .summary-item:last-child {
            border-right: none;
        }
        
        .summary-item .label {
            font-size: 8pt;
            color: #6b7280;
            text-transform: uppercase;
            display: block;
            margin-bottom: 5px;
        }
        
        .summary-item .value {
            font-size: 14pt;
            font-weight: bold;
            color: #10b981;
        }
        
        .footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            height: 50px;
            text-align: center;
            font-size: 8pt;
            color: #9ca3af;
            border-top: 1px solid #e5e7eb;
            padding-top: 10px;
            background: white;
        }
        
        .page-break {
            page-break-after: always;
        }
        
        .badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 3px;
            font-size: 8pt;
            font-weight: bold;
        }
        
        .badge-success {
            background: #d1fae5;
            color: #065f46;
        }
        
        .badge-warning {
            background: #fef3c7;
            color: #92400e;
        }
        
        .badge-danger {
            background: #fee2e2;
            color: #991b1b;
        }
        
        .badge-info {
            background: #dbeafe;
            color: #1e40af;
        }
        
        .amount {
            font-weight: bold;
            color: #10b981;
        }
        
        .watermark {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-45deg);
            font-size: 80pt;
            color: rgba(16, 185, 129, 0.05);
            z-index: -1;
            font-weight: bold;
        }
        
        h2 {
            color: #1f2937;
            font-size: 14pt;
            margin: 20px 0 10px 0;
            padding-bottom: 5px;
            border-bottom: 2px solid #10b981;
        }
        
        h3 {
            color: #374151;
            font-size: 12pt;
            margin: 15px 0 8px 0;
        }
        
        p {
            margin: 5px 0;
        }
        
        .note {
            background: #fffbeb;
            border-left: 4px solid #f59e0b;
            padding: 10px 15px;
            margin: 15px 0;
            font-size: 9pt;
        }
        
        .total-row {
            font-weight: bold;
            background: #ecfdf5 !important;
            border-top: 2px solid #10b981;
        }
    </style>
</head>
<body>
    <div class="watermark">CHARITYHUB</div>
    
    <div class="header">
        <div class="logo">CharityHub</div>
        <div class="subtitle">Philippine Online Charity Platform</div>
    </div>
    
    <div class="report-title">
        @yield('report-title')
    </div>
    
    <div class="meta-info">
        <table>
            <tr>
                <td>Report Generated:</td>
                <td>{{ $generated_at ?? now()->format('F d, Y - h:i A') }}</td>
            </tr>
            <tr>
                <td>Generated By:</td>
                <td>{{ $generated_by ?? 'System' }}</td>
            </tr>
            @yield('meta-info')
        </table>
    </div>
    
    <div class="content">
        @yield('content')
    </div>
    
    <div class="footer">
        <div>
            <strong>CharityHub</strong> - Empowering Filipino Charities<br>
            © {{ date('Y') }} CharityHub. All Rights Reserved. | Generated: {{ now()->format('Y-m-d H:i:s') }}
        </div>
    </div>
</body>
</html>
