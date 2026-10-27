<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ProSaúde - Painel do Médico</title>
    <!-- Chart.js para renderização de gráficos -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    @vite(['resources/css/painel-base.css', 'resources/css/medico.css', 'resources/js/scripts/medico.js'])
</head>
<body>
    <header class="site-header">
        <div class="header-container">
            <a href="{{ url('/') }}" class="logo-link">
                <img class="logo" src="{{ asset('icons/logo.svg') }}" alt="Ícone ProSaúde">
            </a>

            <nav aria-label="Navegação do painel">
                <ul class="link-nav">
                    <li><a href="#dashboard">Visão Geral</a></li>
                    <li><a href="#agenda">Agenda do Dia</a></li>
                    <li><a href="#pacientes">Meus Pacientes</a></li>
                    <li><a href="#relatorios">Relatórios</a></li>
                </ul>
            </nav>

            <div class="usuario">
                <!-- A foto (images/equipe-<nome>.jpg) é definida por resources/js/scripts/medico.js -->
                <img class="usuario-foto" data-campo="foto" data-base="{{ asset('images') }}" src="" alt="Foto do médico" hidden>
                <div class="usuario-info">
                    <strong data-campo="nome"></strong>
                    <span data-campo="especialidade"></span>
                </div>
                <a id="btn-sair" class="btn btn-outline btn-sm" href="{{ url('/login-medico') }}">Sair</a>
            </div>
        </div>
    </header>

    <main class="painel">
        <!-- BARRA LATERAL DE AÇÕES RÁPIDAS -->
        <aside class="painel-lateral">
            <div class="card card-lateral">
                <h3>Ações Rápidas</h3>
                <div class="acoes-rapidas">
                    <button type="button" id="btn-encaixe" class="btn btn-primary btn-block btn-esquerda">+ Nova Consulta Encaixe</button>
                    <button type="button" id="btn-receita" class="btn btn-outline btn-block btn-esquerda">Emitir Receita Médica</button>
                    <button type="button" id="btn-exames" class="btn btn-outline btn-block btn-esquerda">Solicitar Exames</button>
                    <button type="button" id="btn-atestado" class="btn btn-outline btn-block btn-esquerda">Atestados / Laudos</button>
                </div>
            </div>

            <div class="card card-destaque card-lateral">
                <h3>Próximo Paciente</h3>
                <p><strong data-campo="proximo-nome"></strong></p>
                <p data-campo="proximo-horario"></p>
                <p data-campo="proximo-detalhe"></p>
                <button type="button" class="btn btn-primary btn-block" data-campo="proximo-botao" hidden>Chamar Paciente</button>
            </div>
        </aside>

        <!-- CONTEÚDO PRINCIPAL DO PAINEL -->
        <div class="painel-conteudo">
            <!-- BOAS VINDAS E CARDS DE METRICAS -->
            <section id="dashboard">
                <div class="boas-vindas">
                    <span class="tag">Painel do Médico</span>
                    <h1 data-campo="saudacao">Olá! 👋</h1>
                    <p class="lead" data-campo="resumo-dia">Acompanhe sua agenda, seus pacientes e seus atendimentos.</p>
                </div>

                <div class="metricas">
                    <div class="card metrica">
                        <span class="rotulo">Consultas Hoje</span>
                        <p class="valor" data-campo="metrica-hoje">—</p>
                        <p class="detalhe" data-campo="metrica-hoje-detalhe"></p>
                    </div>
                    <div class="card metrica">
                        <span class="rotulo">Taxa de Presença</span>
                        <p class="valor" data-campo="metrica-presenca">—</p>
                        <p class="detalhe" data-campo="metrica-presenca-detalhe"></p>
                    </div>
                    <div class="card metrica">
                        <span class="rotulo">Tempo Médio / Consulta</span>
                        <p class="valor">—</p>
                        <p class="detalhe">Sem registro de duração das consultas</p>
                    </div>
                    <div class="card metrica">
                        <span class="rotulo">Avaliação Média</span>
                        <p class="valor">—</p>
                        <p class="detalhe">Sem avaliações de pacientes ainda</p>
                    </div>
                </div>
            </section>

            <!-- AGENDA DO DIA -->
            <section id="agenda" class="card">
                <div class="card-cabecalho">
                    <h2 data-campo="titulo-agenda">Agenda de Hoje</h2>
                    <input class="entrada entrada-data" type="date" value="{{ date('Y-m-d') }}" aria-label="Data da agenda">
                </div>

                <div class="tabela-rolagem">
                    <table class="tabela">
                        <thead>
                            <tr>
                                <th>Horário</th>
                                <th>Paciente</th>
                                <th>Motivo / Tipo</th>
                                <th>Status</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <!-- As linhas da agenda são montadas por resources/js/scripts/medico.js -->
                        <tbody data-campo="agenda"></tbody>
                    </table>
                </div>
            </section>

            <!-- MEUS PACIENTES -->
            <section id="pacientes" class="card">
                <div class="card-cabecalho">
                    <h2>Meus Pacientes</h2>
                    <input class="entrada entrada-busca" type="search" data-campo="filtro-pacientes" placeholder="Filtrar por nome" aria-label="Filtrar pacientes por nome" hidden>
                </div>
                <!-- Os cartões dos pacientes são montados por resources/js/scripts/medico.js -->
                <div class="grade-pacientes" data-campo="lista-pacientes"></div>
            </section>

            <!-- SEÇÃO DE GRÁFICOS -->
            <section id="relatorios" class="card">
                <div class="card-cabecalho">
                    <h2>Indicadores Atendimento &amp; Atividades</h2>
                </div>
                <div class="graficos">
                    <div class="grafico">
                        <h3 class="item-titulo">Volume de Consultas (Últimos 6 meses)</h3>
                        <canvas id="chartConsultas"></canvas>
                    </div>
                    <div class="grafico">
                        <h3 class="item-titulo">Distribuição por Faixa Etária</h3>
                        <canvas id="chartPacientes"></canvas>
                    </div>
                </div>
            </section>
        </div>
    </main>

    <footer class="site-footer">
        <p>© {{ date('Y') }} ProSaúde. Todos os direitos reservados.</p>
    </footer>

    <!-- Documento (atestado/laudo) montado por medico.js e impresso com window.print() -->
    <div id="area-impressao" aria-hidden="true"></div>
</body>
</html>
