# Correções aplicadas sobre a branch `dev`

Este arquivo resume o que mudou em relação ao commit `6b584a9` ("Atualização de testes") da branch `dev` de
`mwrlon/clinica-medica`. A numeração dos itens 1 a 18 é a do diagnóstico enviado antes.

**Aparência:** as páginas estáticas (início, logins, cadastro, esqueci a senha) não foram alteradas e renderizam iguais ao original:
em 10 capturas (desktop e celular), 9 são idênticas pixel a pixel e a décima difere em 50 pixels por ±1 de anti-aliasing na borda de
um botão. Só os painéis do médico e do paciente foram refeitos, a pedido, para seguir o visual da página inicial
(seções "Visual dos painéis" e "Botões dos painéis").

## Erros que derrubavam a API

| # | O que foi feito |
|---|-----------------|
| 1 | Criados os models `Administrador`, `Consulta`, `Exame`, `Especialidade`, `Sala`, `Convenio` e `Atendimento`. Em `config/auth.php`, o model padrão deixa de ser o inexistente `App\Models\User` e passa a ser `Paciente` (a API autentica por token, cada perfil com o seu model). |
| 2 | `AuthController` ganhou `loginPaciente` e `loginMedico`. Resposta de login: `access_token`, `token_type`, `perfil` e `usuario`. |
| 3 | Migration `personal_access_tokens` (Sanctum). |
| 4 | Models com `CREATED_AT = 'criado_em'` e `UPDATED_AT = null` (ou `$timestamps = false` onde não há data). |
| 5 | Relações `Medico::especialidade()` e as de `Consulta`, `Paciente`, `Sala`, `Atendimento` e `Exame`. |
| 6 | Criado `ConsultaController::registrarAtendimento` (médico responsável; pode pedir exames; a consulta vira "realizada"). |
| 7 | Fluxo "esqueci minha senha": `POST /esqueci-senha`, link de uso único válido por 60 min (token com hash em `password_reset_tokens`), tela `redefinirSenha.blade.php`. Resposta idêntica para e-mail cadastrado ou não. |

## Integração front-end ↔ API

| # | O que foi feito |
|---|-----------------|
| 8 | A API aceita `confirmar_senha` (formulário) e também `senha_confirmation`. |
| 9 | Os scripts de login guardam `access_token` (antes gravavam `"undefined"`). |
| 10 | Credenciais inválidas respondem **422** no campo `email` (é o que o front já esperava); médico inativo responde 403. As mensagens aparecem sob o campo, nas classes `.field-error` que já existiam. |
| 11 | Botão "Sair" com `id="btn-sair"`: revoga o token na API, limpa a sessão e volta ao login do perfil. |
| 12 | `paciente.js` e `medico.js` montam os painéis com os dados reais da API (perfil, próximas consultas, exames, receitas, lembretes; no médico, métricas, próximo paciente, agenda por data e os dois gráficos). Todo texto vindo da API entra por `textContent`. As amostras fixas do HTML foram removidas, e a estrutura de tags foi mantida para o CSS continuar valendo. |

## Banco e dados

| # | O que foi feito |
|---|-----------------|
| 13 | `SalaAdminController` usa a coluna `identificacao`. |
| 14 | `ExameController` ordena por `solicitado_em` e filtra por paciente, médico (exames que ele pediu) ou admin (todos). |
| 15 | `DatabaseSeeder` cria especialidades, convênios, salas e o administrador (`ADMIN_EMAIL`/`ADMIN_PASSWORD`). Fora de produção, `DemoSeeder` cria médicos, pacientes, consultas, exames e receitas de exemplo. |
| 16 | Migrations `cache` e `jobs`. |

## Configuração e ambiente

| # | O que foi feito |
|---|-----------------|
| 17 | `.env.example` com os dados do `docker-compose.yml` (`DB_HOST=db`, `clinica_medica`, `laravel_user`), `APP_LOCALE=pt_BR`, fuso `America/Sao_Paulo` e variáveis do administrador. README do projeto no lugar do padrão do Laravel. |
| 18 | `public/build` continua sem versionar (como deve ser); o README explica `npm run build`. Os testes usam `withoutVite()` e não dependem do build. |

## Visual dos painéis (paciente e médico)

Os painéis estavam com a aparência quebrada: `logo.svg` (1344×768) sem limite de tamanho, seletores de CSS amplos demais, títulos em negrito
padrão do navegador (a fonte Newsreader só era carregada no peso 500, então o negrito era "falso") e sem regras para celular. Agora:

- **`resources/css/painel-base.css` (novo):** mesmos tokens, fontes e componentes do `index.css`. Títulos `h1`–`h4` em Newsreader peso 500,
  textos e nomes de item em IBM Plex Sans, botões `.btn` / `.btn-primary` / `.btn-outline` (raio 4 px, peso 600), etiquetas `.tag`,
  cartões brancos com borda suave e cabeçalho fixo translúcido, como no site.
- **`paciente.css` e `medico.css`** só têm o que é de cada tela. O estilo passou a ser por **classes** (não mais por posição da tag),
  o que evita o tipo de quebra que acontecia antes.
