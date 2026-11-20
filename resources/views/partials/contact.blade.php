<div class="contact">
    <span class="ct">Contactez l’EVC</span>
    @foreach (config('evc.contact_emails') as $email)
        @if (! $loop->first)<span class="sep">/</span>@endif
        <a href="mailto:{{ $email }}">{{ $email }}</a>
    @endforeach
</div>
