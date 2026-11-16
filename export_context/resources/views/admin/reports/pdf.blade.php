<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Master Evaluation Audit</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #1f2937; }
        .header { border-bottom: 2px solid #2563eb; padding-bottom: 10px; margin-bottom: 16px; }
        .title { font-size: 18px; font-weight: bold; color: #1d4ed8; }
        .subtitle { font-size: 11px; color: #6b7280; margin-top: 4px; }
        table { width: 100%; border-collapse: collapse; margin-top: 12px; }
        th, td { border: 1px solid #d1d5db; padding: 6px; text-align: left; font-size: 10px; }
        th { background: #eff6ff; color: #1d4ed8; }
        .footer { margin-top: 24px; font-size: 10px; color: #6b7280; border-top: 1px solid #e5e7eb; padding-top: 8px; }
    </style>
</head>
<body>
    <div class="header">
        <div class="title">UITM Smart E-Logbook System</div>
        <div class="subtitle">Master Evaluation Audit Report</div>
    </div>

    <p><strong>Generated on:</strong> {{ now()->format('d F Y H:i') }}</p>

    @foreach($students as $student)
        <h3>{{ $student->name }} ({{ $student->matric_no }})</h3>
        <p><strong>Lecturer:</strong> {{ optional($student->lecturer)->name ?? 'Unassigned' }}</p>
        <p><strong>Course:</strong> {{ $student->course }}</p>
        <p><strong>Logbook entries:</strong> {{ $student->logbooks->count() }}</p>

        <table>
            <thead>
                <tr>
                    <th>Week</th>
                    <th>Activity Date</th>
                    <th>Status</th>
                    <th>Summary</th>
                </tr>
            </thead>
            <tbody>
                @foreach($student->logbooks as $logbook)
                    <tr>
                        <td>{{ $logbook->week_no }}</td>
                        <td>{{ $logbook->activity_date }}</td>
                        <td>{{ $logbook->status }}</td>
                        <td>{{ Str::limit($logbook->description, 80) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        <br>
    @endforeach

    <div class="footer">
        <div>Prepared for university administration review</div>
        <div>Page 1 of 1</div>
    </div>
</body>
</html>
