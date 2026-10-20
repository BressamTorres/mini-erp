# CI/CD do Mini ERP

## Ambiente validado

- PHP `^8.3`; CI e build da release usam PHP 8.4.
- Laravel 13, PHPUnit 12 e Composer 2.
- Node.js 22, npm e Vite 8.
- Testes usam SQLite em memória conforme `phpunit.xml`.
- Produção: `/var/www/blog_infinit`.

O CI roda em pull requests e pushes para `main`: valida o Composer, instala dependências, executa Pint, testes Laravel, testes JavaScript disponíveis e build Vite. Nenhum banco ou serviço de produção é acessado.

## Secrets do ambiente `production`

Cadastre em **Settings > Environments > production**:

- `DEPLOY_HOST`: hostname ou IP da VPS.
- `DEPLOY_PORT`: porta SSH, normalmente `22`.
- `DEPLOY_USER`: usuário restrito de deploy.
- `DEPLOY_SSH_KEY`: chave privada exclusiva do GitHub Actions.
- `DEPLOY_KNOWN_HOSTS`: linha validada da chave pública SSH da VPS.
- `DEPLOY_HEALTHCHECK_URL`: URL HTTPS pública e completa do healthcheck, terminando em `/up`.

Configure ao menos um **Required reviewer** no ambiente. Em repositório privado, confirme que o plano do GitHub oferece essa proteção. O workflow continua manual mesmo sem essa regra, pois usa `workflow_dispatch`.

## Preparação única da VPS

O deploy preserva a estrutura atual em `/var/www/blog_infinit`. Não é necessário criar `current`, `releases` ou `shared`, nem alterar o root atual do Nginx:

```nginx
root /var/www/blog_infinit/public;
```

Confirme que `.env` e `storage` já existem, que `public/storage` aponta para `storage/app/public` e que o usuário `deploy` pode atualizar o projeto. O workflow cria `.deploy/staging` e `.deploy/backups` automaticamente.

```bash
cd /var/www/blog_infinit
test -f .env
test -d storage
test -L public/storage || php artisan storage:link
sudo chown -R deploy:www-data /var/www/blog_infinit
sudo chmod 0640 .env
sudo find storage bootstrap/cache -type d -exec chmod 2770 {} \;
sudo find storage bootstrap/cache -type f -exec chmod 0660 {} \;
```

A VPS precisa de `php`, `curl`, `rsync` e `tar`. Composer e Node.js não são necessários na VPS: `vendor` e `public/build` são preparados no GitHub Actions.

Crie um wrapper pertencente ao root em `/usr/local/sbin/reload-php-fpm`:

```shell
#!/bin/sh
exec systemctl reload php8.4-fpm
```

Permita ao usuário de deploy executar somente esse wrapper via `sudoers`:

```text
deploy ALL=(root) NOPASSWD: /usr/local/sbin/reload-php-fpm
```

## Publicação

1. Faça push para `main` e aguarde o workflow **CI** terminar com sucesso.
2. Abra **Actions > Deploy de produção > Run workflow** e selecione `main`.
3. Aprove o ambiente `production`, quando solicitado.
4. Confirme o healthcheck `/up` e o SHA exibido no log.

O deploy não executa migrations. Ele prepara a release em staging, salva um snapshot da versão atual, ativa manutenção apenas durante a sincronização, recria os caches, recarrega o PHP-FPM e valida `/up`. Deploys simultâneos são serializados.

São preservados em produção: `.env`, `storage`, `public/storage`, `.git` e `node_modules`.

## Rollback

Falhas durante a publicação ou no healthcheck restauram automaticamente o snapshot criado em `.deploy/backups/<release-id>`.

Para recuperação manual, liste os snapshots e confira a revisão desejada:

```bash
ls -la /var/www/blog_infinit/.deploy/backups
cat /var/www/blog_infinit/.deploy/backups/SNAPSHOT_ESCOLHIDO/REVISION
```

Restaure o snapshot preservando os dados persistentes:

```bash
cd /var/www/blog_infinit
php artisan down --retry=60 --no-interaction
rsync --archive --delete-after \
  --exclude=.deploy \
  --exclude=.env \
  --exclude=.git \
  --exclude=node_modules \
  --exclude=public/storage \
  --exclude=storage \
  .deploy/backups/SNAPSHOT_ESCOLHIDO/ ./
php artisan config:cache --no-interaction
php artisan route:cache --no-interaction
php artisan view:cache --no-interaction
sudo /usr/local/sbin/reload-php-fpm
php artisan queue:restart --no-interaction
php artisan up --no-interaction
```

Valide a URL de saúde depois do rollback. Como migrations não são automáticas, o rollback de código não altera o banco.

## Diagnóstico

- Logs do pipeline: aba **Actions** do repositório, selecione o workflow e a etapa com falha.
- Logs Laravel: `/var/www/blog_infinit/storage/logs`.
- Logs Nginx e PHP-FPM: `journalctl` ou arquivos configurados na VPS.
- Revisão ativa: `cat /var/www/blog_infinit/REVISION`.

Não armazene `.env`, chaves SSH, tokens ou senhas no repositório. Revogue imediatamente qualquer chave exposta.
