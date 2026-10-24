<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ProSaúde - Entrar (Paciente)</title>
    @vite('resources/css/loginPaciente.css')
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
                <h2>Bem-vindo de volta à ProSaúde</h2>
                <p>Acesse sua conta para cuidar da sua saúde com tudo organizado em um só lugar.</p>

                <ul class="benefits">
                    <li>
                        <span class="check" aria-hidden="true">✓</span>
                        <span>Agende e acompanhe suas consultas</span>
                    </li>
                    <li>
                        <span class="check" aria-hidden="true">✓</span>
                        <span>Veja seus exames e receitas</span>
                    </li>
                    <li>
                        <span class="check" aria-hidden="true">✓</span>
                        <span>Consulte todo o seu histórico de saúde</span>
                    </li>
                </ul>
            </aside>

            <section id="login" class="auth-card">
                <div class="auth-head">
                    <h1>Entrar na sua conta</h1>
                    <p>Use seu e-mail e sua senha para acessar.</p>
                </div>

                <form method="POST" action="{{ url('/api/login/paciente') }}" novalidate>
                    @csrf

                    <div class="field">
                        <label for="email">E-mail</label>
                        <input type="email" id="email" name="email" value="{{ old('email') }}" placeholder="seuemail@exemplo.com" autocomplete="username" required>
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

                    <button type="submit" class="btn btn-primary btn-block">Entrar</button>
                </form>

                <div class="auth-links">
                    <p>Ainda não tem conta? <a href="{{ url('/cadastro') }}">Cadastre-se</a></p>
                    <p>Você é médico? <a href="{{ url('/login-medico') }}">Acesse a área médica aqui</a></p>
                </div>
            </section>

        </div>
    </main>

    <footer class="site-footer">
        <p>© {{ date('Y') }} ProSaúde. Todos os direitos reservados.</p>
    </footer>
</body>
</html>
