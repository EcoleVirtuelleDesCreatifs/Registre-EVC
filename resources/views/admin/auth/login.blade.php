@extends('layouts.admin')
@section('title', 'Connexion')
@section('admin')
    <div class="login">
        <h1>Connexion</h1>
        <p class="lead">Espace réservé aux personnes habilitées de l’EVC.</p>
        <form class="card form" method="POST" action="{{ route('admin.login.attempt') }}" style="grid-template-columns:1fr">
            @csrf
            <label>Adresse e-mail <input type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username"></label>
            @error('email')<div class="err-text">{{ $message }}</div>@enderror
            <label>Mot de passe <input type="password" name="password" required autocomplete="current-password"></label>
            <label class="check"><input type="checkbox" name="remember" value="1"> Rester connecté sur cet appareil</label>
            <button class="btn" type="submit">Se connecter</button>
        </form>
    </div>
@endsection
