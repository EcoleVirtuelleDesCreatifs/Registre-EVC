@extends('layouts.admin')
@section('title', $holder->exists ? 'Modifier le titulaire' : 'Nouveau titulaire')
@section('admin')
    <h1>{{ $holder->exists ? 'Modifier le titulaire' : 'Nouveau titulaire' }}</h1>
    <form class="card form" method="POST" action="{{ $holder->exists ? route('admin.beneficiaires.update', $holder) : route('admin.beneficiaires.store') }}">
        @csrf
        @if ($holder->exists) @method('PUT') @endif
        <label class="full">Nom complet (tel qu’il figure sur les documents) <input name="full_name" value="{{ old('full_name', $holder->full_name) }}" required></label>
        @error('full_name')<div class="err-text full">{{ $message }}</div>@enderror
        @if ($errors->has('full_name') && ! $holder->exists)
            <label class="check full"><input type="checkbox" name="confirm_duplicate" value="1"> Créer quand même (personne différente portant le même nom)</label>
        @endif
        <label>E-mail <input type="email" name="email" value="{{ old('email', $holder->email) }}"></label>
        <label>Téléphone <input name="phone" value="{{ old('phone', $holder->phone) }}"></label>
        <label>Date de naissance <input type="date" name="birth_date" value="{{ old('birth_date', $holder->birth_date?->toDateString()) }}"></label>
        <label>Promotion <input name="promotion" value="{{ old('promotion', $holder->promotion) }}"></label>
        <label>Matricule My EVC <input name="external_id" value="{{ old('external_id', $holder->external_id) }}"></label>
        @error('external_id')<div class="err-text full">{{ $message }}</div>@enderror
        <label class="full">Notes internes <textarea name="notes" rows="3">{{ old('notes', $holder->notes) }}</textarea></label>
        @if ($holder->exists)
            <label class="full">Justification (obligatoire si le nom change et que des documents sont publiés) <input name="justification" value="{{ old('justification') }}"></label>
            @error('justification')<div class="err-text full">{{ $message }}</div>@enderror
        @endif
        <p class="fileinfo full">Ces données personnelles ne sont jamais affichées sur le registre public, à l’exception du nom du titulaire.</p>
        <div class="full row"><button class="btn" type="submit">Enregistrer</button>
            <a class="btn ghost" href="{{ $holder->exists ? route('admin.beneficiaires.show', $holder) : route('admin.beneficiaires.index') }}">Annuler</a></div>
    </form>
@endsection
