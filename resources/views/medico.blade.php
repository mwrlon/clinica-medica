<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ProSaúde - Painel do Médico</title>
    <!-- Chart.js para renderização de gráficos -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    @vite(['resources/css/medico.css', 'resources/js/scripts/medico.js'])
</head>
<body>
    <header>
        <div>
            <a href="{{ url('/') }}">
                <img src="{{ asset('icons/logo.svg') }}" alt="Ícone ProSaúde">
            </a>
            <nav>
                <ul>
                    <li><a href="#dashboard" class="active">Visão Geral</a></li>
                    <li><a href="#agenda">Agenda do Dia</a></li>
                    <li><a href="#pacientes">Meus Pacientes</a></li>
                    <li><a href="#relatorios">Relatórios</a></li>
                </ul>
            </nav>
            <div>
                <!-- A foto (images/equipe-<nome>.jpg) é definida por resources/js/scripts/medico.js -->
                <img data-campo="foto" data-base="{{ asset('images') }}" src="" alt="Foto do médico" hidden>
                <div>
                    <strong data-campo="nome"></strong>
                    <span data-campo="especialidade"></span>
                </div>
                <a id="btn-sair" href="{{ url('/login-medico') }}">Sair</a>
            </div>
        </div>
    </header>

    <main>
        <!-- BARRA LATERAL DE AÇÕES RÁPIDAS -->
        <aside>
            <h3>Ações Rápidas</h3>
            <ul>
                <li><button>+ Nova Consulta Encaixe</button></li>
                <li><button>Emitir Receita Médica</button></li>
                <li><button>Solicitar Exames</button></li>
                <li><button>Atestados / Laudos</button></li>
            </ul>

            <div>
                <h4>Próximo Paciente</h4>
                <p><strong data-campo="proximo-nome"></strong></p>
                <p data-campo="proximo-horario"></p>
                <p data-campo="proximo-detalhe"></p>
                <button data-campo="proximo-botao">Chamar Paciente</button>
            </div>
        </aside>

        <!-- CONTEÚDO PRINCIPAL DO PAINEL -->
        <section>
            <!-- CARDS DE METRICAS E RESUMO -->
            <div>
                <div>
                    <h3>Consultas Hoje</h3>
                    <p data-campo="metrica-hoje">—</p>
                    <span data-campo="metrica-hoje-detalhe"></span>
                </div>
                <div>
                    <h3>Taxa de Presença</h3>
                    <p data-campo="metrica-presenca">—</p>
                    <span data-campo="metrica-presenca-detalhe"></span>
                </div>
                <div>
                    <h3>Tempo Médio / Consulta</h3>
                    <p>—</p>
                    <span>Sem registro de duração das consultas</span>
                </div>
                <div>
                    <h3>Avaliação Média</h3>
                    <p>—</p>
                    <span>Sem avaliações de pacientes ainda</span>
                </div>
            </div>

            <!-- SEÇÃO DE GRÁFICOS -->
            <section id="relatorios">
                <h2>Indicadores Atendimento & Atividades</h2>
                <div>
                    <div>
                        <h3>Volume de Consultas (Últimos 6 meses)</h3>
                        <canvas id="chartConsultas"></canvas>
                    </div>
                    <div>
                        <h3>Distribuição por Faixa Etária</h3>
                        <canvas id="chartPacientes"></canvas>
                    </div>
                </div>
            </section>

            <!-- AGENDA DO DIA -->
            <section id="agenda">
                <div>
                    <h2 data-campo="titulo-agenda">Agenda de Hoje</h2>
                    <input type="date" value="{{ date('Y-m-d') }}">
                </div>

                <table>
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
            </section>
        </section>
    </main>

    <footer>
        <p>© {{ date('Y') }} ProSaúde. Todos os direitos reservados.</p>
    </footer>


</body>
</html>