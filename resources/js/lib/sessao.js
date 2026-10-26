/**
 * Sessão (token da API) e formulários de autenticação, compartilhados pelas telas.
 *
 * O token fica em localStorage quando o usuário marca "Lembrar de mim" e em
 * sessionStorage caso contrário (some ao fechar a aba).
 */

export const LOGIN = { paciente: '/login', medico: '/login-medico' };
export const PAINEL = { paciente: '/paciente', medico: '/medico' };

export function limparSessao() {
    for (const tipo of ['localStorage', 'sessionStorage']) {
        try {
            window[tipo].removeItem('token');
            window[tipo].removeItem('perfil');
        } catch (e) { /* armazenamento indisponível */ }
    }
}

export function salvarSessao(token, perfil, lembrar = true) {
    limparSessao();
    try {
        const storage = lembrar ? window.localStorage : window.sessionStorage;
        storage.setItem('token', token);
        storage.setItem('perfil', perfil);
    } catch (e) { /* armazenamento indisponível */ }
}

export function lerSessao() {
    for (const tipo of ['localStorage', 'sessionStorage']) {
        try {
            const token = window[tipo].getItem('token');
            if (token) return { token, perfil: window[tipo].getItem('perfil') };
        } catch (e) { /* armazenamento indisponível */ }
    }
    return { token: null, perfil: null };
}

/**
 * Faz uma requisição JSON à API usando a sessão atual.
 * Um 401 encerra a sessão; erros mantêm status e payload em `response`.
 */
async function requisitarApi(token, perfil, caminho, opcoes = {}) {
    const resposta = await fetch(`/api${caminho}`, {
        ...opcoes,
        headers: {
            Accept: 'application/json',
            ...(opcoes.body ? { 'Content-Type': 'application/json' } : {}),
            Authorization: `Bearer ${token}`,
            ...opcoes.headers,
        },
    });

    const texto = await resposta.text();
    let dados = null;
    if (texto) {
        try { dados = JSON.parse(texto); }
        catch { dados = { mensagem: texto }; }
    }

    if (resposta.status === 401) {
        limparSessao();
        window.location.href = LOGIN[perfil];
    }

    if (!resposta.ok) {
        const erro = new Error(dados?.mensagem ?? `Erro HTTP ${resposta.status}`);
        erro.response = { status: resposta.status, data: dados };
        throw erro;
    }

    return dados;
}

/**
 * Protege um painel: exige sessão do perfil esperado (senão vai para o login)
 * e devolve métodos JSON autenticados por Bearer token.
 */
export function iniciarPainel(perfilEsperado) {
    const { token, perfil } = lerSessao();

    if (!token || perfil !== perfilEsperado) {
        window.location.href = LOGIN[perfilEsperado];
        return null;
    }

    return {
        get: (caminho) => requisitarApi(token, perfilEsperado, caminho),
        post: (caminho, dados) => requisitarApi(token, perfilEsperado, caminho, {
            method: 'POST',
            ...(dados === undefined ? {} : { body: JSON.stringify(dados) }),
        }),
        put: (caminho, dados) => requisitarApi(token, perfilEsperado, caminho, {
            method: 'PUT',
            ...(dados === undefined ? {} : { body: JSON.stringify(dados) }),
        }),
    };
}

/** Botão "Sair": revoga o token na API, limpa a sessão e volta ao login do perfil. */
export function ligarLogout(api, perfil) {
    const botao = document.getElementById('btn-sair');
    if (!botao) return;

    botao.addEventListener('click', async (e) => {
        e.preventDefault();
        try {
            await api.post('/logout');
        } catch (erro) { /* mesmo sem resposta da API a sessão local é encerrada */ }
        limparSessao();
        window.location.href = LOGIN[perfil];
    });
}

/* ------------------------------------------------------------------ */
/* Mensagens de erro dos formulários (usa as classes .field-error existentes) */
/* ------------------------------------------------------------------ */

export function limparErros(form) {
    form.querySelectorAll('.field-error').forEach((el) => {
        if (el.dataset.dinamico) el.remove();
        else el.textContent = '';
    });
}

/** Mostra o erro abaixo do campo; cria o <span class="field-error"> se o servidor não o renderizou. */
export function mostrarErro(form, campo, texto) {
    const input = form.querySelector(`[name="${campo}"]`);
    const alvo = input?.closest('.field') || input?.closest('.terms');

    if (!alvo) {
        const mensagens = document.getElementById('mensagens');
        if (mensagens) mensagens.textContent = texto;
        else alert(texto);
        return;
    }

    // Campo comum: o erro fica dentro de .field. Termos: o erro fica logo depois de .terms.
    const emTermos = alvo.classList.contains('terms');
    let span = emTermos
        ? (alvo.nextElementSibling?.classList.contains('field-error') ? alvo.nextElementSibling : null)
        : alvo.querySelector('.field-error');

    if (!span) {
        span = document.createElement('span');
        span.className = 'field-error';
        span.dataset.dinamico = '1';
        if (emTermos) alvo.after(span);
        else alvo.append(span);
    }

    span.textContent = texto;
}

/**
 * Envia o login e, em caso de sucesso, guarda o token e abre o painel.
 * Erros: 422 = campo inválido/credenciais; 403 = acesso negado (ex.: médico inativo).
 */
export async function entrar({ form, endpoint, perfil, textoPadrao, textoCarregando }) {
    limparErros(form);

    const botao = form.querySelector('button[type="submit"]');
    botao.disabled = true;
    botao.textContent = textoCarregando;

    try {
        const resposta = await fetch(endpoint, {
            method: 'POST',
            headers: { Accept: 'application/json', 'Content-Type': 'application/json' },
            body: JSON.stringify({ email: form.email.value, senha: form.senha.value }),
        });
        const data = await resposta.json();
        if (!resposta.ok) {
            const erro = new Error(data?.mensagem ?? `Erro HTTP ${resposta.status}`);
            erro.response = { status: resposta.status, data };
            throw erro;
        }

        if (!data.access_token) throw new Error('Resposta da API sem access_token.');

        salvarSessao(data.access_token, perfil, form.lembrar.checked);
        window.location.href = PAINEL[perfil];
    } catch (erro) {
        botao.disabled = false;
        botao.textContent = textoPadrao;

        const status = erro.response?.status;
        const dados = erro.response?.data;

        if (status === 422 && dados?.errors) {
            for (const [campo, mensagens] of Object.entries(dados.errors)) {
                mostrarErro(form, campo, mensagens[0]);
            }
        } else if (status === 403 || status === 412) {
            mostrarErro(form, 'email', dados?.mensagem ?? 'Acesso negado.');
        } else {
            mostrarErro(form, 'senha', 'Não foi possível conectar ao servidor. Tente novamente em instantes.');
        }
    }
}
