<!DOCTYPE html>
<html>
<head>
    <title>Invoices</title>

    <style>
        body {
            font-family: DejaVu Sans, Arial, sans-serif;
            font-size: 12px;
            color: #111827;
        }

        h2 {
            margin-bottom: 12px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th {
            background: #f3f4f6;
            font-weight: bold;
            text-align: left;
        }

        th,
        td {
            border: 1px solid #d1d5db;
            padding: 6px;
            vertical-align: top;
        }
    </style>
</head>

<body>
    <h2>Invoices</h2>

    <table>
        <thead>
            <tr>
                <th>Client</th>
                <th>Job</th>
                <th>Amount</th>
                <th>Status</th>
                <th>Due Date</th>
            </tr>
        </thead>

        <tbody>
            @foreach($invoices as $invoice)
                <tr>
                    <td>{{ $invoice->client->name ?? 'N/A' }}</td>
                    <td>{{ $invoice->job->description ?? 'N/A' }}</td>
                    <td>{{ number_format((float) ($invoice->amount ?? 0), 2) }}</td>
                    <td>{{ ucfirst($invoice->status ?? 'pending') }}</td>
                    <td>{{ $invoice->due_date ?? '—' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>