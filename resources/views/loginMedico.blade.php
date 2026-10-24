<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ProSaúde - Portal Médico</title>
    @vite(['resources/css/loginMedico.css', 'resources/js/scripts/loginMedico.js'])
</head>
<body>
    <header class="site-header">
        <div class="header-container">
            <a href="{{ url('/') }}" class="logo-link">
                <img class="logo" src="{{ asset('icons/logo.svg') }}" alt="Ícone ProSaúde">
            </a>
            <p class="header-login">É paciente? <a href="{{ url('/login') }}">Login de pacientes</a></p>
        </div>
    </header>

    <main class="auth-page">
        <div class="auth-grid">

            <aside class="auth-aside">
                <span class="badge">Acesso restrito</span>
                <h2>Portal do Médico</h2>
                <p>Ambiente exclusivo para médicos cadastrados no corpo clínico da ProSaúde.</p>

                <ul class="benefits">
                    <li>
                        <span class="check" aria-hidden="true">✓</span>
                        <span>Agenda e consultas do dia</span>
                    </li>
                    <li>
                        <span class="check" aria-hidden="true">✓</span>
                        <span>Histórico completo de cada paciente</span>
                    </li>
                    <li>
                        <span class="check" aria-hidden="true">✓</span>
                        <span>Exames, receitas e retornos em um só lugar</span>
                    </li>
                </ul>
            </aside>

            <section id="login" class="auth-card">
                <div class="auth-head">
                    <h1>Entrar no portal</h1>
                    <p>Use seu e-mail corporativo e sua senha para acessar.</p>
                </div>

                <form method="POST" action="{{ url('/api/login/medico') }}" novalidate>
                    @csrf

                    <div class="field">
                        <label for="email">E-mail corporativo / Login</label>
                        <input type="email" id="email" name="email" value="{{ old('email') }}" placeholder="medico@prosaude.com.br" autocomplete="username" required>
                        @error('email')<span class="field-error">{{ $message }}</span>@enderror
                    </div>

                    <div class="field">
                        <label for="senha">Senha</label>
                        <input type="password" id="senha" name="senha" placeholder="Digite sua senha" autocomplete="current-password" required>
                        @error('senha')<span class="field-error">{{ $message }}</span>@enderror
                    </div>

                    <div class="form-options">
                        <div class="remember">
                            <input type="checkbox" id="lembrar" name="lembrar">
                            <label for="lembrar">Lembrar de mim</label>
                        </div>
                        <a href="{{ url('/esqueci-senha') }}">Esqueci minha senha</a>
                    </div>

                    <button type="submit" class="btn btn-primary btn-block">Entrar no Portal Médico</button>
                </form>

                <p class="auth-switch">É paciente? <a href="{{ url('/login') }}">Voltar para o login de pacientes</a></p>
            </section>

        </div>
    </main>

    <footer class="site-footer">
        <p>© {{ date('Y') }} ProSaúde. Todos os direitos reservados.</p>
    </footer>

</body>
</html>
