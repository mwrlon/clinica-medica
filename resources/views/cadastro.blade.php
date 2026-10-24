<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ProSaúde - Cadastro</title>
    @vite(['resources/css/cadastro.css', 'resources/js/scripts/cadastro.js'])
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
                <h2>Seu histórico de saúde, sempre à mão</h2>
                <p>Com a conta ProSaúde você agenda consultas e acompanha tudo em um só lugar.</p>

                <ul class="benefits">
                    <li>
                        <span class="check" aria-hidden="true">✓</span>
                        <span>Agende consultas sem precisar ligar</span>
                    </li>
                    <li>
                        <span class="check" aria-hidden="true">✓</span>
                        <span>Exames, receitas e retornos em um único prontuário</span>
                    </li>
                    <li>
                        <span class="check" aria-hidden="true">✓</span>
                        <span>Acesso ao seu histórico completo em qualquer consulta</span>
                    </li>
                </ul>
            </aside>

            <section id="cadastro" class="auth-card">
                <div class="auth-head">
                    <h1>Crie sua conta</h1>
                    <p>Preencha os dados abaixo para começar. Leva menos de um minuto.</p>
                </div>

                <form id="form-cadastro">
                    @csrf

                    <div class="form-grid">
                        <div class="field field-full">
                            <label for="nome">Nome completo</label>
                            <input type="text" id="nome" name="nome" value="{{ old('nome') }}" placeholder="Digite seu nome completo" autocomplete="name" required>
                            @error('nome')<span class="field-error">{{ $message }}</span>@enderror
                        </div>

                        <div class="field">
                            <label for="cpf">CPF</label>
                            <input type="text" id="cpf" name="cpf" value="{{ old('cpf') }}" placeholder="000.000.000-00" inputmode="numeric" maxlength="14" required>
                            @error('cpf')<span class="field-error">{{ $message }}</span>@enderror
                        </div>

                        <div class="field">
                            <label for="nascimento">Data de nascimento</label>
                            <input type="date" id="nascimento" name="data_nascimento" value="{{ old('data_nascimento') }}" autocomplete="bday" required>
                            @error('data_nascimento')<span class="field-error">{{ $message }}</span>@enderror
                        </div>

                        <div class="field">
                            <label for="sexo">Sexo</label>
                            <select id="sexo" name="sexo" required>
                                <option value="">Selecione</option>
                                <option value="M">Masculino</option>
                                <option value="F">Feminino</option>
                                <option value="Outro">Outro</option>
                            </select>
                            <span class="field-error"></span>
                        </div>

                        <div class="field">
                            <label for="email">E-mail</label>
                            <input type="email" id="email" name="email" value="{{ old('email') }}" placeholder="seuemail@exemplo.com" autocomplete="email" required>
                            @error('email')<span class="field-error">{{ $message }}</span>@enderror
                        </div>

                        <div class="field">
                            <label for="telefone">Telefone / WhatsApp</label>
                            <input type="tel" id="telefone" name="telefone" value="{{ old('telefone') }}" placeholder="(00) 00000-0000" autocomplete="tel" required>
                            @error('telefone')<span class="field-error">{{ $message }}</span>@enderror
                        </div>
                    </div>

                    <div class="form-divider"><span>Acesso</span></div>

                    <div class="form-grid">
                        <div class="field">
                            <label for="senha">Senha</label>
                            <input type="password" id="senha" name="senha" placeholder="Crie uma senha" minlength="8" autocomplete="new-password" required>
                            <span class="field-hint">Mínimo de 8 caracteres.</span>
                            @error('senha')<span class="field-error">{{ $message }}</span>@enderror
                        </div>

                        <div class="field">
                            <label for="confirmar-senha">Confirmar senha</label>
                            <input type="password" id="confirmar_senha" name="confirmar_senha" placeholder="Repita a senha" minlength="8" autocomplete="new-password" required>
                            @error('confirmar_senha')<span class="field-error">{{ $message }}</span>@enderror
                        </div>
                    </div>

                    <div class="terms">
                        <input type="checkbox" id="termos" name="termos" required>
                        <label for="termos">Li e aceito os termos de uso e a política de privacidade</label>
                    </div>
                    @error('termos')<span class="field-error">{{ $message }}</span>@enderror

                    <button type="submit" class="btn btn-primary btn-block">Criar conta</button>
                </form>

                <p class="auth-switch">Já tem uma conta? <a href="{{ url('/login') }}">Entrar</a></p>
            </section>

        </div>
    </main>

    <footer class="site-footer">
        <p>© 2026 ProSaúde. Todos os direitos reservados.</p>
    </footer>
</body>
</html>
