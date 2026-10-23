<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ProSaúde - Minha Área (Paciente)</title>
    @vite('resources/css/paciente.css')
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
                    <strong>Helena Marques</strong>
                    <span>CPF: 123.456.789-00</span>
                </div>
                <a href="{{ url('/login') }}">Sair</a>
            </div>
        </div>
    </header>

    <main>
        <!-- BARRA LATERAL -->
        <aside>
            <button>+ Agendar Nova Consulta</button>
            
            <div>
                <h3>Plano ProSaúde</h3>
                <p><strong>Nº Carteira:</strong> 987654321-0</p>
                <p><strong>Titular:</strong> Helena Marques</p>
                <span>Ativo</span>
            </div>

            <div>
                <h4>Lembretes de Medicamentos</h4>
                <ul>
                    <li>
                        <strong>Omeprazol 20mg</strong>
                        <p>1 comprimido às 08:00 (Jejum)</p>
                    </li>
                    <li>
                        <strong>Vitamina D 2000UI</strong>
                        <p>1 cápsula no Almoço</p>
                    </li>
                </ul>
            </div>
        </aside>

        <!-- CONTEÚDO PRINCIPAL -->
        <section>
            <!-- BOAS VINDAS -->
            <div>
                <h2>Olá, Helena! 👋</h2>
                <p>Acompanhe aqui seus próximos agendamentos, exames e receitas médicas.</p>
            </div>

            <!-- PROXIMAS CONSULTAS -->
            <section id="consultas">
                <h2>Próxima Consulta</h2>
                <div>
                    <div>
                        <h3>Dra. Marina Ferraz</h3>
                        <p>Cardiologia</p>
                        <p><strong>Data:</strong> 15 de Outubro de 2026 às 10:30</p>
                        <p><strong>Local:</strong> ProSaúde — Rua das Acadias, 482 (Erechim, RS)</p>
                    </div>
                    <div>
                        <button>Reagendar</button>
                        <button>Cancelar</button>
                    </div>
                </div>
            </section>

            <!-- HISTÓRICO RECENTE DE EXAMES E RECEITAS -->
            <section id="exames">
                <h2>Exames e Receitas Recentes</h2>
                <div>
                    <div>
                        <div>📄</div>
                        <div>
                            <h4>Hemograma Completo</h4>
                            <p>Realizado em: 28/09/2026</p>
                            <a href="#">Download Resultado (PDF)</a>
                        </div>
                    </div>
                    <div>
                        <div>💊</div>
                        <div>
                            <h4>Receita Médica Digital</h4>
                            <p>Emitida por: Dr. Rafael Tavares</p>
                            <a href="#">Ver Receita Digital</a>
                        </div>
                    </div>
                    <div>
                        <div>🫀</div>
                        <div>
                            <h4>Eletrocardiograma</h4>
                            <p>Realizado em: 10/08/2026</p>
                            <a href="#">Download Laudo (PDF)</a>
                        </div>
                    </div>
                </div>
            </section>

            <!-- EVOLUÇÃO DE INDICADORES DE SAÚDE DO PACIENTE -->
            <section id="historico">
                <h2>Seus Indicadores de Saúde</h2>
                <div>
                    <div>
                        <h3>Evolução da Pressão Arterial (Sistólica/Diastólica)</h3>
                        <canvas></canvas>
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