@extends('layouts.admin')
@section('title', 'Code de sécurité')
@section('admin')
    <div class="login">
        <h1>Code de sécurité</h1>
        <p class="lead">Saisissez le code à 6 chiffres affiché par votre application d’authentification.</p>
        <form class="card form" method="POST" action="{{ route('admin.2fa.verify') }}" style="grid-template-columns:1fr">
            @csrf
            <label>Code <input name="code" inputmode="numeric" autocomplete="one-time-code" maxlength="7" required autofocus style="font-size:22px;letter-spacing:6px;text-align:center"></label>
            @error('code')<div class="err-text">{{ $message }}</div>@enderror
            <button class="btn" type="submit">Valider</button>
        </form>
        <p class="small">Téléphone perdu ? Un administrateur peut réinitialiser votre double authentification.</p>
    </div>
@endsection
