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
| `POST /api/consultas`                                  | autenticado   | Agenda (paciente só para si; médico só na própria agenda). `sala_id` é opcional: sem ele, a primeira sala livre é usada |
| `PUT /api/consultas/{id}/cancelar`                     | paciente/médico da consulta ou admin | Cancela e libera o horário |
| `PUT /api/consultas/{id}/reagendar`                    | paciente/médico da consulta ou admin | Muda `data` e `hora` (mesmo médico) |
| `GET /api/medicos/{id}/horarios?data=AAAA-MM-DD`       | autenticado   | Horários livres do médico no dia (`ignorar={consulta}` ao reagendar) |
| `GET /api/pacientes?busca=…`                           | médico/admin  | Busca por nome ou CPF (3+ letras, até 10 resultados) para marcar encaixes |
| `POST /api/atendimentos`                               | médico        | Registra o atendimento (receita, diagnóstico) |
| `GET /api/exames`                                      | autenticado   | Paciente: os seus · médico: os que pediu · admin: todos |
| `/api/admin/*`                                         | administrador | CRUD de médicos, especialidades, convênios e salas |

### Horário de atendimento

A grade de horários fica em `config/clinica.php`: segunda a sexta das 07:00 às 19:00, sábado das 08:00 às 12:00, domingo fechado,
consultas de 30 em 30 minutos (é o que o site informa). O **paciente** só marca dentro dessa grade; o **médico** pode marcar
*encaixes* fora dela. Horários que já passaram são recusados para todos. A sala é escolhida automaticamente
(a primeira livre no horário) e uma consulta cancelada libera médico, paciente e sala.

## Painéis

Os dois painéis usam o mesmo visual da página inicial (`resources/css/painel-base.css` reaproveita as cores, fontes e componentes do `index.css`;
`paciente.css` e `medico.css` só trazem o que é específico de cada tela).

- **Paciente** (`/paciente`): *Agendar Nova Consulta* (especialidade → médico → dia → horário livre), *Reagendar* e *Cancelar* (com confirmação).
- **Médico** (`/medico`): agenda por dia, *Atender Agora* (diagnóstico, receita e exames), *Ver Prontuário*, *Nova Consulta Encaixe*,
  *Emitir Receita Médica* e *Solicitar Exames* (abrem o atendimento de uma consulta pendente), *Chamar Paciente*, *Meus Pacientes* e
  *Atestados / Laudos*. O atestado/laudo é um documento para **imprimir ou salvar em PDF** pelo navegador (não fica gravado no sistema).

O código dos botões está em `resources/js/scripts/paciente.js` e `medico.js`, com as peças compartilhadas em `resources/js/lib`
(`ui.js`: janelas, confirmação e avisos; `horarios.js`: escolha de data e horário).

## Testes

```bash
php artisan test
```

Os testes usam SQLite em memória (definido no `phpunit.xml`) e não dependem do MySQL nem de `npm run build`.
Crie o `.env` antes (`cp .env.example .env`): sem ele os testes passam, mas o `php artisan test` mostra avisos
sobre o arquivo `.env` inexistente (`vendor/bin/phpunit` não tem esse problema).

## Estrutura

- `app/Http/Controllers/Api` — controllers da API · `app/Http/Middleware/ExigirPerfil.php` — controle por perfil (`perfil:admin|medico|paciente`)
- `app/Models` — modelos Eloquent (as tabelas usam `criado_em`, sem `updated_at`)
- `database/migrations`, `database/seeders` — esquema e dados iniciais/de exemplo
- `app/Support/AgendaClinica.php` — regras de horário e de sala · `config/clinica.php` — expediente da clínica
- `resources/views` — páginas Blade · `resources/css` — estilos · `resources/js` — scripts (login, cadastro e painéis)
- `lang/pt_BR` — mensagens de validação em português
