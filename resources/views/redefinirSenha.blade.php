<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ProSaúde - Redefinir senha</title>
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
                    <rect x="4" y="11" width="16" height="9" rx="2"></rect>
                    <path d="M8 11V8a4 4 0 0 1 8 0v3"></path>
                </svg>
            </div>

            <div class="auth-head">
                <h1>Criar nova senha</h1>
                <p>Escolha uma nova senha para acessar sua conta.</p>
            </div>

            @if (session('status'))
                <div class="alert alert-success" role="status">
                    {{ session('status') }}
                </div>
            @else
                <form method="POST" action="{{ url('/redefinir-senha') }}" novalidate>
                    @csrf
                    <input type="hidden" name="token" value="{{ $token }}">
                    <input type="hidden" name="email" value="{{ $email }}">
                    <input type="hidden" name="tipo" value="{{ $tipo }}">

                    <div class="field">
                        <label for="senha">Nova senha</label>
                        <input type="password" id="senha" name="senha" placeholder="Mínimo de 8 caracteres" minlength="8" autocomplete="new-password" autofocus required>
                        @error('senha')<span class="field-error">{{ $message }}</span>@enderror
                    </div>

                    <div class="field">
                        <label for="confirmar_senha">Confirmar nova senha</label>
                        <input type="password" id="confirmar_senha" name="confirmar_senha" placeholder="Repita a senha" minlength="8" autocomplete="new-password" required>
                        @error('confirmar_senha')<span class="field-error">{{ $message }}</span>@enderror
                        @error('email')<span class="field-error">{{ $message }}</span>@enderror
                        @error('token')<span class="field-error">{{ $message }}</span>@enderror
                    </div>

                    <button type="submit" class="btn btn-primary btn-block">Salvar nova senha</button>
                </form>
            @endif

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
