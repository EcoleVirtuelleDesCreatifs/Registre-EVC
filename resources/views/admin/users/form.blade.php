@extends('layouts.admin')
@section('title', $user->exists ? 'Modifier l’utilisateur' : 'Nouvel utilisateur')
@section('admin')
    <h1>{{ $user->exists ? 'Modifier : '.$user->name : 'Nouvel utilisateur' }}</h1>
    <form class="card form" method="POST" action="{{ $user->exists ? route('admin.utilisateurs.update', $user) : route('admin.utilisateurs.store') }}" style="max-width:720px">
        @csrf
        @if ($user->exists) @method('PUT') @endif
        <label>Nom <input name="name" value="{{ old('name', $user->name) }}" required></label>
        <label>E-mail
            @if ($user->exists)<input value="{{ $user->email }}" disabled>@else<input type="email" name="email" value="{{ old('email') }}" required>@endif
        </label>
        @error('email')<div class="err-text full">{{ $message }}</div>@enderror
        <label>Rôle
            <select name="role" required>
                @foreach (config('evc.roles') as $code => $label)<option value="{{ $code }}" @selected(old('role', $user->role) === $code)>{{ $label }}</option>@endforeach
            </select>
        </label>
        @if ($user->exists)
            <label class="check" style="align-self:end"><input type="hidden" name="is_active" value="0"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $user->is_active))> Compte actif</label>
        @endif
        @error('role')<div class="err-text full">{{ $message }}</div>@enderror
        <label>{{ $user->exists ? 'Nouveau mot de passe (facultatif)' : 'Mot de passe' }} <input type="password" name="password" autocomplete="new-password" @required(! $user->exists)></label>
        <label>Confirmation <input type="password" name="password_confirmation" autocomplete="new-password" @required(! $user->exists)></label>
        @error('password')<div class="err-text full">{{ $message }}</div>@enderror
        <span class="fileinfo full">12 caractères minimum, avec majuscules, minuscules et chiffres.</span>
        @if ($user->exists && $user->hasTwoFactor())
            <label class="check full"><input type="checkbox" name="reset_2fa" value="1"> Réinitialiser la double authentification (téléphone perdu)</label>
        @endif
        <div class="full row"><button class="btn" type="submit">Enregistrer</button><a class="btn ghost" href="{{ route('admin.utilisateurs.index') }}">Annuler</a></div>
    </form>
@endsection
