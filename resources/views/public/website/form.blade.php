<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Contact Us</title>
</head>
<body>
<form method="POST" action="{{ $action }}">
    <input type="text" name="name" placeholder="Your name" required>
    <input type="email" name="email" placeholder="Email">
    <input type="text" name="phone" placeholder="Phone">
    <button type="submit">Submit</button>
</form>
</body>
</html>
