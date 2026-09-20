<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Register</title>
</head>

<body>
    <h1>Register</h1>
    @if ($errors->any())
        <ul>
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    @endif
    <form method="POST" action="{{ route('register.store') }}">
        @csrf
        <label>Name <input type="text" name="name" value="{{ old('name') }}" required></label>
        <label>Phone <input type="text" name="phone" value="{{ old('phone') }}"></label>
        <label>Email <input type="email" name="email" value="{{ old('email') }}" required></label>
        <label>Password <input type="password" name="password" required></label>
        <label>Confirm password <input type="password" name="password_confirmation" required></label>
        <button type="submit">Register</button>
    </form>
    <a href="{{ route('login') }}">Log in</a>
</body>

</html>
