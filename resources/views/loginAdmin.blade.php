<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex">
    <title>ProSaúde - Administração</title>
    @vite(['resources/css/modal.css', 'resources/css/loginMedico.css', 'resources/js/scripts/loginAdmin.js'])
</head>
<body>
    <header class="site-header">
        <div class="header-container">
            <a href="{{ url('/') }}" class="logo-link">
                <img class="logo" src="{{ asset('icons/logo.svg') }}" alt="Ícone ProSaúde">
            </a>
            <p class="header-login">É médico? <a href="{{ url('/login-medico') }}">Portal do médico</a></p>
        </div>
    </header>

    <main class="auth-page">
        <div class="auth-grid">

            <aside class="auth-aside">
                <span class="badge">Acesso restrito</span>
                <h2>Administração da clínica</h2>
                <p>Ambiente exclusivo para a equipe administrativa da ProSaúde.</p>

                <ul class="benefits">
                    <li>
                        <span class="check" aria-hidden="true">✓</span>
                        <span>Cadastro de médicos, especialidades, convênios e salas</span>
                    </li>
                    <li>
                        <span class="check" aria-hidden="true">✓</span>
                        <span>Consultas de toda a clínica, com filtros e cancelamento</span>
                    </li>
                    <li>
                        <span class="check" aria-hidden="true">✓</span>
                        <span>Números do mês em uma visão geral</span>
                    </li>
                </ul>
            </aside>

            <section id="login" class="auth-card">
                <div class="auth-head">
                    <h1>Entrar na administração</h1>
                    <p>Use o e-mail e a senha da sua conta de administrador.</p>
                </div>

                <form method="POST" action="{{ url('/api/login/admin') }}" novalidate>
                    @csrf

                    <div class="field">
                        <label for="email">E-mail</label>
                        <input type="email" id="email" name="email" value="{{ old('email') }}" placeholder="admin@prosaude.com.br" autocomplete="username" required>
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
                    </div>

                    <button type="submit" class="btn btn-primary btn-block">Entrar na administração</button>
                </form>

                <p class="auth-switch">Voltar para o <a href="{{ url('/') }}">site da clínica</a></p>
            </section>

        </div>
    </main>

    <footer class="site-footer">
        <p>© {{ date('Y') }} ProSaúde. Todos os direitos reservados.</p>
    </footer>

</body>
</html>
