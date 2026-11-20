@extends('layouts.admin')
@section('title', 'Double authentification')
@section('admin')
    <h1>Double authentification</h1>
    @if ($enabled)
        <div class="card">
            <p>✓ La double authentification est <strong>activée</strong> sur votre compte. Un code vous est demandé à chaque connexion.</p>
            @if (! auth()->user()->mustUseTwoFactor())
                <form method="POST" action="{{ route('admin.2fa.disable') }}" class="row" style="margin-top:12px">
                    @csrf @method('DELETE')
                    <input class="f" type="password" name="password" placeholder="Mot de passe actuel" required style="max-width:260px">
                    <button class="btn danger" type="submit">Désactiver</button>
                </form>
                @error('password')<div class="err-text">{{ $message }}</div>@enderror
            @else
                <p class="small">Votre rôle ({{ auth()->user()->roleLabel() }}) exige la double authentification.</p>
            @endif
        </div>
    @else
        <p class="lead">Protégez votre compte : en plus du mot de passe, un code à 6 chiffres généré par votre téléphone sera demandé à chaque connexion.</p>
        <div class="cols">
            <div class="card">
                <h2>1. Scannez ce QR code</h2>
                <p class="small" style="margin-top:0">Avec Google Authenticator, Microsoft Authenticator ou une application équivalente.</p>
                @include('partials.qr', ['text' => $uri, 'id' => 'qr-2fa', 'filename' => null])
                <p class="small">Ou saisissez cette clé : <code style="word-break:break-all">{{ trim(chunk_split($secret, 4, ' ')) }}</code></p>
            </div>
            <div class="card">
                <h2>2. Confirmez avec un code</h2>
                <form method="POST" action="{{ route('admin.2fa.enable') }}" class="form" style="grid-template-columns:1fr">
                    @csrf
                    <label>Code affiché par l’application <input name="code" inputmode="numeric" maxlength="7" required style="font-size:22px;letter-spacing:6px;text-align:center"></label>
                    @error('code')<div class="err-text">{{ $message }}</div>@enderror
                    <button class="btn gold" type="submit">Activer</button>
                </form>
            </div>
        </div>
    @endif
@endsection
