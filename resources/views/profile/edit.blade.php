<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Profile</title>
</head>

<body>
    <h1>Profile</h1>
    @if (session('status'))
        <p>{{ session('status') }}</p>
    @endif
    @if ($errors->any())
        <ul>
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    @endif
    <form method="POST" action="{{ route('profile.update') }}">
        @csrf @method('PUT')
        <label>Name <input type="text" name="name" value="{{ old('name', $user->name) }}" required></label>
        <label>Phone <input type="text" name="phone" value="{{ old('phone', $user->phone) }}"></label>
        <label>Email <input type="email" name="email" value="{{ old('email', $user->email) }}" required></label>
        <button type="submit">Save profile</button>
    </form>
    <h2>Change password</h2>
    <form method="POST" action="{{ route('password.update') }}">
        @csrf @method('PUT')
        <label>Current password <input type="password" name="current_password" required></label>
        <label>New password <input type="password" name="password" required></label>
        <label>Confirm password <input type="password" name="password_confirmation" required></label>
        <button type="submit">Change password</button>
    </form>
</body>

</html>
