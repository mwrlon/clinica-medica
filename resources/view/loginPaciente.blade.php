<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ProSaúde - Entrar (Paciente)</title>
    <link rel="stylesheet" href="{{ asset('css/login.css') }}">
</head>
<body>
    <header>
        <div>
            <a href="{{ url('/') }}">
                <img src="{{ asset('icons/logo-prosaude.svg') }}" alt="Ícone ProSaúde">
            </a>
        </div>
    </header>

    <main>
        <section id="login">
            <div>
                <h1>Entrar na sua conta</h1>
                <p>Acesse para agendar consultas, ver exames e acompanhar seu histórico.</p>
            </div>

            <form method="POST" action="{{ url('/api/login/paciente') }}">
                @csrf
                <div>
                    <label for="email">E-mail</label>
                    <input type="email" id="email" name="email" placeholder="seuemail@exemplo.com" required>
                </div>

                <div>
                    <label for="senha">Senha</label>
                    <input type="password" id="senha" name="senha" placeholder="Digite sua senha" required>
                </div>

                <div>
                    <input type="checkbox" id="lembrar" name="lembrar">
                    <label for="lembrar">Lembrar de mim</label>
                </div>

                <button type="submit">Entrar</button>
            </form>

            <div>
                <p><a href="{{ url('/esqueci-senha') }}">Esqueci minha senha</a></p>
                <p>Ainda não tem conta? <a href="{{ url('/cadastro') }}">Cadastre-se</a></p>
                <hr class="login-divider">
                <p>Você é médico? <a href="{{ url('/login-medico') }}"><strong>Acesse a área médica aqui</strong></a></p>
            </div>
        </section>
    </main>

    <footer>
        <p>© {{ date('Y') }} ProSaúde. Todos os direitos reservados.</p>
    </footer>
</body>
</html>