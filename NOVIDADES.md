# ProSaúde — Tudo o que foi adicionado nesta etapa

Resumo completo das mudanças feitas sobre o projeto `clinica-medica-debug` (Laravel 12, Sanctum, MySQL, Vite):
**avisos em janela (modais)**, **página 404 personalizada**, **painel do administrador**, **Meu Perfil**,
**Termos de Uso / Política de Privacidade** e as **rotas de API** que sustentam tudo isso.

> Depois de aplicar os arquivos, rode `npm run build`. Sem isso as páginas não carregam o CSS/JS novo.

---

## 1. Avisos em janela (no lugar do `alert()`)

Nenhum `alert()`, `confirm()` ou `prompt()` do navegador é mais usado (um teste automatizado varre `resources/js` para garantir isso).

**Componente compartilhado**
- `resources/js/lib/ui.js`
  - `avisar({ tipo, titulo, mensagem, itens, botao, fecharEm, acao })` — janela de aviso (`sucesso`, `aviso`, `erro`, `info`), com lista opcional de problemas e botão extra de ação.
  - `confirmar({ titulo, mensagem, confirmarTexto, voltarTexto, perigo })` — pergunta de confirmação; o foco inicial fica no botão seguro ("Voltar").
  - `criarModal({ titulo, subtitulo, largo })` — janela genérica para formulários.
  - `mensagemDeErro`, `tituloDoErro`, `avisarErro` — traduzem erros da API em texto para o usuário.
- `resources/css/modal.css` — visual clean (cantos arredondados, ícone circular por tipo, animação de entrada, barra de progresso nos avisos temporizados, respeita `prefers-reduced-motion`).

**Comportamento**
- Sucesso fecha sozinho depois de ~3 a 4,5 s; erro e aviso esperam o usuário.
- Esc e clique no fundo fecham os avisos. Janelas de formulário **não** fecham no clique no fundo (evita perder o que foi digitado).
- Acessibilidade: `role="dialog"`/`alertdialog`, `aria-labelledby`, `aria-describedby`; todo texto entra por `textContent` (sem `innerHTML`).
- Erros de preenchimento aparecem **embaixo do campo**; erros da API, de conexão e de sistema aparecem **em janela**.
- Mensagens tratadas: sem internet ("Sem conexão com o servidor…"), 429 ("Muitas tentativas…"), 5xx (nunca mostra "Server Error" cru), 403/409 ("Acesso negado", "Ação não permitida").
- **Sessão expirada (401):** aviso "Sua sessão expirou" e, ao fechar, volta para o login do perfil (`lib/sessao.js`).

**Onde foi aplicado**
- Login de paciente, médico e administrador (`sessao.js → entrar()`), com validação do e-mail/senha antes de chamar a API.
- Cadastro (`scripts/cadastro.js`): sucesso, lista de problemas (422) e erro inesperado.
- Esqueci/Redefinir senha (`scripts/recuperacao.js`): o resultado do servidor vira janela; link inválido/expirado leva ao pedido de um novo link. Sem JavaScript o texto aparece numa faixa (`<noscript>`).
- Painel do paciente e do médico: agendar, reagendar, cancelar, encaixe, atendimento, atestado etc. (sucessos e erros da API em janela; erros de preenchimento do formulário continuam dentro dele).
- Painel do administrador (todas as abas).

---

## 2. Página 404 personalizada

- `resources/views/errors/404.blade.php` — "Não encontramos esta página", código 404 grande, linha de batimento cardíaco que se desenha sozinha, botões *Voltar ao início* e *Entrar na minha área*, atalhos (Especialidades, Equipe médica, Contato, Portal do médico) e `noindex`.
- `resources/views/layouts/institucional.blade.php` — layout (cabeçalho + rodapé) usado pela 404 e pelas páginas legais. Só carrega o Vite se houver `public/build/manifest.json` ou `public/hot`, então **a 404 funciona mesmo sem `npm run build`**.
- `resources/css/institucional.css` — estilos dessas páginas.
- `bootstrap/app.php` já devolve **JSON** para `/api/*` e requisições que esperam JSON, então a API continua respondendo `{"message": ...}` com 404.
- Para criar 403, 419, 429 e 500 no mesmo estilo, copie o arquivo para `errors/{código}.blade.php`.

---

## 3. Painel do administrador

**Acesso:** `/login-admin` → `/admin`. Link discreto "Acesso administrativo" no rodapé dos logins de paciente e médico.
Credenciais de desenvolvimento: `admin@prosaude.com.br` / `admin12345` (em produção valem `ADMIN_EMAIL` / `ADMIN_PASSWORD` do `.env`).

**Arquivos**
- Views: `resources/views/admin.blade.php`, `resources/views/loginAdmin.blade.php`
- CSS: `resources/css/admin.css`
- JS: `resources/js/scripts/admin.js`, `resources/js/scripts/loginAdmin.js`, `resources/js/lib/admin/{comum,resumo,medicos,pacientes,consultas,cadastros}.js`
- Rotas web em `routes/web.php`: `/login-admin`, `/admin`

