<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Contact Form</title>
</head>
<body style="font-family:sans-serif">

<form method="POST"
      action="{{ url('/api/v1/forms/'.$source->form_token.'/submit') }}"
      style="max-width:400px">

    @csrf

    <h3>Contact Us</h3>

    <input name="name" placeholder="Your Name" required style="width:100%;margin-bottom:10px">

    <input name="phone" placeholder="Phone Number" required style="width:100%;margin-bottom:10px">

    <input name="email" placeholder="Email (optional)" style="width:100%;margin-bottom:10px">

    <button type="submit">Submit</button>
</form>

</body>
</html>
