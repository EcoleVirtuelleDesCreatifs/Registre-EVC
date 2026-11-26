@extends('layouts.app')

@section('robots', 'noindex, nofollow')
@section('brand_title', 'Administration du registre')
@section('brand_sub', 'École Virtuelle des Créatifs')

@section('nav')
    @auth
        <a href="{{ route('public.index') }}" target="_blank" rel="noopener">Registre public ↗</a>
        <form method="POST" action="{{ route('admin.logout') }}" style="margin:0">@csrf<button type="submit">Déconnexion</button></form>
    @endauth
@endsection

@section('layout_raw')
    @auth
        @php
            $u = auth()->user();
            $pending = \App\Models\OfficialDocument::query()
                ->when($u->hasPermission('documents.control') && ! $u->hasPermission('documents.approve'),
                    fn ($q) => $q->where('status', 'en_verification')->whereNull('pedagogical_validated_at'))
                ->when($u->hasPermission('documents.approve'),
                    fn ($q) => $q->where(fn ($w) => $w->where(fn ($x) => $x->where('status', 'en_verification')->whereNotNull('pedagogical_validated_at'))->orWhere('status', 'approuve')))
                ->when(! $u->hasPermission('documents.control') && ! $u->hasPermission('documents.approve'), fn ($q) => $q->where('status', 'rejete'))
                ->count();
            $nav = [
                ['Tableau de bord', 'admin.dashboard', 'stats.view', 'admin.dashboard'],
                ['Nouveau document', 'admin.documents.create', 'documents.create', 'admin.documents.create'],
                ['Registre des documents', 'admin.documents.index', 'documents.view', 'admin.documents.index|admin.documents.show|admin.documents.edit'],
                ['Bénéficiaires', 'admin.beneficiaires.index', 'documents.view', 'admin.beneficiaires.*'],
                ['Étudiants par document', 'admin.par-document.index', 'documents.view', 'admin.par-document.*'],
                ['Validations', 'admin.validations.index', 'documents.view', 'admin.validations.*'],
                ['Import Excel / CSV', 'admin.imports.create', 'imports.drafts', 'admin.imports.*'],
            ];
            $navAdmin = [
                ['Historique', 'admin.audit.index', 'audit.view', 'admin.audit.*'],
                ['Rôles et utilisateurs', 'admin.utilisateurs.index', 'users.manage', 'admin.utilisateurs.*'],
                ['Sauvegardes', 'admin.backups.index', 'backups.manage', 'admin.backups.*'],
            ];
        @endphp
        <div class="adm">
            <aside class="side">
                <div class="grp">Registre</div>
                @foreach ($nav as [$label, $route, $perm, $active])
                    @can($perm)
                        <a href="{{ route($route) }}" class="{{ request()->routeIs(...explode('|', $active)) ? 'on' : '' }}">
                            {{ $label }}
                            @if ($route === 'admin.validations.index' && $pending)<span class="count">{{ $pending }}</span>@endif
                        </a>
                    @endcan
                @endforeach
                @if (collect($navAdmin)->contains(fn ($n) => $u->hasPermission($n[2])))
                    <div class="grp">Gouvernance</div>
                    @foreach ($navAdmin as [$label, $route, $perm, $active])
                        @can($perm)<a href="{{ route($route) }}" class="{{ request()->routeIs($active) ? 'on' : '' }}">{{ $label }}</a>@endcan
                    @endforeach
                @endif
                <div class="grp">Mon compte</div>
                <a href="{{ route('admin.2fa.setup') }}" class="{{ request()->routeIs('admin.2fa.setup') ? 'on' : '' }}">Double authentification</a>
                <a href="{{ route('admin.2fa.password') }}" class="{{ request()->routeIs('admin.2fa.password') ? 'on' : '' }}">Mot de passe</a>
                <div class="who">{{ $u->name }}<br>{{ $u->roleLabel() }}</div>
            </aside>
            <main>
                @include('partials.alerts')
                @yield('admin')
            </main>
        </div>
    @else
        <main>
            @include('partials.alerts')
            @yield('admin')
        </main>
    @endauth
@endsection
