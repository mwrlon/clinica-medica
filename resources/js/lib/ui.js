import { el } from './painel.js';

/**
 * Componentes de interface compartilhados: janelas (<dialog> nativo), avisos, confirmação e
 * tradução de erros da API em mensagens para o usuário.
 *
 * Todo texto entra por textContent (via el()), nunca por innerHTML.
 * Os estilos ficam em resources/css/modal.css.
 */

let contador = 0;

/** Coloca o <dialog> na página, abre como janela modal e o remove do DOM ao fechar. */
function abrir(dialog) {
    dialog.addEventListener('close', () => dialog.remove());
    document.body.append(dialog);
    dialog.showModal();
}

/**
 * Abre uma janela. Esc e o botão × fecham; o clique no fundo não fecha de propósito
 * (arrastar o mouse para fora de um campo de texto não pode descartar o que foi digitado).
 *
 * @returns {{ dialog: HTMLDialogElement, corpo: HTMLElement, rodape: HTMLElement, fechar: () => void }}
 */
export function criarModal({ titulo, subtitulo = '', largo = false }) {
    const idTitulo = `modal-titulo-${++contador}`;

    const corpo = el('div', { class: 'modal-corpo' });
    const rodape = el('div', { class: 'modal-rodape' });
    const botaoFechar = el('button', { type: 'button', class: 'modal-fechar', 'aria-label': 'Fechar', texto: '×' });

    const dialog = el('dialog', { class: largo ? 'modal modal-largo' : 'modal', 'aria-labelledby': idTitulo },
        el('div', { class: 'modal-cabecalho' },
            el('div', {},
                el('h2', { id: idTitulo, texto: titulo }),
                subtitulo ? el('p', { texto: subtitulo }) : null
            ),
            botaoFechar
        ),
        corpo,
        rodape
    );

    botaoFechar.addEventListener('click', () => dialog.close());

    abrir(dialog);

    return { dialog, corpo, rodape, fechar: () => dialog.close() };
}

/* ------------------------------------------------------------------ */
/* Avisos e confirmação                                                */
/* ------------------------------------------------------------------ */

const SVG_NS = 'http://www.w3.org/2000/svg';

// Traços de cada ícone (viewBox 24x24). Os "pontos" são traços de comprimento zero com ponta redonda.
const ICONES = {
    sucesso: ['M5 12.5l4.5 4.5L19 7.5'],
    erro: ['M7 7l10 10', 'M17 7L7 17'],
    aviso: ['M12 7.5v5.5', 'M12 17v.01'],
    info: ['M12 11v5.5', 'M12 7.5v.01'],
    pergunta: ['M9.6 9.4a2.6 2.6 0 1 1 3.9 2.3c-.9.5-1.5 1.1-1.5 2.1', 'M12 17.2v.01'],
};

function icone(nome) {
    const svg = document.createElementNS(SVG_NS, 'svg');
    svg.setAttribute('viewBox', '0 0 24 24');
    svg.setAttribute('aria-hidden', 'true');

    for (const d of ICONES[nome] ?? ICONES.info) {
        const traco = document.createElementNS(SVG_NS, 'path');
        traco.setAttribute('d', d);
        svg.append(traco);
    }

    return svg;
}

/** Monta o <dialog> centralizado usado por avisar() e confirmar(). */
function montarAviso({ tom, iconeNome, titulo, mensagem, itens = [], acoes, progresso = 0, alerta = false }) {
    const n = ++contador;
    const idTitulo = `aviso-titulo-${n}`;
    const idTexto = `aviso-texto-${n}`;
    const temTexto = Boolean(mensagem) || itens.length > 0;

    const atributos = {
        class: `modal modal-aviso tom-${tom}`,
        role: alerta ? 'alertdialog' : 'dialog',
        'aria-labelledby': idTitulo,
    };
    if (temTexto) atributos['aria-describedby'] = idTexto;

    return el('dialog', atributos,
        el('div', { class: 'aviso-conteudo' },
            el('div', { class: 'aviso-icone' }, icone(iconeNome)),
            el('h2', { id: idTitulo, class: 'aviso-titulo', texto: titulo }),
            temTexto
                ? el('div', { id: idTexto, class: 'aviso-texto' },
                    mensagem ? el('p', { class: 'aviso-mensagem', texto: mensagem }) : null,
                    itens.length ? el('ul', { class: 'aviso-itens' }, ...itens.map((texto) => el('li', { texto }))) : null)
                : null
        ),
        el('div', { class: 'aviso-acoes' }, ...acoes),
        progresso ? el('span', { class: 'aviso-progresso', style: `animation-duration: ${progresso}ms`, 'aria-hidden': 'true' }) : null
    );
}

/**
 * Aviso em janela, no lugar do alert() do navegador.
 *
 * - tipo:     'sucesso' | 'erro' | 'aviso' | 'info'
 * - itens:    lista de problemas (ex.: erros de validação), mostrada abaixo da mensagem
 * - fecharEm: milissegundos até fechar sozinho (use só em sucesso; erros esperam o usuário)
 * - acao:     { texto, aoClicar } acrescenta um segundo botão (o principal); o primeiro vira "Fechar"
 *
 * Resolve quando a janela fecha (botão, Esc, clique no fundo ou tempo).
 */
