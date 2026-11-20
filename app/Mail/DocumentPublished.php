<?php

namespace App\Mail;

use App\Models\OfficialDocument;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/** Mail de félicitations envoyé au titulaire lorsque son document est publié au registre. */
class DocumentPublished extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public OfficialDocument $document)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Félicitations — votre '.$this->document->type->label.' est officiellement délivré');
    }

    public function content(): Content
    {
        return new Content(view: 'emails.published', with: [
            'url' => route('public.show', $this->document->reference),
        ]);
    }
}
