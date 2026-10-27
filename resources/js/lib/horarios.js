import { el, hojeISO } from './painel.js';
import { mensagemDeErro } from './ui.js';

let contador = 0;

/**
 * Escolha de data e horário de uma consulta: campo de data + botões com os horários livres
 * do médico naquele dia (GET /api/medicos/{id}/horarios).
 *
 * - ignorar:    id da consulta que está sendo reagendada (o horário atual dela conta como livre)
 * - horaLivre:  mostra também um campo de hora livre (encaixe fora da grade; só o médico pode)
 * - aoMudar:    chamado sempre que a data ou o horário escolhido mudam
 *
 * @returns {{ elemento: HTMLElement, valor: () => {data: string, hora: string}, definirMedico: (id) => void, definirData: (iso) => void }}
 */
export function criarSeletorHorarios({ api, medicoId = null, ignorar = null, horaLivre = false, aoMudar = () => {} }) {
    const n = ++contador;
    const idData = `consulta-data-${n}`;
    const idHora = `consulta-hora-${n}`;

    const entradaData = el('input', { type: 'date', id: idData, min: hojeISO(), required: '' });
    const aviso = el('p', { class: 'horarios-aviso' });
    const grade = el('div', { class: 'horarios', role: 'radiogroup', 'aria-label': 'Horários disponíveis' });
    const entradaHora = horaLivre ? el('input', { type: 'time', id: idHora }) : null;

    let medico = medicoId;
    let hora = '';
    let requisicao = 0; // descarta respostas atrasadas quando o usuário troca de dia rapidamente

    const encaixe = horaLivre ? ' Como médico, você ainda pode digitar um horário para o encaixe.' : '';

    function mostrarAviso(texto) {
        grade.replaceChildren();
        aviso.textContent = texto;
        aviso.hidden = false;
    }

    function escolher(novaHora, doBotao) {
        hora = novaHora;
        if (entradaHora && doBotao) entradaHora.value = novaHora;
        aoMudar();
    }

    function botaoHorario(h) {
        const radio = el('input', { type: 'radio', name: `horario-${n}`, value: h });
        radio.addEventListener('change', () => escolher(h, true));
        return el('label', { class: 'horario' }, radio, el('span', { texto: h }));
    }

    async function carregar() {
        const data = entradaData.value;

        hora = '';
        if (entradaHora) entradaHora.value = '';
        const minha = ++requisicao;
        aoMudar();

        if (!medico) return mostrarAviso('Escolha o médico para ver os horários.');
        if (!data) return mostrarAviso('Escolha uma data para ver os horários.');
        if (data < hojeISO()) return mostrarAviso('Escolha uma data a partir de hoje.');

        mostrarAviso('Carregando horários…');

        try {
            const params = { data };
            if (ignorar) params.ignorar = ignorar;
            const { data: resposta } = await api.get(`/medicos/${medico}/horarios`, { params });
            if (minha !== requisicao) return;

            if (resposta.fechado) return mostrarAviso(`A clínica não atende neste dia. Escolha outra data.${encaixe}`);
            if (resposta.horarios.length === 0) return mostrarAviso(`Não há horários livres neste dia. Tente outra data.${encaixe}`);

            aviso.hidden = true;
            grade.replaceChildren(...resposta.horarios.map(botaoHorario));
        } catch (erro) {
            if (minha !== requisicao) return;
            mostrarAviso(mensagemDeErro(erro, 'Não foi possível carregar os horários. Tente novamente.'));
        }
    }

    entradaData.addEventListener('change', carregar);

    if (entradaHora) {
        entradaHora.addEventListener('input', () => {
            grade.querySelectorAll('input[type="radio"]').forEach((radio) => { radio.checked = false; });
            escolher(entradaHora.value, false);
        });
    }

    const elemento = el('div', { class: 'form-grid' },
        el('div', { class: 'campo' }, el('label', { for: idData, texto: 'Data' }), entradaData),
        el('div', { class: 'campo' }, el('span', { class: 'rotulo-campo', texto: 'Horários disponíveis' }), aviso, grade),
        entradaHora
            ? el('div', { class: 'campo' },
                el('label', { for: idHora, texto: 'Ou digite outro horário (encaixe)' }),
                entradaHora)
            : null
    );

    mostrarAviso(medico ? 'Escolha uma data para ver os horários.' : 'Escolha o médico para ver os horários.');

    return {
        elemento,
        valor: () => ({ data: entradaData.value, hora }),
        definirMedico(id) {
            medico = id || null;
            carregar();
        },
        definirData(iso) {
            entradaData.value = iso;
            carregar();
        },
    };
}
