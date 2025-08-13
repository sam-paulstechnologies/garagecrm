<!DOCTYPE html>
<html>
<head>
    <title>Invoices</title>
</head>
<body>
    <h2>Invoices</h2>
    <table width="100%" border="1" cellspacing="0" cellpadding="4">
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
                    <td>{{ number_format($invoice->amount, 2) }}</td>
                    <td>{{ ucfirst($invoice->status) }}</td>
                    <td>{{ $invoice->due_date }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
