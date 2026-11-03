<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex">
    <title>ProSaúde - Painel Administrativo</title>
    @vite(['resources/css/painel-base.css', 'resources/css/admin.css', 'resources/js/scripts/admin.js'])
</head>
<body>
    <header class="site-header">
        <div class="header-container">
            <a href="{{ url('/') }}" class="logo-link">
                <img class="logo" src="{{ asset('icons/logo.svg') }}" alt="Ícone ProSaúde">
            </a>

            <nav aria-label="Navegação do painel administrativo">
                <ul class="link-nav" data-campo="abas">
                    <li><a href="#resumo">Visão Geral</a></li>
                    <li><a href="#medicos">Médicos</a></li>
                    <li><a href="#pacientes">Pacientes</a></li>
                    <li><a href="#consultas">Consultas</a></li>
                    <li><a href="#cadastros">Cadastros</a></li>
                    <li><a href="#perfil">Minha Conta</a></li>
                </ul>
            </nav>

            <div class="usuario">
                <div class="usuario-info">
                    <strong data-campo="nome"></strong>
                    <span>Administrador</span>
                </div>
                <a id="btn-sair" class="btn btn-outline btn-sm" href="{{ url('/login-admin') }}">Sair</a>
            </div>
        </div>
    </header>

    <main class="painel-unico">
        <div class="boas-vindas">
            <span class="tag">Painel Administrativo</span>
            <h1 data-campo="saudacao">Olá! 👋</h1>
            <p class="lead">Gerencie médicos, pacientes, consultas e os cadastros da clínica.</p>
        </div>

        <!-- VISÃO GERAL (os números são preenchidos por resources/js/lib/admin/resumo.js) -->
        <section id="resumo" class="aba" aria-label="Visão geral">
            <div class="metricas">
                <div class="card metrica">
                    <span class="rotulo">Médicos Ativos</span>
                    <p class="valor" data-campo="m-medicos">—</p>
                    <p class="detalhe" data-campo="m-medicos-detalhe"></p>
                </div>
                <div class="card metrica">
                    <span class="rotulo">Pacientes</span>
                    <p class="valor" data-campo="m-pacientes">—</p>
                    <p class="detalhe" data-campo="m-pacientes-detalhe"></p>
                </div>
                <div class="card metrica">
                    <span class="rotulo">Consultas Hoje</span>
                    <p class="valor" data-campo="m-hoje">—</p>
                    <p class="detalhe" data-campo="m-hoje-detalhe"></p>
                </div>
                <div class="card metrica">
                    <span class="rotulo">Consultas no Mês</span>
                    <p class="valor" data-campo="m-mes">—</p>
                    <p class="detalhe" data-campo="m-mes-detalhe"></p>
                </div>
            </div>

            <div class="resumo-grade">
                <div class="card">
                    <div class="card-cabecalho">
                        <h2>Consultas por especialidade</h2>
                        <span class="texto-fraco">Mês atual</span>
                    </div>
                    <div class="barras" data-campo="por-especialidade"></div>
                </div>

                <div class="card">
                    <div class="card-cabecalho">
                        <h2>Próximas consultas</h2>
                        <a class="link-acao" href="#consultas">Ver todas</a>
                    </div>
                    <ul class="lista-proximas" data-campo="proximas"></ul>
                </div>
            </div>
        </section>

        <!-- MÉDICOS -->
        <section id="medicos" class="aba card" hidden>
            <div class="card-cabecalho">
                <h2>Médicos</h2>
                <button type="button" id="btn-novo-medico" class="btn btn-primary btn-sm">+ Novo médico</button>
            </div>

            <div class="filtros">
                <input class="entrada" type="search" data-campo="busca-medicos" placeholder="Buscar por nome, CRM ou e-mail" aria-label="Buscar médicos por nome, CRM ou e-mail">
                <select class="entrada" data-campo="filtro-status-medicos" aria-label="Filtrar médicos por situação">
                    <option value="">Todas as situações</option>
                    <option value="ativo">Ativos</option>
                    <option value="inativo">Inativos</option>
                </select>
            </div>

            <div class="tabela-rolagem">
                <table class="tabela tabela-larga">
                    <thead>
                        <tr>
                            <th>Médico</th>
                            <th>Especialidade</th>
                            <th>Contato</th>
                            <th>Situação</th>
                            <th>Consultas</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody data-campo="lista-medicos"></tbody>
                </table>
            </div>
        </section>

        <!-- PACIENTES -->
        <section id="pacientes" class="aba card" hidden>
            <div class="card-cabecalho">
                <h2>Pacientes</h2>
            </div>

            <div class="filtros">
                <input class="entrada" type="search" data-campo="busca-pacientes" placeholder="Buscar por nome, CPF ou e-mail" aria-label="Buscar pacientes por nome, CPF ou e-mail">
            </div>

            <div class="tabela-rolagem">
                <table class="tabela tabela-larga">
                    <thead>
                        <tr>
                            <th>Paciente</th>
                            <th>Contato</th>
                            <th>Convênio</th>
                            <th>Consultas</th>
                            <th>Cadastro</th>
                        </tr>
                    </thead>
                    <tbody data-campo="lista-pacientes"></tbody>
                </table>
            </div>

            <div class="paginacao" data-campo="paginacao-pacientes"></div>
        </section>

        <!-- CONSULTAS -->
        <section id="consultas" class="aba card" hidden>
            <div class="card-cabecalho">
                <h2>Consultas</h2>
                <button type="button" class="btn btn-outline btn-sm" data-campo="limpar-filtros-consultas">Limpar filtros</button>
            </div>

            <div class="filtros filtros-consultas">
                <div class="campo">
                    <label for="f-busca">Paciente ou médico</label>
                    <input id="f-busca" class="entrada" type="search" data-campo="filtro-busca-consultas" placeholder="Nome ou CPF">
                </div>
                <div class="campo">
                    <label for="f-status">Situação</label>
                    <select id="f-status" class="entrada" data-campo="filtro-status-consultas">
                        <option value="">Todas</option>
                        <option value="agendada">Agendadas</option>
                        <option value="realizada">Realizadas</option>
                        <option value="cancelada">Canceladas</option>
                    </select>
                </div>
                <div class="campo">
                    <label for="f-medico">Médico</label>
                    <select id="f-medico" class="entrada" data-campo="filtro-medico-consultas">
                        <option value="">Todos</option>
                    </select>
                </div>
                <div class="campo">
                    <label for="f-inicio">De</label>
                    <input id="f-inicio" class="entrada" type="date" data-campo="filtro-inicio-consultas">
                </div>
                <div class="campo">
                    <label for="f-fim">Até</label>
                    <input id="f-fim" class="entrada" type="date" data-campo="filtro-fim-consultas">
                </div>
            </div>

            <div class="tabela-rolagem">
                <table class="tabela tabela-larga">
                    <thead>
                        <tr>
                            <th>Data e horário</th>
                            <th>Paciente</th>
                            <th>Médico</th>
                            <th>Sala</th>
                            <th>Situação</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody data-campo="lista-consultas"></tbody>
                </table>
            </div>

            <div class="paginacao" data-campo="paginacao-consultas"></div>
        </section>

        <!-- CADASTROS: especialidades, convênios e salas (os cartões são montados por lib/admin/cadastros.js) -->
        <section id="cadastros" class="aba" hidden>
            <div class="card-cabecalho">
                <h2>Cadastros da clínica</h2>
            </div>
            <div class="cadastros-grade" data-campo="cadastros"></div>
        </section>

        <!-- MINHA CONTA -->
        <section id="perfil" class="aba" hidden>
            <div class="card-cabecalho">
                <h2>Minha Conta</h2>
            </div>
            <!-- Os formulários são montados por resources/js/lib/perfil.js -->
            <div class="perfil-grade" data-campo="perfil"></div>
        </section>
    </main>

    <footer class="site-footer">
        <p>© {{ date('Y') }} ProSaúde. Todos os direitos reservados.</p>
    </footer>
</body>
</html>
