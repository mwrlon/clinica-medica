<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ProSaúde - Minha Área (Paciente)</title>
    @vite(['resources/css/painel-base.css', 'resources/css/paciente.css', 'resources/js/scripts/paciente.js'])
</head>
<body>
    <header class="site-header">
        <div class="header-container">
            <a href="{{ url('/') }}" class="logo-link">
                <img class="logo" src="{{ asset('icons/logo.svg') }}" alt="Ícone ProSaúde">
            </a>

            <nav aria-label="Navegação do painel">
                <ul class="link-nav">
                    <li><a href="#inicio">Início</a></li>
                    <li><a href="#consultas">Minhas Consultas</a></li>
                    <li><a href="#exames">Exames e Receitas</a></li>
                    <li><a href="#historico">Histórico e Saúde</a></li>
                </ul>
            </nav>

            <div class="usuario">
                <div class="usuario-info">
                    <strong data-campo="nome"></strong>
                    <span data-campo="cpf"></span>
                </div>
                <a id="btn-sair" class="btn btn-outline btn-sm" href="{{ url('/login') }}">Sair</a>
            </div>
        </div>
    </header>

    <main class="painel">
        <!-- BARRA LATERAL -->
        <aside class="painel-lateral">
            <button type="button" id="btn-agendar" class="btn btn-primary btn-block">+ Agendar Nova Consulta</button>

            <div class="card card-lateral">
                <h3>Plano ProSaúde</h3>
                <p data-campo="convenio"></p>
                <p data-campo="titular"></p>
                <span class="tag tag-ok">Ativo</span>
            </div>

            <div class="card card-lateral">
                <h3>Lembretes de Medicamentos</h3>
                <ul class="lista-simples" data-campo="lembretes"></ul>
            </div>
        </aside>

        <!-- CONTEÚDO PRINCIPAL -->
        <div class="painel-conteudo">
            <!-- BOAS VINDAS -->
            <section id="inicio" class="boas-vindas">
                <span class="tag">Área do Paciente</span>
                <h1 data-campo="saudacao">Olá! 👋</h1>
                <p class="lead">Acompanhe aqui seus próximos agendamentos, exames e receitas médicas.</p>
            </section>

            <!-- PROXIMAS CONSULTAS -->
            <section id="consultas" class="card">
                <div class="card-cabecalho">
                    <h2 data-campo="titulo-consultas">Próxima Consulta</h2>
                </div>
                <!-- Os cartões das consultas são montados por resources/js/scripts/paciente.js -->
                <div data-campo="lista-consultas"></div>
            </section>

            <!-- HISTÓRICO RECENTE DE EXAMES E RECEITAS -->
            <section id="exames">
                <div class="card-cabecalho">
                    <h2>Exames e Receitas Recentes</h2>
                </div>
                <!-- Os cartões de exames e receitas são montados por resources/js/scripts/paciente.js -->
                <div class="grade-exames" data-campo="lista-exames"></div>
            </section>

            <!-- EVOLUÇÃO DE INDICADORES DE SAÚDE DO PACIENTE -->
            <section id="historico" class="card">
                <div class="card-cabecalho">
                    <h2>Seus Indicadores de Saúde</h2>
                </div>
                <h3 class="item-titulo">Evolução da Pressão Arterial (Sistólica/Diastólica)</h3>
                <!-- O sistema ainda não registra medições de pressão: sem gráfico, evita uma caixa vazia -->
                <p class="vazio">Ainda não há medições de pressão arterial registradas.</p>
            </section>
        </div>
    </main>

    <footer class="site-footer">
        <p>© {{ date('Y') }} ProSaúde. Todos os direitos reservados.</p>
    </footer>
</body>
</html>
