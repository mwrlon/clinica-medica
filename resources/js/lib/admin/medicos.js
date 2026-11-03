import { el, campo } from '../painel.js';
import { criarModal, confirmar, avisar, avisarErro } from '../ui.js';
import { criarCampo, tratarErroDeValidacao } from '../formulario.js';
import { estado, debounce, simplificar, linhaVazia, celulaDupla, etiqueta, botao } from './comum.js';

/** Aba "Médicos": lista com busca e filtro, cadastro, edição, inativação e exclusão. */
let contador = 0;

export function iniciarMedicos() {
    campo('busca-medicos').addEventListener('input', debounce(desenhar, 150));
    campo('filtro-status-medicos').addEventListener('change', desenhar);
    document.getElementById('btn-novo-medico')?.addEventListener('click', () => abrirFormulario(null));
}

export async function carregarMedicos({ silencioso = false } = {}) {
    try {
        const { data } = await estado.api.get('/admin/medicos');
        estado.medicos = data;
        desenhar();
    } catch (erro) {
        if (silencioso) return;
        campo('lista-medicos').replaceChildren(linhaVazia(6, 'Não foi possível carregar os médicos.'));
        avisarErro(erro, {
            titulo: 'Não foi possível carregar os médicos',
            padrao: 'Não foi possível carregar a lista de médicos. Tente novamente.',
        });
    }
}

function filtrados() {
    const busca = simplificar(campo('busca-medicos').value.trim());
    const status = campo('filtro-status-medicos').value;

    return estado.medicos.filter((m) =>
        (!status || m.status === status)
        && (!busca || simplificar(`${m.nome} ${m.crm} ${m.email}`).includes(busca)));
}

function desenhar() {
    const corpo = campo('lista-medicos');
    const lista = filtrados();

    if (lista.length === 0) {
        corpo.replaceChildren(linhaVazia(6, estado.medicos.length
            ? 'Nenhum médico encontrado com estes filtros.'
            : 'Nenhum médico cadastrado ainda. Use "Novo médico" para cadastrar o primeiro.'));
        return;
    }

    corpo.replaceChildren(...lista.map(linha));
}

function linha(medico) {
    const excluir = botao('Excluir', 'btn btn-perigo btn-sm', () => excluirMedico(medico));
    if (medico.consultas_count > 0) {
        excluir.disabled = true;
        excluir.title = 'Este médico tem consultas registradas. Inative o cadastro em vez de excluir.';
    }

    return el('tr', {},
        celulaDupla(medico.nome, medico.crm),
        el('td', { texto: medico.especialidade?.nome ?? '—' }),
        celulaDupla(medico.telefone, medico.email),
        el('td', {}, etiqueta(medico.status)),
        el('td', { class: 'numero', texto: String(medico.consultas_count ?? 0) }),
        el('td', {}, el('div', { class: 'acoes-linha' },
            botao('Editar', 'btn btn-outline btn-sm', () => abrirFormulario(medico)),
            botao(medico.status === 'ativo' ? 'Inativar' : 'Reativar', 'btn btn-outline btn-sm', () => alternarStatus(medico)),
            excluir
        ))
    );
}

/* ------------------------------------------------------------------ */
/* Cadastro e edição                                                    */
/* ------------------------------------------------------------------ */

