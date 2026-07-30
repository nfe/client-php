<?php

declare(strict_types=1);

namespace Nfe;

/**
 * Declarative environment metadata for the NFE.io API.
 *
 * **This enum does not route traffic.** The NFE.io platform has no sandbox
 * host: every request goes to the production hosts regardless of this value
 * (see {@see Config::baseUrlForApi()}, which never consults it). Isolation on
 * NFE.io comes from the **API key scope** (use a development-account key) and
 * from the **company's environment** (`company.environment = Development`),
 * never from a subdomain.
 */
enum Environment: string
{
    case Production = 'production';

    /**
     * @deprecated Não há host sandbox na plataforma — selecionar `Sandbox`
     *             NUNCA isolou tráfego (toda request vai para produção). Para
     *             testar sem efeito fiscal, use uma chave de conta de
     *             desenvolvimento e uma empresa com
     *             `company.environment = Development`. Será removido na
     *             próxima major.
     */
    case Sandbox = 'sandbox';
}
