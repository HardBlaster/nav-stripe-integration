<?php
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Address;

function sendInvoiceEmail(array $invoiceData, string $pdfPath): bool {
    $buyerEmail = $invoiceData['buyer']['email'] ?? null;

    if (!$buyerEmail) {
        file_put_contents(__DIR__ . '/../logs/email.log',
            date('c') . " - Skipped email: buyer email missing\n", FILE_APPEND);
        return false;
    }

    try {
        // 1️⃣ SMTP kapcsolat
        $transport = Transport::fromDsn($_ENV['MAILER_DSN']);
        $mailer = new Mailer($transport);

        // 2️⃣ E-mail összeállítása
        $subject = "Számla #" . $invoiceData['invoiceNumber'];
        $body = <<<EOT
Kedves {$invoiceData['buyer']['name']}!

Köszönjük a vásárlást. Csatoltan küldjük a számlát a {$invoiceData['invoiceNumber']} számú tranzakcióról.

Üdvözlettel:
{$invoiceData['seller']['name']}
EOT;

        $email = (new Email())
            ->from(new Address($_ENV['MAIL_FROM_ADDRESS'], $_ENV['MAIL_FROM_NAME']))
            ->to($buyerEmail)
            ->replyTo($_ENV['MAIL_REPLY_TO'])
            ->subject($subject)
            ->text($body)
            ->attachFromPath($pdfPath, basename($pdfPath), 'application/pdf');

        // 3️⃣ Küldés
        $mailer->send($email);

        file_put_contents(__DIR__ . '/../logs/email.log',
            date('c') . " - Email sent to {$buyerEmail}, invoice {$invoiceData['invoiceNumber']}\n", FILE_APPEND);

        return true;

    } catch (Throwable $e) {
        file_put_contents(__DIR__ . '/../logs/email.log',
            date('c') . " - Email send error: {$e->getMessage()}\n", FILE_APPEND);
        return false;
    }
}
