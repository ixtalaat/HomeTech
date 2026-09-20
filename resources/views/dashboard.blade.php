<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Dashboard</title>
</head>

<body>
    <h1>Dashboard</h1>
    <p>Welcome, {{ auth()->user()->name }}.</p>
    <a href="{{ route('profile.edit') }}">Profile</a>
    <form method="POST" action="{{ route('logout') }}">@csrf<button type="submit">Log out</button></form>
</body>

</html>
