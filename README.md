# Baba PRO

Base inicial do SaaS Baba PRO em PHP + MySQL, com autenticacao por `telefone + codigo do baba + PIN` e isolamento multiusuario por `baba`.

## Estrutura inicial

- `public/`: ponto de entrada HTTP.
- `src/`: bootstrap, core e servicos.
- `database/schema.sql`: schema inicial de producao.
- `database/seed.sql`: dados de teste.

## Como rodar local

1. Copie `.env.example` para `.env` e ajuste `DB_*`.
2. Crie banco e usuario no MySQL (uma das opcoes):
   - **Recomendado:** `chmod +x scripts/setup_local_db.sh && ./scripts/setup_local_db.sh` (pede a senha do `root` local)
   - Manual: `mysql -u root -p < scripts/setup_local_mysql.sql` (senha do usuario no SQL deve bater com o `.env`)
3. Rode o schema e seed:
   - `mysql -u root -p babapro < database/schema.sql`
   - `mysql -u root -p babapro < database/seed.sql`
   - `php scripts/migrate_baba_branding.php`
4. Inicie servidor local:
   - `php -S localhost:8000 -t public`
5. Acesse `http://localhost:8000/login.php`.

### Site sem CSS / layout diferente do local

No aaPanel, defina a **raiz do site (Document Root)** para a pasta `public`:

`/www/wwwroot/babapro.tdesksolutions.com.br/public`

Se a raiz for a pasta do projeto (sem `public`), o CSS em `/assets/app.css` nao carrega. A funcao `asset_url()` tenta corrigir com `/public/assets/...`, mas o ideal e ajustar a raiz no painel.

### Erro `Access denied for user 'babapro'@'localhost'`

O MySQL local ainda nao tem o usuario/senha do `.env`. Rode o passo 2 acima ou altere no `.env` para um usuario que ja exista (ex.: `root` + sua senha local).

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
