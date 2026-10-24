<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ProSaúde - Recuperar senha</title>
    @vite(['resources/css/esqueciSenha.css'])
</head>
<body>
    <header class="site-header">
        <div class="header-container">
            <a href="{{ url('/') }}" class="logo-link">
                <img class="logo" src="{{ asset('icons/logo.svg') }}" alt="Ícone ProSaúde">
            </a>
            <p class="header-login">Lembrou a senha? <a href="{{ url('/login') }}">Entrar</a></p>
        </div>
    </header>

    <main class="auth-page">
        <section id="recuperar" class="auth-card">

            <div class="icon-circle" aria-hidden="true">
                <svg viewBox="0 0 24 24" width="28" height="28" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="3" y="5" width="18" height="14" rx="2"></rect>
                    <path d="M3 7l9 6 9-6"></path>
                </svg>
            </div>

            <div class="auth-head">
                <h1>Esqueceu sua senha?</h1>
                <p>Sem problemas. Informe o e-mail da sua conta e enviaremos as instruções para criar uma nova senha.</p>
            </div>

            @if (session('status'))
                <div class="alert alert-success" role="status">
                    {{ session('status') }}
                </div>
            @endif

            <form method="POST" action="{{ url('/esqueci-senha') }}" novalidate>
                @csrf

                <div class="field">
                    <label for="email">E-mail</label>
                    <input type="email" id="email" name="email" value="{{ old('email') }}" placeholder="seuemail@exemplo.com" autocomplete="email" autofocus required>
                    @error('email')<span class="field-error">{{ $message }}</span>@enderror
                </div>

                <button type="submit" class="btn btn-primary btn-block">Enviar instruções</button>
            </form>

            <p class="auth-note">Não recebeu? Confira a caixa de spam ou tente novamente em alguns minutos.</p>

            <div class="auth-links">
                <a href="{{ url('/login') }}">← Voltar para o login de pacientes</a>
                <a href="{{ url('/login-medico') }}">Acessar o portal médico</a>
            </div>

        </section>
    </main>

    <footer class="site-footer">
        <p>© {{ date('Y') }} ProSaúde. Todos os direitos reservados.</p>
    </footer>
</body>
</html>
