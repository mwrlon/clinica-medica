# ProSaúde — Sistema de Clínica Médica

Aplicação Laravel 12 com páginas em Blade, API REST protegida por Laravel Sanctum (token) e banco **MySQL**.
Perfis: **paciente**, **médico** e **administrador**.

## Requisitos

- PHP 8.2+ (com `pdo_mysql`, `mbstring`, `bcmath`, `gd`) e Composer
- Node 20+ e npm (para compilar o CSS/JS com Vite)
- MySQL 8 (o `docker-compose.yml` já sobe um)

## Como rodar com Docker

```bash
cp .env.example .env              # já vem com os dados do MySQL do docker-compose

docker compose up -d --build
docker compose exec app composer install
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate --seed

npm install && npm run build      # na sua máquina: gera public/build (não é versionado)
```

Acesse **http://localhost:8000**.

O MySQL também fica disponível na sua máquina em `127.0.0.1:3307`
(usuário `laravel_user`, senha `laravel_password`, banco `clinica_medica`).

## Como rodar sem Docker

```bash
cp .env.example .env
# ajuste no .env: DB_HOST=127.0.0.1 (e DB_PORT/DB_USERNAME/DB_PASSWORD do seu MySQL)
composer install
php artisan key:generate
php artisan migrate --seed
npm install && npm run build
php artisan serve                 # http://127.0.0.1:8000
```

Durante o desenvolvimento, `npm run dev` recompila o front-end automaticamente.

> **Importante:** `public/build` não é versionado. Sem `npm run build` as páginas dão erro
> *"Vite manifest not found"*.

## Logins de demonstração

Criados por `php artisan db:seed` (os dados de exemplo **não** são criados em produção):

| Perfil        | E-mail                           | Senha         |
|---------------|----------------------------------|---------------|
| Paciente      | `helena@exemplo.com`             | `paciente123` |
| Médico        | `rafael.tavares@prosaude.com.br` | `medico123`   |
| Administrador | `admin@prosaude.com.br`          | `admin12345`  |

Há outros médicos e pacientes de exemplo no `database/seeders/DemoSeeder.php` (todos os médicos usam
`medico123`). O administrador usa `ADMIN_EMAIL` / `ADMIN_PASSWORD` do `.env`; em produção a senha é obrigatória.

## Variáveis de ambiente importantes

| Variável                           | Para quê                                                             |
|------------------------------------|----------------------------------------------------------------------|
| `DB_HOST`, `DB_PORT`, …            | `db`/`3306` dentro do Docker; `127.0.0.1`/`3307` direto na máquina   |
| `APP_LOCALE`, `APP_TIMEZONE`       | `pt_BR` e `America/Sao_Paulo` (mensagens de validação e datas)       |
| `ADMIN_EMAIL`, `ADMIN_PASSWORD`    | Administrador inicial criado pelo seeder                             |
| `MAIL_MAILER`                      | `log` por padrão: os e-mails são gravados em `storage/logs/laravel.log` |

### Recuperação de senha

Em `/esqueci-senha` o usuário informa o e-mail e recebe um link de redefinição (válido por tempo limitado e de uso único).
Com `MAIL_MAILER=log` não há envio real: o e-mail, com o link, aparece em `storage/logs/laravel.log`.
Para enviar de verdade, configure `MAIL_MAILER=smtp` e as variáveis `MAIL_HOST`, `MAIL_PORT`, `MAIL_USERNAME`, `MAIL_PASSWORD`.

## API

Todas as rotas ficam em `/api` e respondem JSON. Rotas protegidas exigem `Authorization: Bearer <access_token>`.

| Método e rota                                          | Acesso        | Descrição                                  |
|--------------------------------------------------------|---------------|--------------------------------------------|
| `POST /api/cadastrar`                                  | público       | Cadastro de paciente (já devolve o token)  |
| `POST /api/login/paciente` · `/medico` · `/admin`      | público       | Login por perfil (`email`, `senha`)        |
| `GET /api/especialidades` · `GET /api/medicos`         | público       | Listas para o agendamento                  |
| `POST /api/logout` · `GET /api/perfil`                 | autenticado   | Encerra a sessão / dados do usuário logado |
| `GET /api/consultas`                                   | autenticado   | Paciente/médico: as suas · admin: todas    |
| `POST /api/consultas`                                  | autenticado   | Agenda (paciente só para si; médico só na própria agenda) |
| `PUT /api/consultas/{id}/cancelar`                     | paciente/médico da consulta ou admin | Cancela e libera o horário |
| `POST /api/atendimentos`                               | médico        | Registra o atendimento (receita, diagnóstico) |
| `GET /api/exames`                                      | autenticado   | Paciente: os seus · médico: os que pediu · admin: todos |
| `/api/admin/*`                                         | administrador | CRUD de médicos, especialidades, convênios e salas |

## Testes

```bash
php artisan test
```

Os testes usam SQLite em memória (definido no `phpunit.xml`) e não dependem do MySQL nem de `npm run build`.

## Estrutura

- `app/Http/Controllers/Api` — controllers da API · `app/Http/Middleware/ExigirPerfil.php` — controle por perfil (`perfil:admin|medico|paciente`)
- `app/Models` — modelos Eloquent (as tabelas usam `criado_em`, sem `updated_at`)
- `database/migrations`, `database/seeders` — esquema e dados iniciais/de exemplo
- `resources/views` — páginas Blade · `resources/css` — estilos · `resources/js` — scripts (login, cadastro e painéis)
- `lang/pt_BR` — mensagens de validação em português
