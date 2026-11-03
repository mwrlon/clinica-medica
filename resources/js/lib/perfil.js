import { el } from './painel.js';
import { avisar, avisarErro } from './ui.js';
import { criarCampo, tratarErroDeValidacao } from './formulario.js';

/**
 * Aba "Meu Perfil" dos painéis (paciente, médico e administrador):
 * um cartão para editar os dados pessoais e outro para trocar a senha.
 *
 * O que o servidor recusa volta abaixo do campo e, junto com os demais problemas, em um aviso.
 * Falhas de conexão ou do sistema aparecem só no aviso.
 */

let contador = 0;

/**
 * Cartão com um formulário.
 *
 * - enviar(valores): faz a chamada à API; devolve { titulo, mensagem } para o aviso de sucesso
 * - aposSucesso(resultado, formulario): chamado depois do aviso (ex.: atualizar o cabeçalho, limpar senhas)
 */
function criarCartao({ titulo, descricao, campos, botao, enviar, aposSucesso, estreito = false }) {
    const formId = `perfil-${++contador}`;
    const itens = campos.map((c) => criarCampo(c, formId));

    const salvar = el('button', { type: 'submit', class: 'btn btn-primary', texto: botao });

    const form = el('form', { class: estreito ? 'form-linha form-estreito' : 'form-linha', novalidate: '' },
        ...itens.map((i) => i.elemento),
        el('div', { class: 'campo campo-largo form-acoes' }, salvar)
    );

    form.addEventListener('submit', async (evento) => {
        evento.preventDefault();

        itens.forEach((i) => i.definirErro(''));
        salvar.disabled = true;
        salvar.textContent = 'Salvando…';

        const valores = {};
        for (const i of itens) {
            if (!i.somenteLeitura) valores[i.nome] = i.entrada.value === '' ? null : i.entrada.value;
        }

        try {
            const resultado = await enviar(valores);

            avisar({ tipo: 'sucesso', titulo: resultado.titulo, mensagem: resultado.mensagem, fecharEm: 3800 });
            aposSucesso?.(resultado, itens);
        } catch (erro) {
            if (!(await tratarErroDeValidacao(erro, itens, { mensagem: 'Não foi possível salvar:' }))) {
                avisarErro(erro, {
                    titulo: 'Não foi possível salvar',
                    padrao: 'Não foi possível salvar agora. Tente novamente em instantes.',
                });
            }
        } finally {
            salvar.disabled = false;
            salvar.textContent = botao;
        }
    });

    return el('div', { class: 'card' },
        el('div', { class: 'card-cabecalho' },
            el('div', {},
                el('h3', { texto: titulo }),
                descricao ? el('p', { class: 'texto-suave', texto: descricao }) : null
            )
        ),
        form
    );
}

const SEXOS = [
    { valor: 'M', texto: 'Masculino' },
    { valor: 'F', texto: 'Feminino' },
    { valor: 'Outro', texto: 'Outro' },
];

/** Campos de dados pessoais de cada perfil. */
function camposDeDados(perfil, usuario, convenios) {
    const nome = { nome: 'nome', rotulo: 'Nome completo', maxlength: 150, autocomplete: 'name', largo: true, valor: usuario.nome };
    const email = { nome: 'email', rotulo: 'E-mail', tipo: 'email', maxlength: 150, autocomplete: 'email', valor: usuario.email };
    const telefone = { nome: 'telefone', rotulo: 'Telefone / WhatsApp', tipo: 'tel', maxlength: 20, autocomplete: 'tel', valor: usuario.telefone };

    if (perfil === 'paciente') {
        return [
            nome,
            { nome: 'cpf', rotulo: 'CPF', somenteLeitura: true, valor: usuario.cpf, dica: 'O CPF não pode ser alterado.' },
            { nome: 'data_nascimento', rotulo: 'Data de nascimento', tipo: 'date', autocomplete: 'bday', valor: (usuario.data_nascimento ?? '').slice(0, 10) },
            { nome: 'sexo', rotulo: 'Sexo', opcoes: SEXOS, valor: usuario.sexo },
            telefone,
            email,
            {
                nome: 'convenio_id',
                rotulo: 'Convênio',
                opcoes: [{ valor: '', texto: 'Particular (sem convênio)' }, ...convenios.map((c) => ({ valor: c.id, texto: c.nome }))],
                valor: usuario.convenio_id ?? '',
            },
            { nome: 'endereco', rotulo: 'Endereço (opcional)', maxlength: 255, autocomplete: 'street-address', valor: usuario.endereco ?? '' },
        ];
    }

    if (perfil === 'medico') {
        return [
            nome,
            { nome: 'crm', rotulo: 'CRM', somenteLeitura: true, valor: usuario.crm, dica: 'O CRM é alterado apenas pela administração.' },
            { nome: 'especialidade', rotulo: 'Especialidade', somenteLeitura: true, valor: usuario.especialidade?.nome ?? '' },
            telefone,
            email,
        ];
    }

    return [nome, email];
}

/**
 * Monta a aba "Meu Perfil" no elemento marcado com data-campo="perfil".
 *
 * - perfil:       'paciente' | 'medico' | 'admin'
 * - usuario:      dados atuais (resposta de GET /api/perfil)
 * - aoAtualizar:  recebe o usuário atualizado depois de salvar (para refrescar nome, convênio etc. na tela)
 */
export async function montarPerfil({ api, perfil, usuario, aoAtualizar = () => {} }) {
    const destino = document.querySelector('[data-campo="perfil"]');
    if (!destino) return;

    let convenios = [];
    if (perfil === 'paciente') {
        try {
            convenios = (await api.get('/convenios')).data;
        } catch (erro) { /* sem a lista, o campo mostra só "Particular" e o resto do perfil continua editável */ }
    }

    let atual = usuario;

    const dados = criarCartao({
        titulo: 'Dados pessoais',
        descricao: 'Mantenha seus dados atualizados para que a clínica consiga falar com você.',
        campos: camposDeDados(perfil, atual, convenios),
        botao: 'Salvar alterações',
        enviar: async (valores) => {
            const { data } = await api.put('/perfil', valores);
            atual = data.usuario;
            return { titulo: 'Dados atualizados!', mensagem: 'As suas informações foram salvas com sucesso.' };
        },
        aposSucesso: () => aoAtualizar(atual),
    });

    const senha = criarCartao({
        titulo: 'Alterar senha',
        descricao: 'Por segurança, os outros aparelhos conectados à sua conta serão desconectados.',
        estreito: true,
        campos: [
            { nome: 'senha_atual', rotulo: 'Senha atual', tipo: 'password', autocomplete: 'current-password', largo: true },
            { nome: 'senha', rotulo: 'Nova senha', tipo: 'password', autocomplete: 'new-password', largo: true, dica: 'Mínimo de 8 caracteres.' },
            { nome: 'confirmar_senha', rotulo: 'Confirmar nova senha', tipo: 'password', autocomplete: 'new-password', largo: true },
        ],
        botao: 'Alterar senha',
        enviar: async (valores) => {
            await api.put('/perfil/senha', valores);
            return { titulo: 'Senha alterada!', mensagem: 'Use a nova senha no seu próximo acesso.' };
        },
        aposSucesso: (_resultado, itens) => itens.forEach((i) => { i.entrada.value = ''; }),
    });

    destino.replaceChildren(dados, senha);
}
