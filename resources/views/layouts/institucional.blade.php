<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('titulo') - ProSaúde</title>
    @yield('meta')
    {{-- Sem os arquivos compilados (npm run build) a página continua abrindo, só sem o estilo --}}
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/painel-base.css', 'resources/css/institucional.css'])
    @endif
</head>
<body>
    <header class="site-header">
        <div class="header-container">
            <a href="{{ url('/') }}" class="logo-link">
                <img class="logo" src="{{ asset('icons/logo.svg') }}" alt="Ícone ProSaúde">
            </a>

            <nav aria-label="Navegação principal">
                <ul class="link-nav">
                    <li><a href="{{ url('/') }}">Início</a></li>
                    <li><a href="{{ url('/#especialidades') }}">Especialidades</a></li>
                    <li><a href="{{ url('/#equipe') }}">Equipe</a></li>
                    <li><a href="{{ url('/#contato') }}">Contato</a></li>
                </ul>
            </nav>

            <a class="btn btn-primary" href="{{ url('/login') }}">Agendar Consulta</a>
        </div>
    </header>

    <main class="institucional">
        @yield('conteudo')
    </main>

    <footer class="site-footer">
        <p>
            © {{ date('Y') }} ProSaúde. Todos os direitos reservados.
            · <a href="{{ url('/termos') }}">Termos de uso</a>
            · <a href="{{ url('/privacidade') }}">Política de privacidade</a>
        </p>
    </footer>
</body>
</html>
