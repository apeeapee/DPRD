@extends('layouts.guest')

@section('title', 'Login')

@section('content')
  <div class="card">
    <div style="font-weight:900; font-size:18px;">Login</div>
    <div class="muted" style="margin-top:4px; margin-bottom:14px;">Masuk sebagai admin atau user</div>

    <form method="POST" action="{{ route('login.submit') }}" style="display:grid; gap:12px;">
      @csrf

      <div>
        <label for="email">Email</label>
        <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus>
        @error('email')
          <div class="err">{{ $message }}</div>
        @enderror
      </div>

      <div>
        <label for="password">Password</label>
        <input id="password" name="password" type="password" required>
        @error('password')
          <div class="err">{{ $message }}</div>
        @enderror
      </div>

      @if ($errors->has('email'))
        <div class="err">{{ $errors->first('email') }}</div>
      @endif

      <button type="submit">Masuk</button>

      <div class="muted">
        Admin akan diarahkan ke dashboard admin, user ke dashboard user.
      </div>
    </form>
  </div>
@endsection