export function avisar({ tipo = 'info', titulo, mensagem = '', itens = [], botao = 'Entendi', fecharEm = 0, acao = null }) {
    return new Promise((resolve) => {
        const ok = el('button', { type: 'button', class: acao ? 'btn btn-outline' : 'btn btn-primary', texto: acao ? 'Fechar' : botao });
        const principal = acao ? el('button', { type: 'button', class: 'btn btn-primary', texto: acao.texto }) : null;

        const dialog = montarAviso({
            tom: tipo,
            iconeNome: tipo,
            titulo,
            mensagem,
            itens,
            acoes: principal ? [ok, principal] : [ok],
            progresso: fecharEm,
            alerta: tipo === 'erro' || tipo === 'aviso',
        });

        let temporizador = null;

        ok.addEventListener('click', () => dialog.close());
        principal?.addEventListener('click', () => {
            dialog.close();
            acao.aoClicar();
        });
        dialog.addEventListener('click', (e) => {
            if (e.target === dialog) dialog.close(); // clique no fundo escuro
        });
        dialog.addEventListener('close', () => {
            clearTimeout(temporizador);
            resolve();
        });

        abrir(dialog);

        if (fecharEm) temporizador = setTimeout(() => dialog.close(), fecharEm);
    });
}

/**
 * Pergunta de confirmação. Resolve true se o usuário confirmar; false se voltar, fechar ou apertar Esc.
 */
export function confirmar({ titulo, mensagem, confirmarTexto = 'Confirmar', voltarTexto = 'Voltar', perigo = false }) {
    return new Promise((resolve) => {
        let resposta = false;

        const voltar = el('button', { type: 'button', class: 'btn btn-outline', texto: voltarTexto });
        const ok = el('button', { type: 'button', class: perigo ? 'btn btn-perigo-cheio' : 'btn btn-primary', texto: confirmarTexto });

        const dialog = montarAviso({
            tom: perigo ? 'aviso' : 'info',
            iconeNome: perigo ? 'aviso' : 'pergunta',
            titulo,
            mensagem,
            acoes: [voltar, ok],
            alerta: perigo,
        });

        voltar.addEventListener('click', () => dialog.close());
        ok.addEventListener('click', () => {
            resposta = true;
            dialog.close();
        });
        dialog.addEventListener('click', (e) => {
            if (e.target === dialog) dialog.close();
        });
        dialog.addEventListener('close', () => resolve(resposta));

        abrir(dialog);
        voltar.focus(); // o foco inicial fica no botão seguro, não no destrutivo
    });
}

/* ------------------------------------------------------------------ */
/* Erros da API                                                         */
/* ------------------------------------------------------------------ */

const semConexao = (erro) => Boolean(erro?.isAxiosError) && !erro.response;

/** Texto de erro de uma chamada da API: mensagem do servidor, primeiro erro de validação ou o padrão. */
export function mensagemDeErro(erro, padrao = 'Não foi possível concluir a ação. Tente novamente.') {
    if (semConexao(erro)) return 'Sem conexão com o servidor. Verifique sua internet e tente novamente.';

    const status = erro?.response?.status;

    if (status >= 500) return padrao; // "Server Error" não ajuda quem está usando
    if (status === 429) return 'Muitas tentativas em pouco tempo. Aguarde um instante e tente novamente.';

    const dados = erro?.response?.data;

    if (dados?.errors) {
        const primeiro = Object.values(dados.errors)[0];
        if (primeiro?.[0]) return primeiro[0];
    }

    // Só o texto em português da nossa API; o "message" genérico do Laravel (404, 429…) vem em inglês
    return dados?.mensagem ?? padrao;
}

/** Título do aviso de erro conforme o tipo de falha. */
export function tituloDoErro(erro, padrao = 'Não foi possível concluir') {
    if (semConexao(erro)) return 'Sem conexão';

    const status = erro?.response?.status;

    if (status >= 500) return 'Algo deu errado';
    if (status === 429) return 'Muitas tentativas';
    if (status === 403) return 'Acesso negado';
    if (status === 409) return 'Ação não permitida';

    return padrao;
}

/**
 * Mostra o erro de uma chamada da API em um aviso. Falhas do sistema (sem conexão, erro 5xx)
 * aparecem como "erro"; recusas de regra (validação, permissão, conflito) como "aviso".
 */
export function avisarErro(erro, { padrao, titulo } = {}) {
    const status = erro?.response?.status;
    const falhaDoSistema = semConexao(erro) || status >= 500 || !erro?.response;

    return avisar({
        tipo: falhaDoSistema ? 'erro' : 'aviso',
        titulo: tituloDoErro(erro, titulo),
        mensagem: mensagemDeErro(erro, padrao),
    });
}
