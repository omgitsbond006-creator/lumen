<?php
/**
 * Simulated mail transport.
 *
 * This demo platform has no SMTP server configured, so instead of sending
 * real email, outbound messages are logged to the `sent_emails` table.
 * The admin dashboard exposes an "Outbox" view so you can see exactly what
 * would have been sent (e.g. password reset links) — perfect for grading
 * without needing a live mail server.
 */
function send_mail(string $to, string $subject, string $body): void
{
    $stmt = db()->prepare('INSERT INTO sent_emails (to_email, subject, body) VALUES (?, ?, ?)');
    $stmt->execute([$to, $subject, $body]);
}
