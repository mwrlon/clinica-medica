@extends('layouts.institucional')

@section('titulo', 'Página não encontrada')

@section('meta')
    <meta name="robots" content="noindex">
@endsection

@section('conteudo')
    <section class="erro-pagina" aria-labelledby="erro-titulo">
        <p class="erro-codigo" aria-hidden="true">404</p>

        <svg class="erro-pulso" viewBox="0 0 400 80" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M0 40h118l14-26 22 56 18-42 12 12h216"></path>
        </svg>

        <span class="tag">Erro 404</span>
        <h1 id="erro-titulo">Não encontramos esta página</h1>
        <p class="lead">O endereço pode ter sido digitado errado ou a página mudou de lugar. A sua saúde continua em boas mãos: escolha por onde seguir.</p>

        <div class="erro-acoes">
            <a class="btn btn-primary" href="{{ url('/') }}">Voltar ao início</a>
            <a class="btn btn-outline" href="{{ url('/login') }}">Entrar na minha área</a>
        </div>

        <ul class="erro-atalhos">
            <li><a href="{{ url('/#especialidades') }}"><strong>Especialidades</strong><span>Conheça o que a clínica oferece</span></a></li>
            <li><a href="{{ url('/#equipe') }}"><strong>Equipe médica</strong><span>Profissionais que cuidam de você</span></a></li>
            <li><a href="{{ url('/#contato') }}"><strong>Contato</strong><span>Telefone, e-mail e endereço</span></a></li>
            <li><a href="{{ url('/login-medico') }}"><strong>Portal do médico</strong><span>Acesso para o corpo clínico</span></a></li>
        </ul>
    </section>
@endsection
