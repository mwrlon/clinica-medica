<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ProSaúde - Início</title>
    @vite('resources/css/index.css')
</head>
<body>
    <header>
        <div class="header-container">
            <div>
                <a href="#inicio">
                    <img class="logo" src="{{ asset('icons/logo.svg') }}" alt="Ícone ProSaúde">
                </a>
            </div>

            <nav class="nav-bar">
                <ul class="link-nav">
                    <li><a href="#especialidades">Especialidades</a></li>
                    <li><a href="#como-funciona">Como funciona</a></li>
                    <li><a href="#equipe">Equipe</a></li>
                    <li><a href="#depoimentos">Depoimentos</a></li>
                    <li><a href="#contato">Contato</a></li>
                </ul>
            </nav>

            <div class="botao-agendar">
                <a href="{{ url('/login') }}"><button>Agendar Consulta</button></a>
            </div>
        </div>
    </header>

    <main>

        <!-- PRINCIPAL -->

        <section id="inicio">
            <div>
                <div>
                    <h1>Cuidado médico com a clareza que a sua saúde merece</h1>
                    <p>Consultas, exames e acompanhamento contínuo reunidos em um só lugar, com especialistas prontos para cuidar de cada fase da sua vida — sem filas de espera nem respostas incompletas.</p>
                </div>

                <div>
                    <a href="{{ url('/login') }}"><button>Agendar Consulta</button></a>
                    <a href="#especialidades"><button>Conhecer Especialidades</button></a>
                </div>

                <div>
                    <div>
                        <h3>18 anos</h3>
                        <p>cuidando de famílias da nossa região</p>
                    </div>
                    <div>
                        <h3>32</h3>
                        <p>especialidades sob o mesmo teto</p>
                    </div>
                    <div>
                        <h3>98%</h3>
                        <p>dos pacientes recomendam a clínica</p>
                    </div>

                </div>

                <div>
                    <img src="{{ asset('images/hero-agendamento.svg') }}" alt="Ilustração de um card de agendamento com data, horário e especialista confirmados">
                </div>
            </div>
        </section>

        <!-- ESPECIALIDADES -->

        <section id="especialidades">
            <div>
                <h2>Especialidades</h2>
                <p>Uma equipe multidisciplinar para cuidar de você e da sua família em cada etapa da vida.</p>
            </div>

            <div>
                <ul>
                    <li>
                        <span name="barra"></span>
                        <span><img src="{{ asset('icons/clinico-geral.svg') }}" alt="Icone clinico geral"></span>
                        <div>
                            <h3>Clínica geral</h3>
                            <p>Acompanhamento contínuo da sua saúde, do check-up de rotina aos encaminhamentos.</p>
                        </div>
                    </li>
                    <li>
                        <span name="barra"></span>
                        <span><img src="{{ asset('icons/crianca.svg') }}" alt="Icone criança"></span>
                        <div>
                            <h3>Pediatria</h3>
                            <p>Cuidado atencioso do nascimento à adolescência, com vacinação e curva de crescimento.</p>
                        </div>
                    </li>
                    <li>
                        <span name="barra"></span>
                        <span><img src="{{ asset('icons/osso.svg') }}" alt="Icone osso"></span>
                        <div>
                            <h3>Ortopedia</h3>
                            <p>Avaliação de ossos, articulações e músculos, da fisioterapia à cirurgia.</p>
                        </div>
                    </li>
                    <li>
                        <span name="barra"></span>
                        <span><img src="{{ asset('icons/calendario.svg') }}" alt="Icone calendario"></span>
                        <div>
                            <h3>Ginecologia</h3>
                            <p>Saúde da mulher em todas as fases, do planejamento familiar à menopausa.</p>
                        </div>
                    </li>
                    <li>
                        <span name="barra"></span>
                        <span><img src="{{ asset('icons/coracao.svg') }}" alt="Icone coração"></span>
                        <div>
                            <h3>Cardiologia</h3>
                            <p>Prevenção e tratamento de doenças do coração, com exames no mesmo endereço.</p>
                        </div>
                    </li>
                    <li>
                        <span name="barra"></span>
                        <span><img src="{{ asset('icons/gota.svg') }}" alt="Icone gota"></span>
                        <div>
                            <h3>Dermatologia</h3>
                            <p>Diagnóstico de pele, cabelo e unhas, incluindo mapeamento de manchas e sinais.</p>
                        </div>
                    </li>
                    <li>
                        <span name="barra"></span>
                        <span><img src="{{ asset('icons/nutricao.svg') }}" alt="Icone nutricao"></span>
                        <div>
                            <h3>Nutrição</h3>
                            <p>Planos alimentares personalizados para emagrecimento, performance e bem-estar.</p>
                        </div>
                    </li>
                    <li>
                        <span name="barra"></span>
                        <span><img src="{{ asset('icons/psicologia.svg') }}" alt="Icone psicologia"></span>
                        <div>
                            <h3>Psicologia</h3>
                            <p>Espaço de escuta e acompanhamento terapêutico para ansiedade, luto e rotina.</p>
                        </div>
                    </li>
                </ul>
            </div>
        </section>

        <!-- COMO FUNCIONA -->

        <section id="como-funciona">
            <div>
                <h2>Como funciona</h2>
                <p>Do agendamento ao acompanhamento, sem burocracia entre uma etapa e outra.</p>
            </div>

            <div>
                <div>
                    <div>1</div>
                    <h3>Agende</h3>
                    <p>Escolha a especialidade e o horário pelo telefone, WhatsApp ou presencialmente.</p>
                </div>

                <div>
                    <div>2</div>
                    <h3>Consulte</h3>
                    <p>Seja atendido por um especialista com acesso ao seu histórico completo.</p>
                </div>

                <div>
                    <div>3</div>
                    <h3>Acompanhe</h3>
                    <p>Receba exames, receitas e retornos organizados em um único prontuário.</p>
                </div>
            </div>
        </section>

        <!-- EQUIPE -->

        <section id="equipe">
            <div>
                <h2>Equipe médica</h2>
                <p>Profissionais experientes, escolhidos pela dedicação tanto quanto pela técnica.</p>
            </div>

            <div>
                <div>
                    <div>
                        <img src="{{ asset('images/equipe-marina-ferraz.jpg') }}" alt="Foto da Dra. Marina Ferraz">
                        <p>Dra. Marina Ferraz — Cardiologia</p>
                    </div>
                    <div>
                        <img src="{{ asset('images/equipe-rafael-tavares.jpg') }}" alt="Foto do Dr. Rafael Tavares">
                        <p>Dr. Rafael Tavares — Pediatria</p>
                    </div>
                    <div>
                        <img src="{{ asset('images/equipe-camila-lopes.jpg') }}" alt="Foto da Dra. Camila Lopes">
                        <p>Dra. Camila Lopes — Dermatologia</p>
                    </div>
                    <div>
                        <img src="{{ asset('images/equipe-joao-salles.jpg') }}" alt="Foto do Dr. João Salles">
                        <p>Dr. João Salles — Ortopedia</p>
                    </div>
                    <div>
                        <img src="{{ asset('images/equipe-helena-prado.jpg') }}" alt="Foto da Dra. Helena Prado">
                        <p>Dra. Helena Prado — Ginecologia</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- DEPOIMENTOS -->

        <section id="depoimentos">
            <div>
                <h3>O que dizem nossos pacientes</h3>
                <p>Histórias reais de quem já passou pela ProSaúde.</p>
            </div>

            <div>

                <div>
                    <h3>Helena Marques</h3>
                    <p>“Fui atendida no mesmo dia em que liguei, e pela primeira vez saí de uma consulta com todas as minhas dúvidas respondidas.”</p>
                </div>

                <div>
                    <h3>Eduardo Nogueira</h3>
                    <p>“O acompanhamento entre exames e retorno é muito mais organizado do que em qualquer outra clínica que já frequentei.”</p>
                </div>

                <div>
                    <h3>Patrícia Ramos</h3>
                    <p>“Levei meu filho para a pediatria e o cuidado com a família toda foi além do que eu esperava.”</p>
                </div>

                <div>
                    <h3>Marcos Vinícius</h3>
                    <p>“Marquei minha consulta pelo WhatsApp e fui atendido no mesmo dia. Recomendo para toda a minha equipe.”</p>
                </div>

            </div>
        </section>

        <!-- NÚMEROS -->

        <section id="numeros">
            <div>
                <div>
                    <h3>18</h3>
                    <p>anos de atividade contínua</p>
                </div>
                <div>
                    <h3>32</h3>
                    <p>especialidades médicas</p>
                </div>
                <div>
                    <h3>60 mil+</h3>
                    <p>consultas realizadas</p>
                </div>
                <div>
                    <h3>98%</h3>
                    <p>de satisfação dos pacientes</p>
                </div>
            </div>
        </section>

        <!-- CONTATO -->

        <section id="contato">
            <div>
                <h2>Pronto para cuidar da sua saúde?</h2>
                <p>Nossa recepção responde rapidamente por telefone ou WhatsApp para encontrar o melhor horário para você.</p>

                <div>
                    <a href="{{ url('/login') }}"><button>Agendar Consulta</button></a>
                    <a href="#especialidades"><button>Ver Especialidades</button></a>
                </div>
            </div>

            <div>
                <p>Telefone / WhatsApp: (51) 99999-9999</p>
                <p>E-mail: contato@prosaude.com.br</p>
                <p>Endereço: Rua das Acácias, 482 — Erechim, RS</p>
                <p>Horário: Segunda a sexta, 7h às 19h · Sábado, 8h às 12h</p>
            </div>
        </section>

    </main>

    <footer>
        <div>
            <img src="{{ asset('icons/logo-e-texto.svg') }}" alt="Logo ProSaúde">
        </div>

        <div>
            <div>
                <p>Navegação</p>
                <a href="#especialidades">Especialidades</a>
                <a href="#como-funciona">Como funciona</a>
                <a href="#equipe">Equipe</a>
                <a href="#depoimentos">Depoimentos</a>
            </div>

            <div>
                <p>Contato</p>
                <a href="tel:+555199999999">(51) 99999-9999</a>
                <a href="mailto:contato@prosaude.com.br">contato@prosaude.com.br</a>
                <a href="#contato">Rua das Acácias, 482 — Erechim, RS</a>
                <a href="#contato">Segunda a sexta, 7h às 19h</a>
            </div>

            <div></div>

            <div>
                <p>© 2026 ProSaúde. Todos os direitos reservados.</p>
                <p>CNPJ 00.000.000/0001-00</p>
            </div>
            
        </div>
        
    </footer>
</body>
</html>