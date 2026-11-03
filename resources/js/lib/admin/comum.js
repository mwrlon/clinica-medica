import { el } from '../painel.js';

/**
 * Peças compartilhadas pelas abas do painel do administrador.
 */

/** Dados carregados da API e usados por mais de uma aba (formulários e filtros). */
export const estado = {
    api: null,
    medicos: [],
    especialidades: [],
};

export function debounce(funcao, ms = 300) {
    let temporizador;
    return (...args) => {
        clearTimeout(temporizador);
        temporizador = setTimeout(() => funcao(...args), ms);
    };
}

/** Minúsculas e sem acento, para buscar "jose" e achar "José". */
export function simplificar(texto) {
    return (texto ?? '')
        .toString()
        .normalize('NFD')
        .replace(/[̀-ͯ]/g, '')
        .toLowerCase();
}

export function linhaVazia(colunas, texto) {
    return el('tr', {}, el('td', { class: 'vazio', colspan: String(colunas), texto }));
}

const ETIQUETAS = {
    agendada: ['Agendada', 'tag'],
    realizada: ['Realizada', 'tag tag-ok'],
    cancelada: ['Cancelada', 'tag tag-neutra'],
    ativo: ['Ativo', 'tag tag-ok'],
    inativo: ['Inativo', 'tag tag-neutra'],
};

/** Etiqueta colorida da situação de uma consulta ou de um médico. */
export function etiqueta(status) {
    const [texto, classe] = ETIQUETAS[status] ?? [status, 'tag tag-neutra'];
    return el('span', { class: classe, texto });
}

/** Célula com um texto principal e, abaixo, um detalhe discreto. */
export function celulaDupla(principal, detalhe) {
    return el('td', {},
        el('strong', { class: 'item-titulo', texto: principal }),
        detalhe ? el('small', { texto: detalhe }) : null
    );
}

export function botao(texto, classe, aoClicar) {
    const b = el('button', { type: 'button', class: classe, texto });
    b.addEventListener('click', aoClicar);
    return b;
}

/**
 * Barra "Anterior / Próxima" de uma lista paginada pelo servidor.
 * meta = { current_page, last_page, total } (resposta do paginate do Laravel).
 */
export function desenharPaginacao(destino, meta, aoMudar) {
    destino.replaceChildren();
    if (!meta || meta.total === 0) return;

    const anterior = botao('← Anterior', 'btn btn-outline btn-sm', () => aoMudar(meta.current_page - 1));
    const proxima = botao('Próxima →', 'btn btn-outline btn-sm', () => aoMudar(meta.current_page + 1));
    anterior.disabled = meta.current_page <= 1;
    proxima.disabled = meta.current_page >= meta.last_page;

    destino.append(
        el('span', {
            class: 'paginacao-info',
            texto: `Página ${meta.current_page} de ${meta.last_page} · ${meta.total} ${meta.total === 1 ? 'registro' : 'registros'}`,
        }),
        el('div', { class: 'paginacao-botoes' }, anterior, proxima)
    );
}
