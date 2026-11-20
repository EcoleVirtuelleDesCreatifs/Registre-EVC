@extends('layouts.app')
@section('title', 'Accès refusé')
@section('robots', 'noindex')
@section('content')
    <h1>Accès refusé</h1>
    <p class="lead">{{ $exception->getMessage() ?: 'Votre rôle ne permet pas cette action.' }}</p>
    <a class="btn" href="{{ url()->previous() }}">Retour</a>
@endsection
