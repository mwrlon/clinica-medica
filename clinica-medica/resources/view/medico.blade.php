<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ProSaúde - Painel do Médico</title>
    <link rel="stylesheet" href="{{ asset('css/medico.css') }}">
    <!-- Chart.js para renderização de gráficos -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <header>
        <div>
            <a href="{{ url('/') }}">
                <img src="{{ asset('icons/logo-prosaude.svg') }}" alt="Ícone ProSaúde">
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
                <img src="{{ asset('images/equipe-rafael-tavares.jpg') }}" alt="Foto Dr. Rafael Tavares">
                <div>
                    <strong>Dr. Rafael Tavares</strong>
                    <span>Pediatra - CRM/RS 45678</span>
                </div>
                <a href="{{ url('/login') }}">Sair</a>
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
                <p><strong>Lucas Silva (8 anos)</strong></p>
                <p>Horário: 14:00 (Em 15 min)</p>
                <p>Tipo: Consulta de Rotina</p>
                <button>Chamar Paciente</button>
            </div>
        </aside>

        <!-- CONTEÚDO PRINCIPAL DO PAINEL -->
        <section>
            <!-- CARDS DE METRICAS E RESUMO -->
            <div>
                <div>
                    <h3>Consultas Hoje</h3>
                    <p>12</p>
                    <span>4 concluídas · 8 pendentes</span>
                </div>
                <div>
                    <h3>Taxa de Presença</h3>
                    <p>95%</p>
                    <span>+2% em relação ao mês anterior</span>
                </div>
                <div>
                    <h3>Tempo Médio / Consulta</h3>
                    <p>28 min</p>
                    <span>Dentro da meta (30 min)</span>
                </div>
                <div>
                    <h3>Avaliação Média</h3>
                    <p>4.9 / 5.0</p>
                    <span>Baseado em 48 opiniões</span>
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
                    <h2>Agenda de Hoje</h2>
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
                    <tbody>
                        <tr>
                            <td>08:30</td>
                            <td>Ana Clara Souza</td>
                            <td>Retorno Exames</td>
                            <td><span>Concluído</span></td>
                            <td><button>Ver Prontuário</button></td>
                        </tr>
                        <tr>
                            <td>09:30</td>
                            <td>Gabriel Santos</td>
                            <td>Primeira Consulta</td>
                            <td><span>Concluído</span></td>
                            <td><button>Ver Prontuário</button></td>
                        </tr>
                        <tr>
                            <td>14:00</td>
                            <td>Lucas Silva</td>
                            <td>Acompanhamento Pediátrico</td>
                            <td><span>Aguardando na Recepção</span></td>
                            <td><button>Atender Agora</button></td>
                        </tr>
                        <tr>
                            <td>15:00</td>
                            <td>Mariana Oliveira</td>
                            <td>Avaliação Geral</td>
                            <td><span>Agendado</span></td>
                            <td><button>Ver Prontuário</button></td>
                        </tr>
                        <tr>
                            <td>16:00</td>
                            <td>Enzo Gabriel Ferraz</td>
                            <td>Vacinação e Peso</td>
                            <td><span>Agendado</span></td>
                            <td><button>Ver Prontuário</button></td>
                        </tr>
                    </tbody>
                </table>
            </section>
        </section>
    </main>

    <footer>
        <p>© {{ date('Y') }} ProSaúde. Todos os direitos reservados.</p>
    </footer>

</body>
</html>