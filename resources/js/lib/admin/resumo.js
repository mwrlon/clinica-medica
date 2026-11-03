import { el, campo, definirTexto, dataBR, horaCurta, hojeISO, diaDaSemana } from '../painel.js';
import { avisarErro } from '../ui.js';
import { estado } from './comum.js';

/** Aba "Visão Geral": números do mês, consultas por especialidade e próximas consultas. */
export async function carregarResumo({ silencioso = false } = {}) {
    try {
        const { data } = await estado.api.get('/admin/resumo');
        desenhar(data);
    } catch (erro) {
        if (!silencioso) {
            avisarErro(erro, {
                titulo: 'Não foi possível carregar a visão geral',
                padrao: 'Não foi possível carregar os números agora. Tente novamente.',
            });
        }
    }
}

function plural(n, singular, pluralTexto) {
    return `${n} ${n === 1 ? singular : pluralTexto}`;
}

function desenhar(d) {
    definirTexto('m-medicos', String(d.medicos.ativos));
    definirTexto('m-medicos-detalhe', d.medicos.inativos
        ? plural(d.medicos.inativos, 'médico inativo', 'médicos inativos')
        : 'Nenhum médico inativo');

    definirTexto('m-pacientes', String(d.pacientes.total));
    definirTexto('m-pacientes-detalhe', `${plural(d.pacientes.novos_30_dias, 'novo cadastro', 'novos cadastros')} em 30 dias`);

    definirTexto('m-hoje', String(d.consultas.hoje));
    definirTexto('m-hoje-detalhe', `${plural(d.consultas.agendadas, 'consulta agendada', 'consultas agendadas')} no total`);

    const noMes = d.consultas.mes_agendadas + d.consultas.mes_realizadas;
    definirTexto('m-mes', String(noMes));
    definirTexto('m-mes-detalhe', `${d.consultas.mes_realizadas} realizadas · ${d.consultas.mes_canceladas} canceladas`);

    desenharEspecialidades(d.por_especialidade);
    desenharProximas(d.proximas);
}

function desenharEspecialidades(lista) {
    const destino = campo('por-especialidade');
    destino.replaceChildren();

    if (lista.length === 0) {
        destino.append(el('p', { class: 'vazio', texto: 'Ainda não há consultas neste mês.' }));
        return;
    }

    const maior = Math.max(...lista.map((item) => Number(item.total)));

    destino.append(...lista.map((item) => el('div', {},
        el('div', { class: 'barra-rotulo' },
            el('span', { texto: item.nome }),
            el('strong', { texto: String(item.total) })
        ),
        el('div', { class: 'barra', role: 'presentation' },
            el('i', { style: `width: ${Math.max(4, Math.round((Number(item.total) / maior) * 100))}%` })
        )
    )));
}

function desenharProximas(lista) {
    const destino = campo('proximas');
    destino.replaceChildren();

    if (lista.length === 0) {
        destino.append(el('li', {}, el('p', { class: 'vazio', texto: 'Nenhuma consulta agendada para os próximos dias.' })));
        return;
    }

    const hoje = hojeISO();

    destino.append(...lista.map((consulta) => el('li', {},
        el('div', { class: 'proxima-data' },
            el('span', { texto: consulta.data === hoje ? 'hoje' : `${diaDaSemana(consulta.data).slice(0, 3)} ${dataBR(consulta.data).slice(0, 5)}` }),
            el('strong', { texto: horaCurta(consulta.hora) })
        ),
        el('div', { class: 'proxima-info' },
            el('strong', { class: 'item-titulo', texto: consulta.paciente?.nome ?? 'Paciente' }),
            el('small', { texto: `${consulta.medico?.nome ?? 'Médico'}${consulta.sala ? ` · ${consulta.sala.identificacao}` : ''}` })
        )
    )));
}
