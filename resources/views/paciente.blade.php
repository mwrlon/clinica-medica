<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ProSaúde - Minha Área (Paciente)</title>
    @vite(['resources/css/paciente.css', 'resources/js/scripts/paciente.js'])
    <!-- Chart.js para visualização da evolução do paciente -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <header>
        <div>
            <a href="{{ url('/') }}">
                <img src="{{ asset('icons/logo.svg') }}" alt="Ícone ProSaúde">
            </a>
            <nav>
                <ul>
                    <li><a href="#inicio">Início</a></li>
                    <li><a href="#consultas">Minhas Consultas</a></li>
                    <li><a href="#exames">Exames e Receitas</a></li>
                    <li><a href="#historico">Histórico e Saúde</a></li>
                </ul>
            </nav>
            <div>
                <div>
                    <strong data-campo="nome"></strong>
                    <span data-campo="cpf"></span>
                </div>
                <a id="btn-sair" href="{{ url('/login') }}">Sair</a>
            </div>
        </div>
    </header>

    <main>
        <!-- BARRA LATERAL -->
        <aside>
            <button>+ Agendar Nova Consulta</button>
            
            <div>
                <h3>Plano ProSaúde</h3>
                <p data-campo="convenio"></p>
                <p data-campo="titular"></p>
                <span>Ativo</span>
            </div>

            <div>
                <h4>Lembretes de Medicamentos</h4>
                <ul data-campo="lembretes"></ul>
            </div>
        </aside>

        <!-- CONTEÚDO PRINCIPAL -->
        <section>
            <!-- BOAS VINDAS -->
            <div>
                <h2 data-campo="saudacao">Olá! 👋</h2>
                <p>Acompanhe aqui seus próximos agendamentos, exames e receitas médicas.</p>
            </div>

            <!-- PROXIMAS CONSULTAS -->
            <section id="consultas">
                <h2 data-campo="titulo-consultas">Próxima Consulta</h2>
                <!-- Os cartões das consultas são montados por resources/js/scripts/paciente.js -->
            </section>

            <!-- HISTÓRICO RECENTE DE EXAMES E RECEITAS -->
            <section id="exames">
                <h2>Exames e Receitas Recentes</h2>
                <!-- Os cartões de exames e receitas são montados por resources/js/scripts/paciente.js -->
                <div data-campo="lista-exames"></div>
            </section>

            <!-- EVOLUÇÃO DE INDICADORES DE SAÚDE DO PACIENTE -->
            <section id="historico">
                <h2>Seus Indicadores de Saúde</h2>
                <div>
                    <div>
                        <h3>Evolução da Pressão Arterial (Sistólica/Diastólica)</h3>
                        <!-- O sistema ainda não registra medições de pressão: sem gráfico, evita uma caixa vazia -->
                        <p>Ainda não há medições de pressão arterial registradas.</p>
                    </div>
                </div>
            </section>
        </section>
    </main>

    <footer>
        <p>© {{ date('Y') }} ProSaúde. Todos os direitos reservados.</p>
    </footer>


</body>
</html>