function abrirFormulario(medico) {
    const editando = medico !== null;
    const prefixo = `medico-${++contador}`;

    const modal = criarModal({
        titulo: editando ? 'Editar médico' : 'Novo médico',
        subtitulo: editando
            ? 'Altere os dados e salve. A senha só muda se você preencher o campo.'
            : 'O médico entra no portal com o e-mail e a senha informados aqui.',
    });

    const itens = [
        criarCampo({ nome: 'nome', rotulo: 'Nome completo', maxlength: 150, largo: true, valor: medico?.nome, placeholder: 'Ex.: Dra. Ana Souza' }, prefixo),
        criarCampo({ nome: 'crm', rotulo: 'CRM', maxlength: 20, valor: medico?.crm, placeholder: 'RS-123456', dica: 'UF e número, como em RS-123456.' }, prefixo),
        criarCampo({
            nome: 'especialidade_id',
            rotulo: 'Especialidade',
            opcoes: [{ valor: '', texto: 'Selecione' }, ...estado.especialidades.map((e) => ({ valor: e.id, texto: e.nome }))],
            valor: medico?.especialidade_id ?? '',
        }, prefixo),
        criarCampo({ nome: 'telefone', rotulo: 'Telefone', tipo: 'tel', maxlength: 20, valor: medico?.telefone, placeholder: '(54) 99999-0000' }, prefixo),
        criarCampo({ nome: 'email', rotulo: 'E-mail (login)', tipo: 'email', maxlength: 150, valor: medico?.email, autocomplete: 'off' }, prefixo),
        criarCampo({
            nome: 'senha',
            rotulo: editando ? 'Nova senha (opcional)' : 'Senha inicial',
            tipo: 'password',
            autocomplete: 'new-password',
            dica: editando ? 'Deixe em branco para manter a senha atual.' : 'Mínimo de 8 caracteres.',
        }, prefixo),
    ];

    if (editando) {
        itens.push(criarCampo({
            nome: 'status',
            rotulo: 'Situação',
            opcoes: [{ valor: 'ativo', texto: 'Ativo' }, { valor: 'inativo', texto: 'Inativo' }],
            valor: medico.status,
        }, prefixo));
    }

    const salvar = el('button', { type: 'submit', form: prefixo, class: 'btn btn-primary', texto: editando ? 'Salvar alterações' : 'Cadastrar médico' });
    const textoSalvar = salvar.textContent;

    const form = el('form', { id: prefixo, class: 'form-linha', novalidate: '' }, ...itens.map((i) => i.elemento));

    form.addEventListener('submit', async (evento) => {
        evento.preventDefault();

        itens.forEach((i) => i.definirErro(''));
        salvar.disabled = true;
        salvar.textContent = 'Salvando…';

        const valores = {};
        for (const i of itens) valores[i.nome] = i.entrada.value.trim() === '' ? null : i.entrada.value.trim();
        if (valores.crm) valores.crm = valores.crm.toUpperCase();
        if (valores.senha === null) delete valores.senha; // sem senha nova, a atual continua valendo

        try {
            if (editando) await estado.api.put(`/admin/medicos/${medico.id}`, valores);
            else await estado.api.post('/admin/medicos', valores);

            modal.fechar();
            avisar({
                tipo: 'sucesso',
                titulo: editando ? 'Médico atualizado!' : 'Médico cadastrado!',
                mensagem: `${valores.nome} ${editando ? 'teve os dados salvos.' : 'já pode ser escolhido nos agendamentos e entrar no portal.'}`,
                fecharEm: 4000,
            });
            await carregarMedicos({ silencioso: true });
        } catch (erro) {
            if (!(await tratarErroDeValidacao(erro, itens, { mensagem: 'Não foi possível salvar o médico:' }))) {
                avisarErro(erro, {
                    titulo: 'Não foi possível salvar',
                    padrao: 'Não foi possível salvar o médico agora. Tente novamente.',
                });
            }
        } finally {
            salvar.disabled = false;
            salvar.textContent = textoSalvar;
        }
    });

    modal.corpo.append(form);

    const cancelar = botao('Cancelar', 'btn btn-outline', () => modal.fechar());
    modal.rodape.append(cancelar, salvar);

    itens[0].entrada.focus();
}

/* ------------------------------------------------------------------ */
/* Inativar, reativar e excluir                                         */
/* ------------------------------------------------------------------ */

async function alternarStatus(medico) {
    const inativar = medico.status === 'ativo';

    const confirmado = await confirmar({
        titulo: inativar ? 'Inativar médico?' : 'Reativar médico?',
        mensagem: inativar
            ? `${medico.nome} deixa de aparecer nos agendamentos e perde o acesso ao portal. As consultas já registradas continuam no histórico.`
            : `${medico.nome} volta a aparecer nos agendamentos e pode entrar no portal novamente.`,
        confirmarTexto: inativar ? 'Inativar' : 'Reativar',
        perigo: inativar,
    });
    if (!confirmado) return;

    try {
        await estado.api.put(`/admin/medicos/${medico.id}`, { status: inativar ? 'inativo' : 'ativo' });
        avisar({
            tipo: 'sucesso',
            titulo: inativar ? 'Médico inativado' : 'Médico reativado',
            mensagem: `${medico.nome} foi ${inativar ? 'inativado' : 'reativado'}.`,
            fecharEm: 3500,
        });
    } catch (erro) {
        await avisarErro(erro, {
            titulo: 'Não foi possível alterar a situação',
            padrao: 'Não foi possível alterar a situação do médico. Tente novamente.',
        });
    }

    await carregarMedicos({ silencioso: true });
}

async function excluirMedico(medico) {
    const confirmado = await confirmar({
        titulo: 'Excluir médico?',
        mensagem: `${medico.nome} será removido da base de dados. Esta ação não pode ser desfeita.`,
        confirmarTexto: 'Excluir médico',
        perigo: true,
    });
    if (!confirmado) return;

    try {
        await estado.api.delete(`/admin/medicos/${medico.id}`);
        avisar({ tipo: 'sucesso', titulo: 'Médico excluído', mensagem: `${medico.nome} foi removido.`, fecharEm: 3500 });
    } catch (erro) {
        await avisarErro(erro, {
            titulo: 'Não foi possível excluir',
            padrao: 'Não foi possível excluir o médico. Tente novamente.',
        });
    }

    await carregarMedicos({ silencioso: true });
}
