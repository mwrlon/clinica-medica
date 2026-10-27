import { el } from './painel.js';

/**
 * Componentes de interface dos painéis: janelas (<dialog> nativo), confirmação e avisos rápidos.
 *
 * Todo texto entra por textContent (via el()), nunca por innerHTML.
 */

let contador = 0;

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
    dialog.addEventListener('close', () => dialog.remove());

    document.body.append(dialog);
    dialog.showModal();

    return { dialog, corpo, rodape, fechar: () => dialog.close() };
}

/**
 * Pergunta de confirmação. Resolve true se o usuário confirmar; false se voltar, fechar ou apertar Esc.
 */
export function confirmar({ titulo, mensagem, confirmarTexto = 'Confirmar', voltarTexto = 'Voltar', perigo = false }) {
    return new Promise((resolve) => {
        const modal = criarModal({ titulo });
        modal.corpo.append(el('p', { texto: mensagem }));

        let resposta = false;

        const voltar = el('button', { type: 'button', class: 'btn btn-outline', texto: voltarTexto });
        const ok = el('button', { type: 'button', class: perigo ? 'btn btn-perigo' : 'btn btn-primary', texto: confirmarTexto });

        voltar.addEventListener('click', () => modal.fechar());
        ok.addEventListener('click', () => {
            resposta = true;
            modal.fechar();
        });
        modal.dialog.addEventListener('close', () => resolve(resposta));

        modal.rodape.append(voltar, ok);
        voltar.focus(); // o foco inicial fica no botão seguro, não no destrutivo
    });
}

/** Aviso rápido no canto da tela. tipo: 'ok' | 'erro' */
export function toast(texto, tipo = 'ok') {
    let area = document.querySelector('.toasts');
    if (!area) {
        area = el('div', { class: 'toasts', role: 'status', 'aria-live': 'polite' });
        document.body.append(area);
    }

    const aviso = el('div', { class: tipo === 'erro' ? 'toast toast-erro' : 'toast', texto });
    area.append(aviso);

    setTimeout(() => aviso.remove(), tipo === 'erro' ? 7000 : 4500);
}

/** Texto de erro de uma chamada da API: mensagem do servidor, primeiro erro de validação ou o padrão. */
export function mensagemDeErro(erro, padrao = 'Não foi possível concluir a ação. Tente novamente.') {
    if (erro?.response?.status >= 500) return padrao; // "Server Error" não ajuda quem está usando

    const dados = erro?.response?.data;

    if (dados?.errors) {
        const primeiro = Object.values(dados.errors)[0];
        if (primeiro?.[0]) return primeiro[0];
    }

    // Só o texto em português da nossa API; o "message" genérico do Laravel (404, 429…) vem em inglês
    return dados?.mensagem ?? padrao;
}
