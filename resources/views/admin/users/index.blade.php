@extends('layouts.admin')
@section('title', 'Rôles et utilisateurs')
@section('admin')
    <div class="row" style="justify-content:space-between">
        <h1>Rôles et utilisateurs</h1>
        <a class="btn gold" href="{{ route('admin.utilisateurs.create') }}">+ Nouvel utilisateur</a>
    </div>
    <div class="card" style="padding:0;overflow-x:auto">
        <table>
            <thead><tr><th>Nom</th><th>E-mail</th><th>Rôle</th><th>Double auth.</th><th>Dernière connexion</th><th>Statut</th><th></th></tr></thead>
            <tbody>
            @foreach ($users as $u)
                <tr>
                    <td><strong>{{ $u->name }}</strong></td>
                    <td>{{ $u->email }}</td>
                    <td>{{ $u->roleLabel() }}</td>
                    <td>{!! $u->hasTwoFactor() ? '<span style="color:var(--ok)">✓ activée</span>' : ($u->mustUseTwoFactor() ? '<span class="warn-text">requise, non configurée</span>' : '<span class="fileinfo">non</span>') !!}</td>
                    <td>{{ $u->last_login_at?->format('d/m/Y H:i') ?? '—' }}</td>
                    <td>{!! $u->is_active ? '<span class="pill publie">Actif</span>' : '<span class="pill revoque">Désactivé</span>' !!}</td>
                    <td><a class="btn ghost" href="{{ route('admin.utilisateurs.edit', $u) }}">Modifier</a></td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>

    <div class="card" style="margin-top:18px">
        <h2>Rôles et permissions</h2>
        @php $perms = config('evc.permissions'); @endphp
        <div style="overflow-x:auto">
            <table>
                <thead><tr><th>Permission</th>@foreach (config('evc.roles') as $label)<th style="font-size:12px">{{ $label }}</th>@endforeach</tr></thead>
                <tbody>
                @foreach ($perms as $perm => $roles)
                    <tr><td class="fileinfo">{{ $perm }}</td>
                        @foreach (array_keys(config('evc.roles')) as $role)<td style="text-align:center">{!! in_array($role, $roles, true) ? '<span style="color:var(--ok)">●</span>' : '<span class="fileinfo">·</span>' !!}</td>@endforeach
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endsection
