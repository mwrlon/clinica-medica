import { el } from './painel.js';
import { avisar } from './ui.js';

/**
 * Peças de formulário compartilhadas pelas janelas e abas (perfil, painel do administrador):
 * campo com rótulo + mensagem de erro, e o tratamento dos erros de validação (422) do servidor.
 */

/** Campo do formulário: rótulo + entrada (input/select) + espaço para a mensagem de erro. */
export function criarCampo(c, prefixo) {
    const id = `${prefixo}-${c.nome}`;
    const idErro = `${id}-erro`;

    let entrada;

    if (c.opcoes) {
        entrada = el('select', { id, name: c.nome },
            ...c.opcoes.map((o) => el('option', { value: String(o.valor), texto: o.texto }))
        );
        entrada.value = String(c.valor ?? '');
    } else {
        entrada = el('input', {
            id,
            name: c.nome,
            type: c.tipo ?? 'text',
            value: c.valor ?? '',
            ...(c.maxlength ? { maxlength: String(c.maxlength) } : {}),
            ...(c.autocomplete ? { autocomplete: c.autocomplete } : {}),
            ...(c.placeholder ? { placeholder: c.placeholder } : {}),
        });
    }

    if (c.somenteLeitura) {
        entrada.setAttribute('readonly', '');
        entrada.setAttribute('aria-readonly', 'true');
        entrada.classList.add('somente-leitura');
    }

    entrada.setAttribute('aria-describedby', idErro);

    const erro = el('span', { id: idErro, class: 'campo-erro', role: 'alert' });
    erro.hidden = true;

    const elemento = el('div', { class: c.largo ? 'campo campo-largo' : 'campo' },
        el('label', { for: id, texto: c.rotulo }),
        entrada,
        c.dica ? el('span', { class: 'campo-ajuda', texto: c.dica }) : null,
        erro
    );

    return {
        nome: c.nome,
        elemento,
        entrada,
        somenteLeitura: Boolean(c.somenteLeitura),
        definirErro(texto) {
            erro.textContent = texto;
            erro.hidden = !texto;
            if (texto) entrada.setAttribute('aria-invalid', 'true');
            else entrada.removeAttribute('aria-invalid');
        },
    };
}

/**
 * Trata um erro 422 (validação) da API: mostra a mensagem abaixo de cada campo e abre um aviso com
 * a lista de problemas. Devolve false (sem fazer nada) se o erro não for de validação.
 *
 * - itens: campos criados por criarCampo()
 */
export async function tratarErroDeValidacao(erro, itens, { titulo = 'Revise os dados informados', mensagem = 'Não foi possível salvar:' } = {}) {
    if (erro?.response?.status !== 422 || !erro.response.data?.errors) return false;

    const problemas = [];
    let primeiro = null;

    for (const [nome, mensagens] of Object.entries(erro.response.data.errors)) {
        problemas.push(mensagens[0]);

        const item = itens.find((i) => i.nome === nome);
        if (item) {
            item.definirErro(mensagens[0]);
            primeiro ??= item;
        }
    }

    await avisar({ tipo: 'aviso', titulo, mensagem, itens: problemas, botao: 'Corrigir dados' });

    primeiro?.entrada.focus();
    return true;
}
