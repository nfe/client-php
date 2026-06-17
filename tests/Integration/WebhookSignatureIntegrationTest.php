<?php

declare(strict_types=1);

namespace Nfe\Tests\Integration;

use Nfe\Exception\SignatureVerificationException;
use Nfe\Webhook;

/**
 * Valida o helper de assinatura contra payloads gerados nós mesmos.
 *
 * Não chama API real (não precisa de NFE_SDK_E2E_API_KEY), mas vive aqui
 * porque o objetivo é exercitar end-to-end o caminho:
 * payload → HMAC-SHA1 → header → verify → WebhookEvent.
 */
final class WebhookSignatureIntegrationTest extends IntegrationTestCase
{
    private string $secret;

    protected function setUp(): void
    {
        parent::setUp();
        $this->secret = $this->requireEnv('NFE_SDK_E2E_WEBHOOK_SECRET');
    }

    public function test_constructEvent_aceita_payload_assinado_corretamente(): void
    {
        $payload = json_encode([
            'type' => 'invoice.issued',
            'data' => ['id' => 'inv-123', 'flowStatus' => 'Issued'],
            'id'   => 'evt-001',
            'createdAt' => '2026-01-01T00:00:00Z',
        ], JSON_THROW_ON_ERROR);

        $sig = 'sha1=' . hash_hmac('sha1', $payload, $this->secret);

        $event = Webhook::constructEvent(payload: $payload, sigHeader: $sig, secret: $this->secret);

        $this->assertSame('invoice.issued', $event->type);
        $this->assertSame('evt-001', $event->id);
        $this->assertSame('inv-123', $event->data['id']);
    }

    public function test_constructEvent_recusa_assinatura_invalida(): void
    {
        $payload = '{"type":"invoice.issued"}';
        $sig = 'sha1=' . hash_hmac('sha1', $payload, 'wrong-secret');

        $this->expectException(SignatureVerificationException::class);
        Webhook::constructEvent(payload: $payload, sigHeader: $sig, secret: $this->secret);
    }
}
