<form method="POST" action="/register">
    @csrf
    <input type="hidden" name="token" value="{{ $token }}">
    <input type="hidden" name="email" value="{{ $email }}">
    <label>Name</label>
    <input type="text" name="name" required>
    <label>Password</label>
    <input type="password" name="password" required>
    <label>Confirm Password</label>
    <input type="password" name="password_confirmation" required>
    <button type="submit">Create Account</button>
</form>