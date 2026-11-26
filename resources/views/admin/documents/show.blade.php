@extends('layouts.admin')
@section('title', $document->reference ?? 'Dossier n° '.$document->id)
@section('admin')
    @php
        $s = $document->status;
        $flow = [
            'brouillon' => 'Création',
            'controle' => 'Contrôle pédagogique',
            'approbation' => 'Approbation DG',
            'delivrance' => 'Délivrance (référence)',
            'publication' => 'Publication',
        ];
        $reached = match (true) {
            $s === 'publie' || $s === 'revoque' => 5,
            $s === 'approuve' && $document->reference => 4,
            $s === 'approuve' => 3,
            $s === 'en_verification' && $document->pedagogical_validated_at => 2,
            $s === 'en_verification' => 1,
            default => 0,
        };
    @endphp

    <div class="row" style="justify-content:space-between">
        <div>
            <h1 style="margin-bottom:4px">{{ $document->reference ?? 'Dossier n° '.$document->id }}</h1>
            <div class="row">@include('partials.status', ['doc' => $document])
                @if ($document->is_legacy)<span class="tag">Document historique</span>@endif
                <span class="tag">Origine : {{ $document->source }}</span>
            </div>
        </div>
        <div class="row">
            @can('update', $document)<a class="btn ghost" href="{{ route('admin.documents.edit', $document) }}">Modifier</a>@endcan
            @if ($document->isPublic())<a class="btn ghost" target="_blank" rel="noopener" href="{{ route('public.show', $document->reference) }}">Voir la page publique ↗</a>@endif
        </div>
    </div>

    <div class="steps">
        @foreach (array_values($flow) as $i => $label)
            <span class="{{ $s === 'rejete' && $i === $reached ? 'cur' : ($i < $reached || $reached === 5 ? 'done' : ($i === $reached ? 'cur' : '')) }}">{{ $i + 1 }}. {{ $label }}</span>
        @endforeach
    </div>

    @if ($s === 'rejete')
        <div class="alert err">Ce dossier a été rejeté. Corrigez-le puis soumettez-le de nouveau au contrôle.</div>
    @endif
    @if ($s === 'revoque' && $document->revocation)
        <div class="alert err"><strong>Document révoqué</strong> le {{ $document->revocation->revoked_at?->format('d/m/Y') }} par {{ $document->revocation->user?->name }}.
            Motif : {{ $document->revocation->reason }}@if ($document->revocation->decision_reference) — Décision {{ $document->revocation->decision_reference }}@endif</div>
    @endif

    <div class="cols">
        <div>
            <div class="card">
                <h2>Informations</h2>
                <div class="kv">
                    <span class="k">Titulaire</span><span class="v"><a href="{{ route('admin.beneficiaires.show', $document->holder) }}">{{ $document->holder?->full_name }}</a></span>
                    <span class="k">Document</span><span class="v">{{ $document->type?->label }}</span>
                    <span class="k">Formation</span><span class="v">{{ $document->formationLabel() ?? '—' }}</span>
                    <span class="k">Promotion</span><span class="v">{{ $document->promotion ?? '—' }}</span>
                    <span class="k">Référence</span><span class="v">{{ $document->reference ?? 'Attribuée à la délivrance' }}</span>
                    <span class="k">Date de délivrance</span><span class="v">{{ $document->issuedFr() }}</span>
                    <span class="k">PDF public</span><span class="v">{{ $document->pdf_public ? 'Oui, après vérification' : 'Non (statut seul)' }}
                        @can('togglePublic', $document)
                            <form method="POST" action="{{ route('admin.documents.consultation', $document) }}" style="display:inline;margin:0">
                                @csrf @method('PATCH')
                                <button class="btn {{ $document->pdf_public ? 'ghost' : 'danger' }}" type="submit" style="padding:2px 10px;font-size:12px">{{ $document->pdf_public ? 'Désactiver' : 'Activer' }}</button>
                            </form>
                        @endcan
                    </span>
                    <span class="k">Créé par</span><span class="v">{{ $document->creator?->name ?? '—' }}, le {{ $document->created_at?->format('d/m/Y') }}</span>
                    <span class="k">Vérifications publiques</span><span class="v">{{ $verifications }}</span>
                    @if ($document->internal_note)<span class="k">Note interne</span><span class="v">{{ $document->internal_note }}</span>@endif
                </div>
            </div>

            {{-- ===== Actions du workflow ===== --}}
            @error('workflow')<div class="alert err" style="margin-top:16px">{{ $message }}</div>@enderror

            @can('submit', $document)
                <form class="action" method="POST" action="{{ route('admin.documents.submit', $document) }}" style="margin-top:16px">
                    @csrf
                    <h3>Soumettre au contrôle pédagogique</h3>
                    <p class="small" style="margin-top:0">Le dossier ne pourra plus être modifié pendant la vérification.</p>
                    <textarea class="f" name="comment" rows="2" placeholder="Commentaire (facultatif)"></textarea>
                    <div class="acts"><button class="btn" type="submit">Soumettre</button></div>
                </form>
            @endcan

            @can('control', $document)
                <form class="action" method="POST" action="{{ route('admin.documents.control', $document) }}" style="margin-top:16px">
                    @csrf
                    <h3>Contrôle pédagogique</h3>
                    <p class="small" style="margin-top:0">Cochez chaque critère vérifié. Tous les critères sont requis pour confirmer l’éligibilité.</p>
                    <div class="checks">
                        @foreach ($document->type->criteria() as $key => $label)
                            <label class="check"><input type="checkbox" name="criteres[]" value="{{ $key }}"> {{ $label }}</label>
                        @endforeach
                    </div>
                    <textarea class="f" name="comment" rows="2" placeholder="Observations (obligatoires en cas de rejet)"></textarea>
                    <div class="acts">
                        <button class="btn" name="decision" value="approuve" type="submit">Confirmer l’éligibilité</button>
                        <button class="btn danger" name="decision" value="rejete" type="submit">Rejeter le dossier</button>
                    </div>
                </form>
            @endcan

            @can('approve', $document)
                <form class="action" method="POST" action="{{ route('admin.documents.approve', $document) }}" style="margin-top:16px">
                    @csrf
                    <h3>Approbation de la Direction générale</h3>
                    <p class="small" style="margin-top:0">Éligibilité confirmée par la Direction pédagogique le {{ $document->pedagogical_validated_at?->format('d/m/Y à H:i') }}.</p>
                    <textarea class="f" name="comment" rows="2" placeholder="Commentaire (obligatoire en cas de refus)"></textarea>
                    <div class="acts">
                        <button class="btn gold" name="decision" value="approuve" type="submit">Autoriser la délivrance</button>
                        <button class="btn danger" name="decision" value="rejete" type="submit">Refuser</button>
                    </div>
                </form>
            @endcan

            @can('deliver', $document)
                <form class="action" method="POST" action="{{ route('admin.documents.deliver', $document) }}" style="margin-top:16px">
                    @csrf
                    <h3>Délivrance — attribution de la référence officielle</h3>
                    <p class="small" style="margin-top:0">La référence est attribuée définitivement et le PDF officiel est généré aussitôt, avec la référence et le QR code imprimés. Il ne reste qu’à l’imprimer et à le faire signer.</p>
                    <label>Date de délivrance <input class="f" type="date" name="issued_at" value="{{ now()->toDateString() }}" max="{{ now()->toDateString() }}" required style="max-width:220px"></label>
                    @error('issued_at')<div class="err-text">{{ $message }}</div>@enderror
                    <div class="acts"><button class="btn gold" type="submit" onclick="return confirm('Attribuer définitivement une référence officielle ?')">Délivrer et attribuer la référence</button></div>
                </form>
            @endcan

            @can('publish', $document)
                <form class="action" method="POST" action="{{ route('admin.documents.publish', $document) }}" style="margin-top:16px">
                    @csrf
                    <h3>Publication au registre public</h3>
                    @if ($document->pdf_path)
                        <p class="small" style="margin-top:0">Le document deviendra vérifiable publiquement par sa référence {{ $document->reference }} et par QR code.</p>
                        <div class="acts"><button class="btn gold" type="submit">Publier</button></div>
                    @else
                        <p class="warn-text">Le PDF officiel est manquant : générez-le (panneau « Document officiel ») ou joignez la version signée.</p>
                    @endif
                </form>
            @endcan

            @can('revoke', $document)
                <details class="action" style="margin-top:16px;border-color:#f0c9c5">
                    <summary style="cursor:pointer;font-weight:600;color:var(--bad)">Révoquer ce document</summary>
                    <form method="POST" action="{{ route('admin.documents.revoke', $document) }}" style="margin-top:12px">
                        @csrf
                        <p class="small" style="margin-top:0">La révocation est définitive et visible publiquement. La référence restera dans le registre avec le statut « révoqué ».</p>
                        <textarea class="f" name="reason" rows="3" required minlength="10" placeholder="Motif de la révocation (10 caractères minimum)">{{ old('reason') }}</textarea>
                        @error('reason')<div class="err-text">{{ $message }}</div>@enderror
                        <input class="f" name="decision_reference" value="{{ old('decision_reference') }}" placeholder="Référence de la décision (facultatif)" style="margin-top:8px">
                        <label class="check" style="margin-top:8px"><input type="checkbox" name="confirm" value="1" required> Je confirme la révocation définitive.</label>
                        <div class="acts"><button class="btn danger" type="submit">Révoquer</button></div>
                    </form>
                </details>
            @endcan

            @can('delete', $document)
                @php $inCircuit = in_array($document->status, ['en_verification', 'approuve'], true); @endphp
                <form method="POST" action="{{ route('admin.documents.destroy', $document) }}" style="margin-top:16px" onsubmit="return confirm('Supprimer ce dossier ? L’opération est enregistrée dans l’historique.')">
                    @csrf @method('DELETE')
                    @if ($inCircuit)
                        <label class="small" style="display:block;margin-bottom:6px">Motif de la suppression (obligatoire)
                            <textarea class="f" name="motif" rows="2" required maxlength="500" style="width:100%;margin-top:4px"></textarea>
                        </label>
                        @error('motif')<div class="err-text">{{ $message }}</div>@enderror
                    @endif
                    <button class="btn danger" type="submit">Supprimer {{ $document->status === 'brouillon' ? 'le brouillon' : 'le dossier' }}</button>
                </form>
            @endcan

            <div class="card" style="margin-top:16px">
                <h2>Historique du dossier</h2>
                <ul class="tl">
                    @foreach ($document->approvals->sortByDesc('created_at') as $a)
                        <li><strong>{{ $a->stepLabel() }}</strong> — {{ ['approuve' => 'favorable', 'rejete' => 'défavorable', 'effectue' => 'effectuée', 'revoque' => 'révoqué'][$a->decision] ?? $a->decision }}
                            <div class="when">{{ $a->created_at?->format('d/m/Y H:i') }} · {{ $a->user?->name }}</div>
                            @if ($a->comment)<div class="small" style="margin-top:2px">« {{ $a->comment }} »</div>@endif
                        </li>
                    @endforeach
                    @foreach ($logs as $log)
                        <li class="muted">{{ $log->description ?? $log->action }}<div class="when">{{ $log->created_at?->format('d/m/Y H:i') }} · {{ $log->user?->name ?? 'Système' }}</div></li>
                    @endforeach
                </ul>
            </div>
        </div>

        <div>
            <div class="card" style="margin-bottom:16px">
                <h2>Document officiel</h2>
                @if ($document->is_legacy)
                    <p class="fileinfo" style="margin-top:0">Document historique : son PDF d’origine fait foi. Il n’est pas regénéré.</p>
                @else
                    @php $land = $document->type->code === 'certificat'; $k = 0.34; @endphp
                    <div style="width:{{ round(($land ? 1123 : 794) * $k) }}px;height:{{ round(($land ? 794 : 1123) * $k) }}px;overflow:hidden;border:1px solid var(--line);border-radius:6px;margin:0 auto">
                        <iframe src="{{ route('admin.documents.render', [$document, 'embed' => 1]) }}" title="Aperçu du document" scrolling="no"
                            style="border:0;width:{{ $land ? 1123 : 794 }}px;height:{{ $land ? 794 : 1123 }}px;transform:scale({{ $k }});transform-origin:0 0"></iframe>
                    </div>
                    <p class="fileinfo">{{ $document->reference ? ($document->generated_at ? 'PDF officiel généré le '.$document->generated_at->format('d/m/Y à H:i').' (gabarit '.$document->template_version.').' : 'PDF officiel non encore généré.') : 'Aperçu de travail (spécimen) : la référence et le QR code seront imprimés à la délivrance.' }}</p>
                    <div class="acts" style="margin-top:6px">
                        <a class="btn ghost" target="_blank" rel="noopener" href="{{ route('admin.documents.render', $document) }}">Aperçu plein écran</a>
                        <a class="btn ghost" target="_blank" rel="noopener" href="{{ $document->generated_pdf_path ? route('admin.documents.file', [$document, 'pdf']) : route('admin.documents.render', [$document, 'format' => 'pdf']) }}">{{ $document->generated_pdf_path ? 'PDF officiel' : 'PDF spécimen' }}</a>
                        @can('generate', $document)
                            <form method="POST" action="{{ route('admin.documents.generate', $document) }}">@csrf<button class="btn ghost" type="submit">{{ $document->generated_pdf_path ? 'Regénérer le PDF' : 'Générer le PDF' }}</button></form>
                        @endcan
                    </div>
                @endif
                @if ($document->pdf_sha256)
                    <p class="fileinfo" style="word-break:break-all;margin-bottom:0">Empreinte SHA-256 du PDF {{ $document->status === 'publie' ? 'publié' : 'officiel' }} :<br><code>{{ $document->pdf_sha256 }}</code></p>
                @endif
            </div>
            <div class="card">
                <h2>Fichiers</h2>
                <div class="kv">
                    <span class="k">PDF officiel</span>
                    <span class="v">@if ($document->pdf_path)<a href="{{ route('admin.documents.file', [$document, 'pdf']) }}" target="_blank">Ouvrir le PDF</a>@else<span class="fileinfo none">Aucun</span>@endif</span>
                    <span class="k">Aperçu (image)</span>
                    <span class="v">@if ($document->preview_path)<a href="{{ route('admin.documents.file', [$document, 'apercu']) }}" target="_blank">Ouvrir l’aperçu</a>@else<span class="fileinfo">Aucun</span>@endif</span>
                </div>
                @can('attach', $document)
                    <form method="POST" action="{{ route('admin.documents.attach', $document) }}" enctype="multipart/form-data" class="form" style="grid-template-columns:1fr;margin-top:14px">
                        @csrf
                        <label>PDF signé (scan, facultatif) <input type="file" name="pdf" accept="application/pdf"></label>
                        <label>Aperçu JPG/PNG <input type="file" name="apercu" accept="image/jpeg,image/png,image/webp"></label>
                        @error('pdf')<div class="err-text">{{ $message }}</div>@enderror
                        @error('apercu')<div class="err-text">{{ $message }}</div>@enderror
                        <span class="fileinfo">Les versions précédentes sont conservées et tracées dans l’historique.</span>
                        <button class="btn ghost" type="submit">Téléverser</button>
                    </form>
                @endcan
            </div>

            @if ($document->reference && $document->public_token)
                <div class="card" style="margin-top:16px">
                    <h2>QR code de vérification</h2>
                    <p class="small" style="margin-top:0">À imprimer sur le document. Il pointe vers un lien unique et non devinable.</p>
                    @include('partials.qr', ['text' => route('public.qr', $document->public_token), 'id' => 'qr-doc', 'filename' => 'QR-'.$document->reference.'.png'])
                    <p class="fileinfo" style="word-break:break-all">{{ route('public.qr', $document->public_token) }}</p>
                </div>
            @endif
        </div>
    </div>
@endsection
