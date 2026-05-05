# Baba PRO

Base inicial do SaaS Baba PRO em PHP + MySQL, com autenticacao por `telefone + codigo do baba + PIN` e isolamento multiusuario por `baba`.

## Estrutura inicial

- `public/`: ponto de entrada HTTP.
- `src/`: bootstrap, core e servicos.
- `database/schema.sql`: schema inicial de producao.
- `database/seed.sql`: dados de teste.

## Como rodar local

1. Copie `.env.example` para `.env`.
2. Crie o banco `baba_pro` no MySQL.
3. Rode `database/schema.sql` e depois `database/seed.sql`.
4. Inicie servidor local:
   - `php -S localhost:8000 -t public`
5. Acesse `http://localhost:8000/login.php`.

## Login de teste

- Owner SaaS
  - Telefone: `71997087082`
  - Codigo do Baba: `BABA10`
  - PIN: `1234`
- Usuario comum
  - Telefone: `71911112222`
  - Codigo do Baba: `BABA10`
  - PIN: `4321`

## Proximo passo sugerido

Implementar modulo de `eventos + presencas` com regras por papel (`baba_admin`/`member`).
