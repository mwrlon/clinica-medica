import { el, campo, dataBR, horaCurta } from '../painel.js';
import { avisar, avisarErro, confirmar } from '../ui.js';
import { estado, debounce, linhaVazia, celulaDupla, etiqueta, botao, desenharPaginacao } from './comum.js';

/** Aba "Consultas": todas as consultas da clínica, com filtros, paginação e cancelamento. */
let pagina = 1;
let requisicao = 0;

const filtro = {
    busca: () => campo('filtro-busca-consultas'),
    status: () => campo('filtro-status-consultas'),
    medico: () => campo('filtro-medico-consultas'),
    inicio: () => campo('filtro-inicio-consultas'),
    fim: () => campo('filtro-fim-consultas'),
};

export function iniciarConsultas() {
    const recarregar = () => {
        pagina = 1;
        carregarConsultas();
    };

    filtro.busca().addEventListener('input', debounce(recarregar, 350));
    [filtro.status(), filtro.medico(), filtro.inicio(), filtro.fim()].forEach((entrada) => entrada.addEventListener('change', recarregar));

    campo('limpar-filtros-consultas').addEventListener('click', () => {
        filtro.busca().value = '';
        filtro.status().value = '';
        filtro.medico().value = '';
        filtro.inicio().value = '';
        filtro.fim().value = '';
        recarregar();
    });
}

/** Mantém a lista de médicos do filtro em dia com os médicos carregados. */
function preencherMedicos() {
    const select = filtro.medico();
    const atual = select.value;

    select.replaceChildren(
        el('option', { value: '', texto: 'Todos' }),
        ...estado.medicos.map((m) => el('option', { value: String(m.id), texto: m.nome }))
    );
    select.value = atual;
}

function parametros() {
    const valores = {
        page: pagina,
        busca: filtro.busca().value.trim(),
        status: filtro.status().value,
        medico_id: filtro.medico().value,
        data_inicio: filtro.inicio().value,
        data_fim: filtro.fim().value,
    };

    return Object.fromEntries(Object.entries(valores).filter(([, valor]) => valor !== '' && valor !== null));
}

export async function carregarConsultas() {
    preencherMedicos();

    const minha = ++requisicao;
    const corpo = campo('lista-consultas');
    const params = parametros();

    try {
        const { data } = await estado.api.get('/admin/consultas', { params });
        if (minha !== requisicao) return;

        const filtrando = Object.keys(params).some((chave) => chave !== 'page');

        corpo.replaceChildren(...(data.data.length
            ? data.data.map(linha)
            : [linhaVazia(6, filtrando ? 'Nenhuma consulta encontrada com estes filtros.' : 'Nenhuma consulta registrada ainda.')]));

        desenharPaginacao(campo('paginacao-consultas'), data, (nova) => {
            pagina = nova;
            carregarConsultas();
        });
    } catch (erro) {
        if (minha !== requisicao) return;
        corpo.replaceChildren(linhaVazia(6, 'Não foi possível carregar as consultas.'));
        campo('paginacao-consultas').replaceChildren();
        avisarErro(erro, {
            titulo: 'Não foi possível carregar as consultas',
            padrao: 'Não foi possível carregar as consultas. Tente novamente.',
        });
    }
}

function linha(consulta) {
    const acoes = el('div', { class: 'acoes-linha' });

    if (consulta.status === 'agendada') {
        acoes.append(botao('Cancelar', 'btn btn-perigo btn-sm', () => cancelar(consulta)));
    }

    return el('tr', {},
        el('td', { class: 'hora' }, `${dataBR(consulta.data)} às ${horaCurta(consulta.hora)}`),
        celulaDupla(consulta.paciente?.nome ?? 'Paciente', consulta.paciente?.cpf ? `CPF ${consulta.paciente.cpf}` : ''),
        celulaDupla(consulta.medico?.nome ?? 'Médico', consulta.medico?.especialidade?.nome ?? ''),
        el('td', { texto: consulta.sala?.identificacao ?? '—' }),
        el('td', {}, etiqueta(consulta.status)),
        el('td', {}, acoes)
    );
}

async function cancelar(consulta) {
    const confirmado = await confirmar({
        titulo: 'Cancelar consulta?',
        mensagem: `Cancelar a consulta de ${consulta.paciente?.nome ?? 'paciente'} com ${consulta.medico?.nome ?? 'o médico'} em ${dataBR(consulta.data)} às ${horaCurta(consulta.hora)}? O horário volta a ficar livre.`,
        confirmarTexto: 'Cancelar consulta',
        voltarTexto: 'Manter consulta',
        perigo: true,
    });
    if (!confirmado) return;

    try {
        await estado.api.put(`/consultas/${consulta.id}/cancelar`);
        avisar({ tipo: 'sucesso', titulo: 'Consulta cancelada', mensagem: 'O horário já está livre para novos agendamentos.', fecharEm: 3500 });
    } catch (erro) {
        await avisarErro(erro, {
            titulo: 'Não foi possível cancelar',
            padrao: 'Não foi possível cancelar a consulta. Tente novamente.',
        });
    }

    await carregarConsultas();
}