**Abas** (navegação por hash `#resumo`, `#medicos`, `#pacientes`, `#consultas`, `#cadastros`, `#perfil`)

| Aba | O que faz |
|-----|-----------|
| **Visão Geral** | Médicos ativos/inativos, pacientes (e novos nos últimos 30 dias), consultas hoje/agendadas, consultas do mês (realizadas/canceladas), barras de consultas por especialidade (top 6 do mês) e as 5 próximas consultas. |
| **Médicos** | Lista com busca (nome, CRM, e-mail) e filtro por situação. **Novo médico** e **Editar** em janela (erros sob cada campo + resumo em aviso), **Inativar/Reativar** com confirmação, **Excluir** com confirmação (desabilitado quando o médico tem consultas). CRM é gravado em maiúsculas; senha só muda se preenchida. |
| **Pacientes** | Busca por nome, CPF ou e-mail, com paginação no servidor; mostra contato, convênio, total de consultas e data de cadastro. |
| **Consultas** | Filtros por paciente/médico (nome ou CPF), situação, médico e período; paginação; **cancelar** consulta com confirmação; "Limpar filtros". |
| **Cadastros** | Três cartões — **especialidades**, **convênios** e **salas** — com adicionar/excluir e contagem de uso ("3 médicos", "8 consultas"…). |
| **Minha Conta** | Dados do administrador e troca de senha (mesmo componente do Meu Perfil). |

Também há "Sair" (revoga o token) e saudação com o primeiro nome.

---

## 4. Meu Perfil (paciente, médico e administrador)

- Aba `#perfil` nos painéis do paciente e do médico (links no menu) e "Minha Conta" no admin.
- `resources/js/lib/perfil.js` monta dois cartões:
  - **Dados pessoais** — por perfil:
    - Paciente: nome, data de nascimento (anterior a hoje), sexo, telefone, e-mail (único), endereço (opcional), convênio (lista vinda de `GET /api/convenios`); CPF somente leitura.
    - Médico: nome, telefone, e-mail; CRM e especialidade somente leitura ("alterados apenas pela administração").
    - Administrador: nome, e-mail.
  - **Alterar senha** — senha atual, nova senha (mín. 8) e confirmação. A nova senha precisa ser diferente da atual; **os outros aparelhos são desconectados** e a sessão atual continua válida.
- Ao salvar, o cabeçalho (nome, convênio…) atualiza sem recarregar a página.
- `resources/js/lib/formulario.js` — `criarCampo` (rótulo + campo + mensagem de erro com `aria-invalid`) e `tratarErroDeValidacao` (erros sob os campos + lista em aviso), reaproveitados pelo perfil e pelo admin.

---

## 5. Termos de Uso e Política de Privacidade

- `/termos` e `/privacidade` (`resources/views/termos.blade.php`, `privacidade.blade.php`), em português, com base na LGPD.
- Linkadas no checkbox do cadastro e nos rodapés da página inicial, da 404 e das páginas legais.
- **Atenção:** os textos são um modelo. Peça revisão de um advogado e preencha CNPJ e contato reais antes de publicar.

---

## 6. API nova ou alterada

Todas em `routes/api.php`.

| Rota | Acesso | Descrição |
|------|--------|-----------|
| `GET /api/convenios` | público | Convênios (`id`, `nome`) para cadastro e perfil. |
| `PUT /api/perfil` | autenticado | Edita o próprio perfil (regras por tipo de usuário). |
| `PUT /api/perfil/senha` | autenticado | Troca a senha (`senha_atual`, `senha`, `confirmar_senha`); revoga os outros tokens. |
| `GET /api/admin/resumo` | admin | Números da visão geral. |
| `GET /api/admin/consultas` | admin | Lista paginada. Filtros: `status`, `data_inicio`, `data_fim`, `medico_id`, `busca`, `por_pagina` (máx. 50; padrão 15). |
| `GET /api/admin/pacientes` | admin | Lista paginada com `busca` (nome, CPF, e-mail), convênio e total de consultas. |
| `GET /api/admin/especialidades` | admin | Especialidades com `medicos_count`. |
| `GET /api/admin/medicos` | admin | Agora com `consultas_count`, ordenado por nome. |
| `DELETE /api/admin/salas/{id}` | admin | Nova. Salas com contagem de consultas na listagem. |
| `DELETE /api/admin/convenios/{id}` | admin | Nova. Convênios com contagem de pacientes na listagem. |

Controllers novos/alterados: `PerfilController`, `Admin/PainelAdminController`, `Admin/MedicoAdminController`, `Admin/ConvenioAdminController`, `Admin/SalaAdminController`, `Admin/EspecialidadeAdminController`, `RecuperacaoSenhaController`.

**Proteção contra exclusão em cascata (retorna 409)**

