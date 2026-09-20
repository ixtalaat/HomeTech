<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Log in</title>
</head>

<body>
    <h1>Log in</h1>
    @if ($errors->any())
        <ul>@foreach ($errors->all() as $error)
        <li>{{ $error }}</li>@endforeach
    </ul>@endif
    <form method="POST" action="{{ route('login.store') }}">
        @csrf
        <label>Email <input type="email" name="email" value="{{ old('email') }}" required></label>
        <label>Password <input type="password" name="password" required></label>
        <label><input type="checkbox" name="remember"> Remember me</label>
        <button type="submit">Log in</button>
    </form>
    <a href="{{ route('register') }}">Register</a>
</body>

</html>
