<?php

namespace App\Notifications;

use App\Models\Transaction;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class TransactionNotification extends Notification
{
    use Queueable;

    protected Transaction $transaction;
    protected string $eventType;

    public function __construct(Transaction $transaction, string $eventType)
    {
        $this->transaction = $transaction;
        $this->eventType = $eventType;
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $t = $this->transaction;
        $status = $t->booking_status;
        $verb = $this->eventType === 'created' ? 'dibuat' : 'diupdate';

        $mail = (new MailMessage)
            ->subject("Transaksi {$verb}: {$t->booking_transaction_id}")
            ->greeting("Hai {$notifiable->name},")
            ->line("Transaksi kamu dengan ID {$t->booking_transaction_id} berhasil {$verb} dengan status *{$status}*.")
            ->line("Tanggal Sewa: {$t->start_date->format('d M Y')} s.d. {$t->end_date->format('d M Y')}")
            ->line("Total: Rp " . number_format($t->grand_total, 0, ',', '.'));

        if ($t->promo?->name) {
            $mail->line("Promo: {$t->promo->name}");
        }

        if ($t->note) {
            $mail->line("Catatan: {$t->note}");
        }

        return $mail->line('Terima kasih telah menggunakan layanan kami.');
    }
}
