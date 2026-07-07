<?php

declare(strict_types=1);

namespace Nfe\Http;

/**
 * Fase em que uma falha de rede ocorreu, do ponto de vista da segurança de retry.
 *
 * Distinguir a fase é o que torna o retry de POST seguro: só é seguro reexecutar
 * um POST quando temos certeza de que a requisição **não** chegou ao servidor —
 * caso contrário, o servidor pode já tê-la processado (ex.: emitido uma NFS-e) e
 * o retry duplicaria o efeito.
 *
 * A classificação é deliberadamente **conservadora**: na dúvida, assume-se
 * {@see self::RequestMaybeSent}. Erramos para o lado de perder uma retentativa
 * segura, nunca para o lado de reexecutar algo que pode ter sido processado.
 */
enum FailurePhase
{
    /**
     * A requisição comprovadamente não chegou ao servidor (DNS não resolveu,
     * TCP não conectou, TLS falhou no handshake). Seguro reexecutar qualquer
     * método, inclusive POST.
     */
    case ConnectionNotEstablished;

    /**
     * A requisição pode ter chegado e sido processada pelo servidor antes da
     * falha (ex.: timeout de leitura após o corpo ter sido enviado). Inseguro
     * reexecutar métodos não idempotentes como POST.
     */
    case RequestMaybeSent;
}
