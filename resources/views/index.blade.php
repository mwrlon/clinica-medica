<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ProSaúde - Início</title>
    @vite('resources/css/index.css')
</head>
<body>
    <header class="site-header">
        <div class="header-container">
            <a href="#inicio" class="logo-link">
                <img class="logo" src="{{ asset('icons/logo.svg') }}" alt="Ícone ProSaúde">
            </a>

            <nav class="nav-bar" aria-label="Navegação principal">
                <ul class="link-nav">
                    <li><a href="#especialidades">Especialidades</a></li>
                    <li><a href="#como-funciona">Como funciona</a></li>
                    <li><a href="#equipe">Equipe</a></li>
                    <li><a href="#depoimentos">Depoimentos</a></li>
                    <li><a href="#contato">Contato</a></li>
                </ul>
            </nav>

            <a class="btn btn-primary" href="{{ url('/login') }}">Agendar Consulta</a>
        </div>
    </header>

    <main>

        <!-- PRINCIPAL -->
        <section id="inicio" class="hero">
            <div class="container hero-grid">
                <div class="hero-text">
                    <h1>Cuidado médico com a clareza que a sua saúde merece</h1>
                    <p class="hero-lead">Consultas, exames e acompanhamento contínuo reunidos em um só lugar, com especialistas prontos para cuidar de cada fase da sua vida — sem filas de espera nem respostas incompletas.</p>

                    <div class="actions">
                        <a class="btn btn-primary" href="{{ url('/login') }}">Agendar Consulta</a>
                        <a class="btn btn-outline" href="#especialidades">Conhecer Especialidades</a>
                    </div>

                    <div class="hero-stats">
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
                </div>

                <div class="hero-visual" aria-hidden="true">
                    <div class="float-card card-appt">
                        <span class="tag">Confirmada</span>
                        <div class="doc-row">
                            <div class="avatar-mono">MF</div>
                            <div>
                                <h4>Dra. Marina Ferraz</h4>
                                <span>Cardiologia</span>
                            </div>
                        </div>
                        <div class="when">
                            <div>
                                <strong>Qui, 11 set — 14h30</strong>
                                <small>Consultório 3 · Presencial</small>
                            </div>
                            <span class="dot-confirm"></span>
                        </div>
                    </div>

                    <div class="float-card card-exams">
                        <div class="exam-row"><span>Hemograma</span><div class="bar"><i style="--w:100%"></i></div></div>
                        <div class="exam-row"><span>Colesterol</span><div class="bar"><i style="--w:100%"></i></div></div>
                        <div class="exam-row"><span>Vitamina D</span><div class="bar"><i style="--w:60%"></i></div></div>
                    </div>
                </div>
            </div>
        </section>

        <!-- ESPECIALIDADES -->
        <section id="especialidades">
            <div class="container">
                <div class="section-head">
                    <h2>Especialidades</h2>
                    <p>Uma equipe multidisciplinar para cuidar de você e da sua família em cada etapa da vida.</p>
                </div>

                <ul class="spec-grid">
                    <li class="spec-item">
                        <span class="spec-bar"></span>
                        <span class="spec-icon"><img src="{{ asset('icons/clinico-geral.svg') }}" alt="Icone clinico geral"></span>
                        <div>
                            <h3>Clínica geral</h3>
                            <p>Acompanhamento contínuo da sua saúde, do check-up de rotina aos encaminhamentos.</p>
                        </div>
                    </li>
                    <li class="spec-item">
                        <span class="spec-bar"></span>
                        <span class="spec-icon"><img src="{{ asset('icons/crianca.svg') }}" alt="Icone criança"></span>
                        <div>
                            <h3>Pediatria</h3>
                            <p>Cuidado atencioso do nascimento à adolescência, com vacinação e curva de crescimento.</p>
                        </div>
                    </li>
                    <li class="spec-item">
                        <span class="spec-bar"></span>
                        <span class="spec-icon"><img src="{{ asset('icons/osso.svg') }}" alt="Icone osso"></span>
                        <div>
                            <h3>Ortopedia</h3>
                            <p>Avaliação de ossos, articulações e músculos, da fisioterapia à cirurgia.</p>
                        </div>
                    </li>
                    <li class="spec-item">
                        <span class="spec-bar"></span>
                        <span class="spec-icon"><img src="{{ asset('icons/calendario.svg') }}" alt="Icone calendario"></span>
                        <div>
                            <h3>Ginecologia</h3>
                            <p>Saúde da mulher em todas as fases, do planejamento familiar à menopausa.</p>
                        </div>
                    </li>
                    <li class="spec-item">
                        <span class="spec-bar"></span>
                        <span class="spec-icon"><img src="{{ asset('icons/coracao.svg') }}" alt="Icone coração"></span>
                        <div>
                            <h3>Cardiologia</h3>
                            <p>Prevenção e tratamento de doenças do coração, com exames no mesmo endereço.</p>
                        </div>
                    </li>
                    <li class="spec-item">
                        <span class="spec-bar"></span>
                        <span class="spec-icon"><img src="{{ asset('icons/gota.svg') }}" alt="Icone gota"></span>
                        <div>
                            <h3>Dermatologia</h3>
                            <p>Diagnóstico de pele, cabelo e unhas, incluindo mapeamento de manchas e sinais.</p>
                        </div>
                    </li>
                    <li class="spec-item">
                        <span class="spec-bar"></span>
                        <span class="spec-icon"><img src="{{ asset('icons/nutricao.svg') }}" alt="Icone nutricao"></span>
                        <div>
                            <h3>Nutrição</h3>
                            <p>Planos alimentares personalizados para emagrecimento, performance e bem-estar.</p>
                        </div>
                    </li>
                    <li class="spec-item">
                        <span class="spec-bar"></span>
                        <span class="spec-icon"><img src="{{ asset('icons/psicologia.svg') }}" alt="Icone psicologia"></span>
                        <div>
                            <h3>Psicologia</h3>
                            <p>Espaço de escuta e acompanhamento terapêutico para ansiedade, luto e rotina.</p>
                        </div>
                    </li>
                </ul>
            </div>
        </section>

        <!-- COMO FUNCIONA -->
        <section id="como-funciona" class="band-soft">
            <div class="container">
                <div class="section-head">
                    <h2>Como funciona</h2>
                    <p>Do agendamento ao acompanhamento, sem burocracia entre uma etapa e outra.</p>
                </div>

                <div class="steps-grid">
                    <div class="step">
                        <span class="step-number">1</span>
                        <h3>Agende</h3>
                        <p>Escolha a especialidade e o horário pelo telefone, WhatsApp ou presencialmente.</p>
                    </div>
                    <div class="step">
                        <span class="step-number">2</span>
                        <h3>Consulte</h3>
                        <p>Seja atendido por um especialista com acesso ao seu histórico completo.</p>
                    </div>
                    <div class="step">
                        <span class="step-number">3</span>
                        <h3>Acompanhe</h3>
                        <p>Receba exames, receitas e retornos organizados em um único prontuário.</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- EQUIPE -->
        <section id="equipe">
            <div class="container">
                <div class="section-head">
                    <h2>Equipe médica</h2>
                    <p>Profissionais experientes, escolhidos pela dedicação tanto quanto pela técnica.</p>
                </div>

                <div class="team-grid">
                    <div class="team-member">
                        <img src="{{ asset('images/equipe-marina-ferraz.jpg') }}" alt="Foto da Dra. Marina Ferraz">
                        <p><strong>Dra. Marina Ferraz</strong><span>Cardiologia</span></p>
                    </div>
                    <div class="team-member">
                        <img src="{{ asset('images/equipe-rafael-tavares.jpg') }}" alt="Foto do Dr. Rafael Tavares">
                        <p><strong>Dr. Rafael Tavares</strong><span>Pediatria</span></p>
                    </div>
                    <div class="team-member">
                        <img src="{{ asset('images/equipe-camila-lopes.jpg') }}" alt="Foto da Dra. Camila Lopes">
                        <p><strong>Dra. Camila Lopes</strong><span>Dermatologia</span></p>
                    </div>
                    <div class="team-member">
                        <img src="{{ asset('images/equipe-joao-salles.jpg') }}" alt="Foto do Dr. João Salles">
                        <p><strong>Dr. João Salles</strong><span>Ortopedia</span></p>
                    </div>
                    <div class="team-member">
                        <img src="{{ asset('images/equipe-helena-prado.jpg') }}" alt="Foto da Dra. Helena Prado">
                        <p><strong>Dra. Helena Prado</strong><span>Ginecologia</span></p>
                    </div>
                </div>
            </div>
        </section>

        <!-- DEPOIMENTOS -->
        <section id="depoimentos" class="band-white">
            <div class="container">
                <div class="section-head">
                    <h2>O que dizem nossos pacientes</h2>
                    <p>Histórias reais de quem já passou pela ProSaúde.</p>
                </div>

                <div class="testimonials-grid">
                    <article class="testimonial">
                        <h3>Helena Marques</h3>
                        <p>“Fui atendida no mesmo dia em que liguei, e pela primeira vez saí de uma consulta com todas as minhas dúvidas respondidas.”</p>
                    </article>
                    <article class="testimonial">
                        <h3>Eduardo Nogueira</h3>
                        <p>“O acompanhamento entre exames e retorno é muito mais organizado do que em qualquer outra clínica que já frequentei.”</p>
                    </article>
                    <article class="testimonial">
                        <h3>Patrícia Ramos</h3>
                        <p>“Levei meu filho para a pediatria e o cuidado com a família toda foi além do que eu esperava.”</p>
                    </article>
                    <article class="testimonial">
                        <h3>Marcos Vinícius</h3>
                        <p>“Marquei minha consulta pelo WhatsApp e fui atendido no mesmo dia. Recomendo para toda a minha equipe.”</p>
                    </article>
                </div>
            </div>
        </section>

        <!-- NÚMEROS -->
        <section id="numeros" class="band-dark">
            <div class="container stats-grid">
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
            <div class="container contact-grid">
                <div class="contact-cta">
                    <h2>Pronto para cuidar da sua saúde?</h2>
                    <p>Nossa recepção responde rapidamente por telefone ou WhatsApp para encontrar o melhor horário para você.</p>

                    <div class="actions">
                        <a class="btn btn-primary" href="{{ url('/login') }}">Agendar Consulta</a>
                        <a class="btn btn-outline" href="#especialidades">Ver Especialidades</a>
                    </div>
                </div>

                <div class="contact-info">
                    <div class="info-item">
                        <span>Telefone / WhatsApp</span>
                        <p>(51) 99999-9999</p>
                    </div>
                    <div class="info-item">
                        <span>E-mail</span>
                        <p>contato@prosaude.com.br</p>
                    </div>
                    <div class="info-item">
                        <span>Endereço</span>
                        <p>Rua das Acácias, 482 — Erechim, RS</p>
                    </div>
                    <div class="info-item">
                        <span>Horário</span>
                        <p>Segunda a sexta, 7h às 19h · Sábado, 8h às 12h</p>
                    </div>
                </div>
            </div>
        </section>

    </main>

    <footer class="site-footer">
        <div class="container">
            <div class="footer-grid">
                <div class="footer-brand">
                    <img src="{{ asset('icons/logo-e-texto.svg') }}" alt="Logo ProSaúde">
                    <p>Consultas, exames e acompanhamento contínuo reunidos em um só lugar.</p>
                </div>

                <nav class="footer-col" aria-label="Navegação do rodapé">
                    <h4>Navegação</h4>
                    <a href="#especialidades">Especialidades</a>
                    <a href="#como-funciona">Como funciona</a>
                    <a href="#equipe">Equipe</a>
                    <a href="#depoimentos">Depoimentos</a>
                </nav>

                <div class="footer-col">
                    <h4>Contato</h4>
                    <a href="tel:+555199999999">(51) 99999-9999</a>
                    <a href="mailto:contato@prosaude.com.br">contato@prosaude.com.br</a>
                    <address>Rua das Acácias, 482<br>Erechim, RS</address>
                </div>

                <div class="footer-col">
                    <h4>Atendimento</h4>
                    <p>Segunda a sexta<br>7h às 19h</p>
                    <p>Sábado<br>8h às 12h</p>
                </div>
            </div>

            <div class="footer-bottom">
                <p>© 2026 ProSaúde. Todos os direitos reservados.</p>
                <p>CNPJ 00.000.000/0001-00</p>
            </div>
        </div>
    </footer>
</body>
</html>
