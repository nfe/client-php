<?php

declare(strict_types=1);

/**
 * Endpoint de webhook NFE.io minimalista.
 *
 * Como rodar:
 *     # 1) Configure NFE_WEBHOOK_SECRET em samples/.env (mesmo segredo do webhook na NFE.io)
 *     # 2) Sirva via PHP built-in server:
 *     php -S 0.0.0.0:8000 samples/webhook-verify.php
 *     # 3) Exponha publicamente (cloudflared/ngrok) e aponte o webhook da NFE.io para esta URL.
 *
 * O endpoint valida HMAC-SHA1 sobre X-Hub-Signature e responde 200 quando ok,
 * 403 quando a assinatura não confere.
 */

require_once __DIR__ . '/_bootstrap.php';

use Nfe\Exception\SignatureVerificationException;
use Nfe\Webhook;

$secret = getenv('NFE_WEBHOOK_SECRET') ?: throw new RuntimeException('NFE_WEBHOOK_SECRET ausente em samples/.env');
$payload = file_get_contents('php://input') ?: '';
$signature = $_SERVER['HTTP_X_HUB_SIGNATURE'] ?? '';

try {
    $event = Webhook::constructEvent(payload: $payload, sigHeader: $signature, secret: $secret);
} catch (SignatureVerificationException $e) {
    http_response_code(403);
    error_log("Webhook recusado: {$e->getMessage()}");
    echo json_encode(['ok' => false, 'reason' => 'invalid_signature']);
    exit;
}

// Eventos no padrão resource.event_action — a lista viva vem de
// $nfe->webhooks->fetchEventTypes() (ex.: service_invoice.*, product_invoice.*).
error_log("Webhook ok: type={$event->type}, id=" . ($event->id ?? '-'));

match ($event->type) {
    'service_invoice.issued_successfully'    => error_log('  -> nota emitida'),
    'service_invoice.cancelled_successfully' => error_log('  -> nota cancelada'),
    'service_invoice.issued_error'           => error_log('  -> nota com erro'),
    default                                  => error_log("  -> evento não tratado: {$event->type}"),
};

http_response_code(200);
echo json_encode(['ok' => true, 'type' => $event->type]);
