@extends('layouts.admin')
@section('title', 'Mot de passe')
@section('admin')
    <h1>Changer mon mot de passe</h1>
    <form class="card form" method="POST" action="{{ route('admin.2fa.password.update') }}" style="max-width:520px;grid-template-columns:1fr">
        @csrf @method('PUT')
        <label>Mot de passe actuel <input type="password" name="current_password" required autocomplete="current-password"></label>
        @error('current_password')<div class="err-text">{{ $message }}</div>@enderror
        <label>Nouveau mot de passe <input type="password" name="password" required autocomplete="new-password"></label>
        <span class="fileinfo">12 caractères minimum, avec majuscules, minuscules et chiffres.</span>
        @error('password')<div class="err-text">{{ $message }}</div>@enderror
        <label>Confirmation <input type="password" name="password_confirmation" required autocomplete="new-password"></label>
        <button class="btn" type="submit">Enregistrer</button>
    </form>
@endsection
