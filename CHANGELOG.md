# Changelog

Todas as mudanças relevantes deste projeto serão documentadas neste arquivo.

O formato é baseado em [Keep a Changelog](https://keepachangelog.com/pt-BR/1.1.0/),
e este projeto segue [Versionamento Semântico](https://semver.org/lang/pt-BR/spec/v2.0.0.html).

## [Unreleased] — v3 (em desenvolvimento)

### Adicionado

- Baseline em PHP 8.2+. Encerra suporte às versões 5.4 até 8.1.
- Autoload PSR-4 com namespace raiz `Nfe\` em `src/`.
- Pacote Composer renomeado: `nfe/client-php` (antes `nfe/nfe`). Os dois
  podem coexistir durante a migração. O `nfe/nfe` está congelado na v2.5.
- `declare(strict_types=1)` em todos os arquivos-fonte.
- Pest 3, PHPStan nível 8 e PHP-CS-Fixer (PER-CS 2.0 + PHP 8.2 migration)
  configurados em `require-dev` e impostos pelo CI.
- Matriz de CI do GitHub Actions em PHP 8.2 / 8.3 / 8.4.
- `dataApiKey` opcional em `Config` e `Client` para a API de serviços de
  dados (consultas de CEP/CNPJ/CPF, query de NF-e/NFC-e). Quando definida,
  o SDK roteia as famílias de recurso correspondentes para a chave de
  dados; quando `null`, faz fallback para `apiKey` — espelha a cadeia
  `resolveDataApiKey()` do SDK Node.
- `Nfe\Exception\AuthorizationException` para HTTP 403 (distinta do 401
  `AuthenticationException`). Sinaliza recusa por plano/escopo — comum
  quando uma chave principal chama um endpoint de serviços de dados sem
  o plano de dados.
- Mapeamento automático de toda resposta não-2xx para uma subclasse tipada
  de `ApiErrorException` na camada de recurso. Antes, respostas 5xx podiam
  emergir como DTOs com tudo `null`; agora geram exceção.

### Alterado

- O desenvolvimento agora ocorre na branch `v3`. A `master` está congelada
  na v2.5.

### Removido

- `lib/NFe/*` (código legado autoloaded por classmap).
- Integração com runner `test/simpletest/*`. Pest substitui o SimpleTest.
- `composer.phar` versionado no repositório.
- `.travis.yml` (substituído por `.github/workflows/ci.yml`).

## [2.5] e anteriores

Veja o histórico git na branch `master`. A linha v2 não recebe mais manutenção.
