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
