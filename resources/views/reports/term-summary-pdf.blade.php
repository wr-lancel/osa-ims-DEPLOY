<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Term Summary - {{ $summary['term_label'] ?? 'Report' }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'DejaVu Sans', Arial, sans-serif; font-size: 11px; color: #1f2937; line-height: 1.5; padding: 24px; }
        .header { text-align: center; margin-bottom: 20px; padding-bottom: 12px; border-bottom: 2px solid #4f46e5; }
        .header h1 { font-size: 18px; font-weight: 700; color: #1e1b4b; margin-bottom: 4px; }
        .header .subtitle { font-size: 10px; color: #6b7280; }
        table { width: 100%; border-collapse: collapse; margin-top: 12px; }
        th, td { padding: 10px 12px; text-align: left; border: 1px solid #e5e7eb; }
        th { background-color: #4f46e5; color: #fff; font-weight: 600; }
        tr:nth-child(even) { background-color: #f9fafb; }
        .footer { margin-top: 24px; font-size: 9px; color: #6b7280; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Semester / Term Summary Report</h1>
        <p class="subtitle">{{ $summary['term_label'] ?? 'No term selected' }}</p>
    </div>
    <table>
        <thead>
            <tr>
                <th>Module / Metric</th>
                <th>Count</th>
            </tr>
        </thead>
        <tbody>
            <tr><td>Enrolled students</td><td>{{ $summary['total_students'] ?? 0 }}</td></tr>
            <tr><td>Discipline cases (this term)</td><td>{{ $summary['discipline_total'] ?? 0 }}</td></tr>
            <tr><td>Complaints (this term)</td><td>{{ $summary['complaints_total'] ?? 0 }}</td></tr>
            <tr><td>Guidance cases (this term)</td><td>{{ $summary['guidance_cases_total'] ?? 0 }}</td></tr>
            <tr><td>Events (this term)</td><td>{{ $summary['events_total'] ?? 0 }}</td></tr>
            <tr><td>Active organizations</td><td>{{ $summary['active_organizations'] ?? 0 }}</td></tr>
            <tr><td>Pending candidacies</td><td>{{ $summary['pending_candidacies'] ?? 0 }}</td></tr>
        </tbody>
    </table>
    <p class="footer">Generated on {{ $summary['generated_at'] ?? now()->format('M j, Y g:i A') }}</p>
</body>
</html>
