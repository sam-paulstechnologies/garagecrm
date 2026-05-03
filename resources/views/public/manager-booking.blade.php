<!DOCTYPE html>
<html>
<head>
    <title>Book Slot</title>
</head>
<body>
    <h2>Booking for {{ $opportunity->client->name }}</h2>

    <form method="POST">
        @csrf

        <label>Date</label><br>
        <input type="date" name="booking_date" required><br><br>

        <label>Slot</label><br>
        <select name="slot" required>
            <option value="morning">Morning</option>
            <option value="evening">Evening</option>
        </select><br><br>

        <button type="submit">Confirm Booking</button>
    </form>
</body>
</html>
