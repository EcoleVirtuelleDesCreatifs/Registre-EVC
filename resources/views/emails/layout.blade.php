<!DOCTYPE html>
<html lang="fr">
<body style="margin:0;padding:0;background:#f4f1ea;font-family:Georgia,'Times New Roman',serif;color:#2a3052">
    <div style="max-width:600px;margin:0 auto;padding:24px 12px">

        {{-- En-tête --}}
        <div style="background:#0f1d3d;border-radius:10px 10px 0 0;padding:22px 32px;text-align:center">
            <img src="{{ $message->embed(public_path('images/logo-evc-blanc.png')) }}" alt="École Virtuelle des Créatifs" style="height:46px">
            <div style="color:#c9a24a;font-size:11px;letter-spacing:2.5px;text-transform:uppercase;margin-top:8px">Registre officiel des documents</div>
        </div>

        {{-- Corps --}}
        <div style="background:#fffdf6;padding:32px;border-left:1px solid #e7ddc8;border-right:1px solid #e7ddc8">
            <h1 style="margin:0 0 18px;font-size:21px;color:#0f1d3d;font-weight:bold">@yield('title')</h1>
            @yield('content')
            @hasSection('action')
                <div style="text-align:center;margin:28px 0 8px">
                    <a href="@yield('action-url')" style="display:inline-block;background:#0f1d3d;color:#fff;font-family:Arial,sans-serif;font-size:15px;font-weight:bold;padding:13px 34px;text-decoration:none;border-radius:6px">@yield('action')</a>
                </div>
            @endif
        </div>

        {{-- Pied de page --}}
        <div style="background:#efe8d8;border-radius:0 0 10px 10px;padding:18px 32px;text-align:center;font-family:Arial,sans-serif;font-size:11px;color:#7a7258;line-height:1.7">
            École Virtuelle des Créatifs · Bloom Square Akwaba, Palmeraie, Abidjan<br>
            +225 07 47 25 95 07 · info@ecolevirtuelledescreatifs.com<br>
            <span style="color:#a89d7f">Cet e-mail est envoyé automatiquement par le registre officiel EVC, merci de ne pas y répondre.</span>
        </div>
    </div>
</body>
</html>
