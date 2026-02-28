<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>{{ $title }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'DejaVu Sans', Arial, Helvetica, sans-serif;
            font-size: 10px;
            color: #1f2937;
            line-height: 1.4;
        }

        .header {
            text-align: center;
            margin-bottom: 16px;
            padding-bottom: 12px;
            border-bottom: 2px solid #4f46e5;
        }

        .header h1 {
            font-size: 18px;
            font-weight: 700;
            color: #1e1b4b;
            margin-bottom: 4px;
        }

        .header .subtitle {
            font-size: 10px;
            color: #6b7280;
        }

        .filters {
            margin-bottom: 12px;
            padding: 8px 12px;
            background-color: #f3f4f6;
            border-radius: 4px;
            font-size: 9px;
            color: #4b5563;
        }

        .filters strong {
            color: #1f2937;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 16px;
        }

        thead th {
            background-color: #4f46e5;
            color: #ffffff;
            font-weight: 600;
            font-size: 9px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 8px 6px;
            text-align: left;
            border: 1px solid #4338ca;
        }

        tbody td {
            padding: 6px;
            border: 1px solid #e5e7eb;
            font-size: 9px;
            vertical-align: top;
        }

        tbody tr:nth-child(even) {
            background-color: #f9fafb;
        }

        tbody tr:hover {
            background-color: #eef2ff;
        }

        .footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            text-align: center;
            font-size: 8px;
            color: #9ca3af;
            padding: 8px 0;
            border-top: 1px solid #e5e7eb;
        }

        .record-count {
            text-align: right;
            font-size: 9px;
            color: #6b7280;
            margin-bottom: 8px;
        }

        @page {
            margin: 40px 30px 50px 30px;
        }
    </style>
</head>

<body>
    <div class="header">
        <h1>{{ $title }}</h1>
        <div class="subtitle">Generated on {{ $date }} | OSA Information Management System</div>
    </div>

    @if(!empty($filters))
        <div class="filters">
            <strong>Active Filters:</strong>
            @foreach($filters as $key => $value)
                @if($value)
                    {{ ucfirst(str_replace('_', ' ', $key)) }}: <strong>{{ $value }}</strong>@if(!$loop->last) &nbsp;|&nbsp; @endif
                @endif
            @endforeach
        </div>
    @endif

    <div class="record-count">Total Records: {{ count($rows) }}</div>

    <table>
        <thead>
            <tr>
                @foreach($headers as $header)
                    <th>{{ $header }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse($rows as $row)
                <tr>
                    @foreach($row as $cell)
                        <td>{{ $cell }}</td>
                    @endforeach
                </tr>
            @empty
                <tr>
                    <td colspan="{{ count($headers) }}" style="text-align: center; padding: 20px; color: #9ca3af;">
                        No records found.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        Page
        <script type="text/php">
            if (isset($pdf)) {
                $x = 270;
                $y = 818;
                $text = "Page {PAGE_NUM} of {PAGE_COUNT}";
                $font = $fontMetrics->get_font("DejaVu Sans", "normal");
                $size = 8;
                $color = array(0.61, 0.64, 0.69);
                $word_space = 0.0;
                $char_space = 0.0;
                $angle = 0.0;
                $pdf->page_text($x, $y, $text, $font, $size, $color, $word_space, $char_space, $angle);
            }
        </script>
    </div>
</body>

</html>