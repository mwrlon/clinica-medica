/**
 * Utilitários de DOM e de datas dos painéis (médico e paciente).
 *
 * Todo texto vindo da API entra por textContent (nunca innerHTML): nomes e observações
 * são digitados por usuários e não podem virar HTML.
 */

export const MESES_CURTOS = ['jan', 'fev', 'mar', 'abr', 'mai', 'jun', 'jul', 'ago', 'set', 'out', 'nov', 'dez'];

const MESES = ['Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho', 'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'];

export const dois = (n) => String(n).padStart(2, '0');

/** Cria um elemento: el('p', { texto: 'Olá', class: 'x' }, filho1, filho2) */
export function el(tag, atributos = {}, ...filhos) {
    const elemento = document.createElement(tag);

    for (const [nome, valor] of Object.entries(atributos)) {
        if (nome === 'texto') elemento.textContent = valor;
        else elemento.setAttribute(nome, valor);
    }

    elemento.append(...filhos.filter((filho) => filho !== null && filho !== undefined));
    return elemento;
}

/** Texto em um elemento marcado com data-campo="nome" */
export function campo(nome) {
    return document.querySelector(`[data-campo="${nome}"]`);
}

export function definirTexto(nome, texto) {
    const elemento = campo(nome);
    if (elemento) elemento.textContent = texto;
}

/** Data de hoje (AAAA-MM-DD) no fuso do navegador */
export function hojeISO() {
    const d = new Date();
    return `${d.getFullYear()}-${dois(d.getMonth() + 1)}-${dois(d.getDate())}`;
}

export function horaAgora() {
    const d = new Date();
    return `${dois(d.getHours())}:${dois(d.getMinutes())}`;
}

/** "2026-09-28" -> "28/09/2026" */
export function dataBR(iso) {
    return iso.slice(0, 10).split('-').reverse().join('/');
}

/** "2026-10-15" -> "15 de Outubro de 2026" */
export function dataLonga(iso) {
    const [ano, mes, dia] = iso.slice(0, 10).split('-').map(Number);
    return `${dia} de ${MESES[mes - 1]} de ${ano}`;
}

/** "2026-09-28T03:00:00.000000Z" (data e hora com fuso) -> "28/09/2026" no fuso do navegador */
export function dataHoraBR(valor) {
    const d = new Date(valor);
    return `${dois(d.getDate())}/${dois(d.getMonth() + 1)}/${d.getFullYear()}`;
}

/** "14:00:00" -> "14:00" */
export function horaCurta(hora) {
    return hora.slice(0, 5);
}

export function idade(nascimentoISO) {
    const [ano, mes, dia] = nascimentoISO.slice(0, 10).split('-').map(Number);
    const hoje = new Date();
    let anos = hoje.getFullYear() - ano;
    if (hoje.getMonth() + 1 < mes || (hoje.getMonth() + 1 === mes && hoje.getDate() < dia)) anos--;
    return anos;
}

/** Consulta ainda por acontecer (agendada, hoje a partir de agora ou em dia futuro) */
export function ehProxima(consulta, hoje = hojeISO(), agora = horaAgora()) {
    return consulta.status === 'agendada'
        && (consulta.data > hoje || (consulta.data === hoje && horaCurta(consulta.hora) >= agora));
}

/** "Dra. Marina Ferraz" -> "marina-ferraz" (nome do arquivo da foto da equipe) */
export function slugDoNome(nome) {
    return nome
        .replace(/^(dr|dra)\.?\s+/i, '')
        .normalize('NFD')
        .replace(/[̀-ͯ]/g, '')
        .toLowerCase()
        .trim()
        .replace(/\s+/g, '-');
}

/** "Helena Souza Marques" -> "HM" (iniciais do primeiro e do último nome, para avatares) */
export function iniciais(nome) {
    const partes = (nome ?? '')
        .replace(/^(dr|dra)\.?\s+/i, '')
        .split(/\s+/)
        .filter(Boolean);

    if (partes.length === 0) return '?';
    if (partes.length === 1) return partes[0][0].toUpperCase();
    return (partes[0][0] + partes[partes.length - 1][0]).toUpperCase();
}

/**
 * Destaca no menu a seção que está na tela (classe "ativo" no link do .link-nav).
 * A seção atual é a última cujo topo já passou do cabeçalho; no fim da página vale a última.
 */
export function ativarMenuPorSecao() {
    const pares = [...document.querySelectorAll('.link-nav a[href^="#"]')]
        .map((link) => [link, document.querySelector(link.getAttribute('href'))])
        .filter(([, secao]) => secao);

    if (pares.length === 0) return;

    let agendado = false;
    let travadoAte = 0; // depois de um clique no menu, a rolagem não troca o link por um instante

    const atualizar = () => {
        agendado = false;
        if (Date.now() < travadoAte) return;
        const limite = (document.querySelector('.site-header')?.offsetHeight ?? 70) + 32;

        let atual = pares[0][0];
        for (const [link, secao] of pares) {
            if (secao.getBoundingClientRect().top <= limite) atual = link;
        }

        const noFim = window.scrollY > 0
            && window.innerHeight + window.scrollY >= document.documentElement.scrollHeight - 4;
        if (noFim) atual = pares[pares.length - 1][0];

        pares.forEach(([link]) => link.classList.toggle('ativo', link === atual));
    };

    const agendar = () => {
        if (!agendado) {
            agendado = true;
            requestAnimationFrame(atualizar);
        }
    };

    // Em páginas curtas a rolagem não chega a levar a seção clicada ao topo (e no fim vale a última):
    // o link clicado fica destacado enquanto a rolagem suave termina.
    pares.forEach(([link]) => link.addEventListener('click', () => {
        travadoAte = Date.now() + 900;
        pares.forEach(([outro]) => outro.classList.toggle('ativo', outro === link));
    }));

    window.addEventListener('scroll', agendar, { passive: true });
    window.addEventListener('resize', agendar);
    atualizar();
}

const DIAS_DA_SEMANA = ['domingo', 'segunda-feira', 'terça-feira', 'quarta-feira', 'quinta-feira', 'sexta-feira', 'sábado'];

/** "2026-10-26" -> "segunda-feira" */
export function diaDaSemana(iso) {
    const [ano, mes, dia] = iso.slice(0, 10).split('-').map(Number);
    return DIAS_DA_SEMANA[new Date(ano, mes - 1, dia).getDay()];
}
