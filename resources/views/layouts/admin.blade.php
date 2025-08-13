<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Garage CRM — Admin</title>
    @viteReactRefresh
    @vite(['resources/css/app.css', 'resources/js/app.jsx'])
</head>
<body class="bg-gray-100">

    {{-- ✅ TEMP: Remove sidebar/nav while testing --}}
    {{-- @include('admin.navigation') --}}

    {{-- ✅ This is where content like react-root will go --}}
    @yield('content')

</body>
</html>
