@extends('layouts.app')
@section('title', 'Page introuvable')
@section('robots', 'noindex')
@section('content')
    <h1>Page introuvable</h1>
    <p class="lead">Ce document n’est pas disponible ou le lien est incorrect.</p>
    <a class="btn" href="{{ route('public.index') }}">Vérifier un document</a>
@endsection
