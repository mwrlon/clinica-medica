<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ProSaúde - Portal Médico</title>
    @vite('resources/css/loginMedico.css')
</head>
<body>
    <header>
        <div>
            <a href="{{ url('/') }}">
                <img src="{{ asset('icons/logo.svg') }}" alt="Ícone ProSaúde">
            </a>
        </div>
    </header>

    <main>
        <section id="login">
            <div>
                <h1>Portal do Médico</h1>
                <p>Acesso restrito para médicos cadastrados no corpo clínico ProSaúde.</p>
            </div>

            <form method="POST" action="{{ url('/api/login/medico') }}">
                @csrf
                <div>
                    <label for="email">E-mail corporativo / Login</label>
                    <input type="email" id="email" name="email" placeholder="medico@prosaude.com.br" required>
                </div>

                <div>
                    <label for="senha">Senha</label>
                    <input type="password" id="senha" name="senha" placeholder="Digite sua senha" required>
                </div>

                <div>
                    <input type="checkbox" id="lembrar" name="lembrar">
                    <label for="lembrar">Lembrar de mim</label>
                </div>

                <button type="submit">Entrar no Portal Médico</button>
            </form>

            <div>
                <p><a href="{{ url('/esqueci-senha') }}">Esqueci minha senha</a></p>
                <hr class="login-divider">
                <p>É paciente? <a href="{{ url('/login') }}"><strong>Voltar para o login de pacientes</strong></a></p>
            </div>
        </section>
    </main>

    <footer>
        <p>© {{ date('Y') }} ProSaúde. Todos os direitos reservados.</p>
    </footer>
</body>
</html>