- **HTML dos painéis:** saudação em `<h1>`, menu com destaque da seção atual, cabeçalho com logo de 38 px, "Sair" como botão, rodapé escuro
  como o da página inicial. No médico, as seções seguem a ordem do menu e `#pacientes` (que o menu apontava sem existir) agora existe.
- **Responsivo:** sem rolagem horizontal de 320 a 1920 px; menu em segunda linha com rolagem no celular; janelas se ajustam à tela.

## Botões dos painéis

Os botões não faziam nada porque nunca tiveram código (e a API não oferecia o necessário para o paciente agendar sozinho).

**API nova** (`ConsultaController`, `AgendaController`, `PacienteController`, `app/Support/AgendaClinica.php`, `config/clinica.php`):

- `GET /api/medicos/{id}/horarios?data=` — horários livres do médico no dia, dentro da grade da clínica.
- `PUT /api/consultas/{id}/reagendar` — troca dia e hora, mantendo a sala se estiver livre.
- `GET /api/pacientes?busca=` — médico/administrador buscam paciente por nome ou CPF (para o encaixe).
- `POST /api/consultas`: `sala_id` passou a ser opcional (a primeira sala livre no horário é usada); o paciente só marca dentro do expediente,
  o médico pode fazer encaixes fora da grade; horários passados são recusados; médico, paciente e sala não podem ter dois compromissos
  no mesmo horário.

**Paciente:** *Agendar Nova Consulta* (especialidade → médico → dia → horário livre → motivo), *Reagendar* e *Cancelar* (com confirmação).

**Médico:** *Atender Agora* (diagnóstico, receita e exames, que o paciente passa a ver no painel dele), *Ver Prontuário*,
*+ Nova Consulta Encaixe* (busca o paciente, dia e horário, inclusive fora da grade), *Emitir Receita Médica* e *Solicitar Exames*
(abrem o atendimento de uma consulta pendente), *Chamar Paciente*, *Meus Pacientes* (lista com prontuário) e *Atestados / Laudos*.

Limites conhecidos, de propósito:

- O atendimento não pode ser editado depois de registrado (a API só tem criação).
- *Atestados / Laudos* gera um documento para imprimir ou salvar em PDF; não fica gravado no sistema.
- *Chamar Paciente* mostra o nome do paciente em uma janela para o médico iniciar o atendimento; não há painel de espera nem aviso ao paciente.
- Tempo médio por consulta e avaliação média continuam como "—" (o sistema não tem esses dados).
- Médicos e administradores podem buscar qualquer paciente por nome/CPF, apenas para marcar encaixes; o prontuário mostra só as
  consultas do próprio médico com aquele paciente.

## Segurança e lógica

- `/api/admin/*` agora exige `auth:sanctum` **e** `perfil:admin` (middleware `ExigirPerfil`); `POST /api/atendimentos` exige `perfil:medico`.
- `POST /api/consultas`: o paciente só agenda para si e o médico só na própria agenda (o `paciente_id`/`medico_id` do cliente é ignorado).
- `cancelar` confere o dono: só o paciente ou o médico da consulta, ou um administrador; consulta realizada não cancela.
- Rota `medicp` corrigida para `medico`.

## Outros problemas encontrados durante os testes

- **Horário cancelado continuava ocupado.** Cancelar uma consulta e marcar o mesmo horário de novo dava erro 500 (índice único do banco). Nova migration `..._ajusta_indices_unicos_das_consultas` troca os índices por versões que ignoram consultas canceladas (funciona em MySQL e SQLite); duas requisições simultâneas pelo mesmo horário recebem 422 em vez de 500.
- `POST /api/consultas` devolvia `status: null` na resposta; agora devolve `agendada`.
- Rotas `api/*` sempre respondem JSON (antes, sem o cabeçalho `Accept`, um 401 virava redirecionamento).
- Mensagens de validação em português (`lang/pt_BR`) e `APP_TIMEZONE`/`APP_LOCALE` no `config/app.php`.
- Typo "Instular" no `docker/Dockerfile`.

## Como validar

```bash
php artisan test        # 122 testes (SQLite em memória; também passam no MySQL)
```

Feito antes da entrega, todos com sucesso: todas as migrations, os seeders e a suíte no **MySQL 8.0**; uma bateria de 59 verificações
da API; e dois testes de navegador (Chromium): um cobrindo cadastro, logins, logout, painéis com dados reais, gráficos, recuperação de senha
e proteção de rotas, e outro com todos os botões dos painéis (agendar, reagendar, cancelar, encaixe, atendimento, prontuário, atestado),
em desktop e celular, sem erros de JavaScript.

## Mensagem de commit sugerida

```
Corrige login, cadastro, painéis e API da clínica

- Adiciona models, relações, logins por perfil, migrations (Sanctum, cache, jobs, senha, índices) e atendimento
- Protege /api/admin e valida dono ao agendar/cancelar consultas
- Liga os painéis do médico e do paciente aos dados reais e refaz o visual deles no padrão da página inicial
- Faz funcionar os botões dos painéis: agendar, reagendar, cancelar, encaixe, atendimento, prontuário e atestado
- Adiciona horários livres, reagendamento e busca de pacientes na API, com grade de atendimento em config/clinica.php
- Adiciona recuperação de senha, seeders de demonstração, README e 122 testes
```
