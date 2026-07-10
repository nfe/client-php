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

    public function test_constructEvent_aceita_envelope_v2_assinado_corretamente(): void
    {
        // Envelope v2 real da NFE.io: { action, payload } — `data` é o payload
        // aninhado (não o corpo inteiro).
        $payload = json_encode([
            'action'    => 'service_invoice.issued_successfully',
            'payload'   => ['id' => 'inv-123', 'flowStatus' => 'Issued'],
            'id'        => 'evt-001',
            'createdAt' => '2026-01-01T00:00:00Z',
        ], JSON_THROW_ON_ERROR);

        $sig = 'sha1=' . hash_hmac('sha1', $payload, $this->secret);

        $event = Webhook::constructEvent(payload: $payload, sigHeader: $sig, secret: $this->secret);

        $this->assertSame('service_invoice.issued_successfully', $event->type);
        $this->assertSame('evt-001', $event->id);
        $this->assertSame('inv-123', $event->data['id']);
        $this->assertSame('Issued', $event->data['flowStatus']);
    }

    public function test_constructEvent_payload_flat_usa_o_corpo_todo_como_data(): void
    {
        // Sem envelope v2 (`action`/`payload`), o helper cai no fallback flat:
        // `data` passa a ser o corpo decodificado inteiro, e `type` vem de
        // type/event_type/action. Este teste pina esse contrato.
        $payload = json_encode([
            'type' => 'service_invoice.issued_successfully',
            'id'   => 'inv-999',
        ], JSON_THROW_ON_ERROR);

        $sig = 'sha1=' . hash_hmac('sha1', $payload, $this->secret);

        $event = Webhook::constructEvent(payload: $payload, sigHeader: $sig, secret: $this->secret);

        $this->assertSame('service_invoice.issued_successfully', $event->type);
        $this->assertSame('inv-999', $event->data['id']); // data = corpo inteiro
        $this->assertSame('inv-999', $event->id);
    }

    public function test_constructEvent_recusa_assinatura_invalida(): void
    {
        $payload = '{"action":"service_invoice.issued_successfully","payload":{}}';
        $sig = 'sha1=' . hash_hmac('sha1', $payload, 'wrong-secret');

        $this->expectException(SignatureVerificationException::class);
        Webhook::constructEvent(payload: $payload, sigHeader: $sig, secret: $this->secret);
    }
}
