@extends('layouts.app')
@section('title', 'Trop de tentatives')
@section('robots', 'noindex')
@section('content')
    <h1>Trop de tentatives</h1>
    <p class="lead">Par sécurité, le nombre de consultations est limité. Patientez quelques minutes puis réessayez.</p>
    <a class="btn" href="{{ route('public.index') }}">Retour</a>
@endsection
