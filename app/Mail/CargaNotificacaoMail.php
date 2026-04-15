<?php

namespace App\Mail;

use App\Models\Carga;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CargaNotificacaoMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Carga $carga,
        public bool $modoTeste,
    ) {}

    public function envelope(): Envelope
    {
        $id = $this->carga->rfq_id ?: $this->carga->id;

        return new Envelope(
            subject: $this->modoTeste
                ? "[Modo teste] Carga {$id} não foi capturada no SAP"
                : "Carga capturada: {$id}",
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.carga-notificacao',
        );
    }
}
