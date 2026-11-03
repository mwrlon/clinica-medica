import { el, campo } from '../painel.js';
import { confirmar, avisar, avisarErro } from '../ui.js';
import { estado, botao } from './comum.js';

/**
 * Aba "Cadastros": especialidades, convênios e salas.
 * Cada cartão lista os itens (com a quantidade de usos), permite adicionar e excluir.
 * Um item em uso não pode ser excluído (o servidor também recusa).
 */
const CADASTROS = [
    {
        chave: 'especialidades',
        titulo: 'Especialidades',
        descricao: 'Aparecem na escolha do médico ao agendar.',
        rota: '/admin/especialidades',
        campoNome: 'nome',
        placeholder: 'Ex.: Neurologia',
        rotuloAdicionar: 'Nova especialidade',
        usos: (item) => item.medicos_count,
        textoUsos: (n) => `${n} ${n === 1 ? 'médico' : 'médicos'}`,
        vazio: 'Nenhuma especialidade cadastrada.',
        artigo: 'A especialidade',
        motivoBloqueio: 'Há médicos nesta especialidade.',
    },
    {
        chave: 'convenios',
        titulo: 'Convênios',
        descricao: 'O paciente escolhe o convênio no perfil dele.',
        rota: '/admin/convenios',
        campoNome: 'nome',
        placeholder: 'Ex.: Unimed',
        rotuloAdicionar: 'Novo convênio',
        usos: (item) => item.pacientes_count,
        textoUsos: (n) => `${n} ${n === 1 ? 'paciente' : 'pacientes'}`,
        vazio: 'Nenhum convênio cadastrado.',
        artigo: 'O convênio',
        motivoBloqueio: 'Há pacientes neste convênio.',
    },
    {
        chave: 'salas',
        titulo: 'Salas',
        descricao: 'Usadas automaticamente nos agendamentos.',
        rota: '/admin/salas',
        campoNome: 'identificacao',
        placeholder: 'Ex.: Consultório 04',
        rotuloAdicionar: 'Nova sala',
        usos: (item) => item.consultas_count,
        textoUsos: (n) => `${n} ${n === 1 ? 'consulta' : 'consultas'}`,
        vazio: 'Nenhuma sala cadastrada.',
        artigo: 'A sala',
        motivoBloqueio: 'Há consultas registradas nesta sala.',
    },
];

const listas = {};
const elementos = {};

/** Monta os três cartões (uma vez). */
export function iniciarCadastros() {
    const grade = campo('cadastros');
    if (!grade) return;

    grade.replaceChildren(...CADASTROS.map(montarCartao));
}

function montarCartao(config) {
    const lista = el('ul', { class: 'lista-cadastro' });
    const entrada = el('input', {
        class: 'entrada',
        type: 'text',
        maxlength: config.campoNome === 'identificacao' ? '30' : '100',
        placeholder: config.placeholder,
        'aria-label': config.rotuloAdicionar,
    });
    const adicionar = el('button', { type: 'submit', class: 'btn btn-primary btn-sm', texto: 'Adicionar' });

    const form = el('form', { class: 'form-adicionar', novalidate: '' }, entrada, adicionar);
    form.addEventListener('submit', (evento) => {
        evento.preventDefault();
        criar(config, entrada, adicionar);
    });

    elementos[config.chave] = lista;

    return el('div', { class: 'card' },
        el('div', { class: 'card-cabecalho' }, el('h3', { texto: config.titulo })),
        el('p', { class: 'cadastro-descricao', texto: config.descricao }),
        lista,
        form
    );
}

export async function carregarCadastros({ silencioso = false } = {}) {
    const resultados = await Promise.allSettled(CADASTROS.map((c) => estado.api.get(c.rota)));
    let falha = null;

    resultados.forEach((resultado, indice) => {
        const config = CADASTROS[indice];

        if (resultado.status === 'fulfilled') {
            listas[config.chave] = resultado.value.data;
            if (config.chave === 'especialidades') estado.especialidades = resultado.value.data;
            desenhar(config);
        } else {
            falha ??= resultado.reason;
            elementos[config.chave]?.replaceChildren(el('li', {}, el('p', { class: 'vazio', texto: 'Não foi possível carregar.' })));
        }
    });

    if (falha && !silencioso) {
        avisarErro(falha, {
            titulo: 'Não foi possível carregar os cadastros',
            padrao: 'Não foi possível carregar alguns cadastros. Tente novamente.',
        });
    }
}

function desenhar(config) {
    const itens = listas[config.chave] ?? [];
    const destino = elementos[config.chave];

    if (itens.length === 0) {
        destino.replaceChildren(el('li', {}, el('p', { class: 'vazio', texto: config.vazio })));
        return;
    }

    destino.replaceChildren(...itens.map((item) => {
        const usos = config.usos(item) ?? 0;
        const excluir = botao('Excluir', 'btn btn-perigo btn-sm', () => excluirItem(config, item));

        if (usos > 0) {
            excluir.disabled = true;
            excluir.title = config.motivoBloqueio;
        }

        return el('li', {},
            el('div', { class: 'item-nome' },
                el('span', { texto: item[config.campoNome] }),
                el('small', { texto: config.textoUsos(usos) })
            ),
            excluir
        );
    }));
}

async function criar(config, entrada, botaoAdicionar) {
    const valor = entrada.value.trim();

    if (!valor) {
        await avisar({
            tipo: 'aviso',
            titulo: 'Informe o nome',
            mensagem: `Digite o nome antes de adicionar. Exemplo: ${config.placeholder.replace('Ex.: ', '')}.`,
        });
        entrada.focus();
        return;
    }

    botaoAdicionar.disabled = true;

    try {
        await estado.api.post(config.rota, { [config.campoNome]: valor });
        entrada.value = '';
        avisar({ tipo: 'sucesso', titulo: 'Cadastrado!', mensagem: `"${valor}" foi adicionado em ${config.titulo.toLowerCase()}.`, fecharEm: 3000 });
        await carregarCadastros({ silencioso: true });
    } catch (erro) {
        await avisarErro(erro, {
            titulo: 'Não foi possível cadastrar',
            padrao: 'Não foi possível cadastrar agora. Tente novamente.',
        });
        entrada.focus();
    } finally {
        botaoAdicionar.disabled = false;
    }
}

async function excluirItem(config, item) {
    const nome = item[config.campoNome];

    const confirmado = await confirmar({
        titulo: `Excluir "${nome}"?`,
        mensagem: `${config.artigo} será removid${config.artigo.startsWith('A') ? 'a' : 'o'} da lista. Esta ação não pode ser desfeita.`,
        confirmarTexto: 'Excluir',
        perigo: true,
    });
    if (!confirmado) return;

    try {
        await estado.api.delete(`${config.rota}/${item.id}`);
        avisar({ tipo: 'sucesso', titulo: 'Excluído', mensagem: `"${nome}" foi removid${config.artigo.startsWith('A') ? 'a' : 'o'}.`, fecharEm: 3000 });
    } catch (erro) {
        await avisarErro(erro, {
            titulo: 'Não foi possível excluir',
            padrao: 'Não foi possível excluir agora. Tente novamente.',
        });
    }

    await carregarCadastros({ silencioso: true });
}