| Tentativa | Motivo | Mensagem |
|-----------|--------|----------|
| Excluir médico com consultas | `medicos → consultas` é cascata | "…Inative o cadastro em vez de excluir." |
| Excluir especialidade com médicos | `especialidades → medicos` é cascata | "…há médicos vinculados a esta especialidade." |
| Excluir convênio com pacientes | `convenios → pacientes` zeraria o convênio | "…há pacientes vinculados a este convênio." |
| Excluir sala com consultas | `salas → consultas` é cascata | mensagem equivalente |

Antes dessa mudança, qualquer uma dessas exclusões apagava (ou zerava) os dados ligados em silêncio.

**Outros ajustes**
- `PUT /api/admin/medicos/{id}` passou a aceitar atualização parcial (`sometimes|required`), usada por Inativar/Reativar.
- `lang/pt_BR/validation.php`: mensagem da regra `integer` e nomes amigáveis (`senha_atual`, `endereco`, `convenio_id`, `crm`, `especialidade_id`, `status`, `busca`, `data_inicio`, `data_fim`, `por_pagina`).
- Link de recuperação de senha inválido agora redireciona com um aviso (`link_invalido`) que leva ao pedido de novo link.

---

## 7. Ajustes de CSS compartilhado

- `painel-base.css` importa `modal.css` e ganhou estilos de perfil (`.perfil-grade`, `.form-estreito`, `.campo-erro`, `.somente-leitura`…) e blocos que antes ficavam só no painel do médico (`.metricas`, `.tabela*`, `.entrada-busca`, `.acoes-linha`), agora usados também pelo admin.
- `medico.css` perdeu esses blocos (sem mudança visual no painel do médico).
- Rodapés: links "Acesso administrativo", "Termos de uso" e "Política de privacidade" em `index.css`, `loginPaciente.css`, `loginMedico.css` e `cadastro.css`.
- Correção de texto: endereço da clínica ("Acácias").

---

## 8. Testes

**PHPUnit — 183 testes, 696 asserções (todos passando)**
- Novos: `PerfilTest`, `PainelAdminTest`, `AvisosRecuperacaoTest`.
- Ampliados: `PaginasTest` (novas páginas, 404 personalizada, 404 JSON da API, links dos termos, link de admin nos logins, aba `#perfil`, abas do admin e varredura que proíbe `alert/confirm/prompt` em `resources/js`) e `PermissoesAdminTest` (novas rotas exigem perfil admin).

**Navegador (Playwright/Chromium)**
- Testes antigos adaptados aos modais (botões dos painéis; login, cadastro e recuperação de senha) — passando.
- Novo teste das novidades: avisos (Esc, fundo, foco, sem conexão, erro 500, celular), 404, termos/privacidade, login e todas as abas do admin (CRUD de médicos, busca, filtros, cancelamento, cadastros, conta), Meu Perfil de paciente e médico, sessão expirada, e **ausência de rolagem horizontal de 320 a 1920 px**. Nenhum erro de console e nenhum `alert` nativo.

---

## 9. Lista de arquivos novos e alterados (56)

**Backend**
`app/Http/Controllers/Api/PerfilController.php` (novo) · `Api/Admin/PainelAdminController.php` (novo) · `Api/Admin/{Medico,Convenio,Sala,Especialidade}AdminController.php` · `RecuperacaoSenhaController.php` · `routes/api.php` · `routes/web.php` · `lang/pt_BR/validation.php`

**Views**
`admin`, `loginAdmin`, `termos`, `privacidade`, `layouts/institucional`, `errors/404` (novos) · `index`, `loginPaciente`, `loginMedico`, `cadastro`, `esqueciSenha`, `redefinirSenha`, `paciente`, `medico` (alterados)

**CSS**
`modal.css`, `admin.css`, `institucional.css` (novos) · `painel-base.css`, `medico.css`, `index.css`, `cadastro.css`, `loginPaciente.css`, `loginMedico.css` (alterados)

**JavaScript**
`scripts/admin.js`, `scripts/loginAdmin.js`, `scripts/recuperacao.js`, `lib/perfil.js`, `lib/formulario.js`, `lib/admin/{comum,resumo,medicos,pacientes,consultas,cadastros}.js` (novos) · `lib/ui.js`, `lib/sessao.js`, `scripts/cadastro.js`, `scripts/paciente.js`, `scripts/medico.js` (reescritos/alterados)

**Testes e documentação**
`tests/Feature/{PerfilTest,PainelAdminTest,AvisosRecuperacaoTest}.php` (novos) · `PaginasTest.php`, `PermissoesAdminTest.php` (ampliados) · `README.md`, `CORRECOES.md`

---

## 10. Pontos de atenção e próximos passos opcionais

- **Revisão jurídica** dos Termos e da Política de Privacidade (CNPJ, contato e cláusulas).
- **Mudança de comportamento:** exclusões em uso agora retornam 409 (veja a tabela da seção 6).
- Só a **404** foi personalizada; 403, 419, 429 e 500 podem seguir o mesmo modelo.
- Ainda não incluído: `docker-compose.prod.yml` + guia de deploy na Oracle Cloud com HTTPS (Caddy e `trustProxies`).
