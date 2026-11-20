@extends('emails.layout')
@section('title', $stepTitle)
@section('action', 'Ouvrir le dossier')
@section('action-url', $url)
@section('content')
    <p style="font-size:15px;line-height:1.6;margin:0 0 18px">{{ $details }}</p>

    <table style="width:100%;border-collapse:collapse;background:#f6efe0;border-radius:8px;font-size:14px">
        <tr>
            <td style="padding:10px 16px;color:#7a7258;width:110px">Document</td>
            <td style="padding:10px 16px"><strong>{{ $document->type->label }} — {{ $document->holder?->full_name }}</strong></td>
        </tr>
        @if ($document->reference)
            <tr>
                <td style="padding:10px 16px;color:#7a7258;border-top:1px solid #e7ddc8">Référence</td>
                <td style="padding:10px 16px;border-top:1px solid #e7ddc8;font-family:monospace">{{ $document->reference }}</td>
            </tr>
        @endif
        <tr>
            <td style="padding:10px 16px;color:#7a7258;border-top:1px solid #e7ddc8">Statut</td>
            <td style="padding:10px 16px;border-top:1px solid #e7ddc8">{{ $document->statusLabel() }}</td>
        </tr>
    </table>

    @if ($comment)
        <div style="margin-top:18px;padding:12px 16px;border-left:3px solid #b8893a;background:#fbf7ec;font-style:italic;font-size:14px;line-height:1.5">{{ $comment }}</div>
    @endif

    <p style="font-size:13px;color:#7a7258;line-height:1.6;margin:22px 0 0">Connectez-vous à l'administration du registre pour traiter ce dossier.</p>
@endsection
