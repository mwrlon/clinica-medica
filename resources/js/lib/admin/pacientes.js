import { el, campo, dataHoraBR } from '../painel.js';
import { avisarErro } from '../ui.js';
import { estado, debounce, linhaVazia, celulaDupla, desenharPaginacao } from './comum.js';

/** Aba "Pacientes": lista paginada com busca por nome, CPF ou e-mail (somente leitura). */
const consulta = { pagina: 1, busca: '' };
let requisicao = 0;

export function iniciarPacientes() {
    campo('busca-pacientes').addEventListener('input', debounce((evento) => {
        consulta.busca = evento.target.value.trim();
        consulta.pagina = 1;
        carregarPacientes();
    }, 350));
}

export async function carregarPacientes() {
    const minha = ++requisicao;
    const corpo = campo('lista-pacientes');

    try {
        const { data } = await estado.api.get('/admin/pacientes', {
            params: { page: consulta.pagina, ...(consulta.busca ? { busca: consulta.busca } : {}) },
        });
        if (minha !== requisicao) return;

        corpo.replaceChildren(...(data.data.length
            ? data.data.map(linha)
            : [linhaVazia(5, consulta.busca ? 'Nenhum paciente encontrado para esta busca.' : 'Nenhum paciente cadastrado ainda.')]));

        desenharPaginacao(campo('paginacao-pacientes'), data, (pagina) => {
            consulta.pagina = pagina;
            carregarPacientes();
        });
    } catch (erro) {
        if (minha !== requisicao) return;
        corpo.replaceChildren(linhaVazia(5, 'Não foi possível carregar os pacientes.'));
        campo('paginacao-pacientes').replaceChildren();
        avisarErro(erro, {
            titulo: 'Não foi possível carregar os pacientes',
            padrao: 'Não foi possível carregar a lista de pacientes. Tente novamente.',
        });
    }
}

function linha(paciente) {
    return el('tr', {},
        celulaDupla(paciente.nome, `CPF ${paciente.cpf}`),
        celulaDupla(paciente.telefone, paciente.email),
        el('td', { texto: paciente.convenio?.nome ?? 'Particular' }),
        el('td', { class: 'numero', texto: String(paciente.consultas_count) }),
        el('td', { texto: dataHoraBR(paciente.criado_em) })
    );
}
