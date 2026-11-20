<?php

namespace App\Mail;

use App\Models\OfficialDocument;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/** Notification envoyée aux acteurs de l'étape suivante du workflow. */
class WorkflowNotification extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public OfficialDocument $document,
        public string $stepTitle,
        public string $details,
        public ?string $comment = null,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: '[Registre EVC] '.$this->stepTitle.' — '.($this->document->holder?->full_name ?? $this->document->reference ?? 'document'));
    }

    public function content(): Content
    {
        return new Content(view: 'emails.workflow', with: [
            'url' => route('admin.documents.show', $this->document),
        ]);
    }
}
