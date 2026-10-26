import { iniciarPainel, ligarLogout } from '../lib/sessao.js';
import { el, campo, definirTexto, dataBR, dataHoraBR, dataLonga, horaCurta, ehProxima } from '../lib/painel.js';

const ENDERECO_CLINICA = 'ProSaúde — Rua das Acadias, 482 (Erechim, RS)';

document.addEventListener('DOMContentLoaded', async () => {
    // Proteção de rota via front-end: exige login de paciente
    const api = iniciarPainel('paciente');
    if (!api) return;

    ligarLogout(api, 'paciente');

    const [rPerfil, rConsultas, rExames] = await Promise.allSettled([
        api.get('/perfil'),
        api.get('/consultas'),
        api.get('/exames'),
    ]);

    if (rPerfil.status === 'fulfilled') preencherPerfil(rPerfil.value.usuario);

    // null = a requisição falhou (diferente de lista vazia)
    const consultas = rConsultas.status === 'fulfilled' ? rConsultas.value : null;
    const exames = rExames.status === 'fulfilled' ? rExames.value : null;

    renderConsultas(api, consultas);
    renderExamesEReceitas(consultas, exames);
    renderLembretes(consultas);
});

/* ------------------------------------------------------------------ */
/* Perfil                                                              */
/* ------------------------------------------------------------------ */

function preencherPerfil(paciente) {
    definirTexto('nome', paciente.nome);
    definirTexto('cpf', `CPF: ${paciente.cpf}`);
    definirTexto('saudacao', `Olá, ${paciente.nome.split(' ')[0]}! 👋`);

    campo('convenio')?.replaceChildren(
        el('strong', { texto: 'Convênio:' }),
        ` ${paciente.convenio?.nome ?? 'Particular'}`
    );
    campo('titular')?.replaceChildren(
        el('strong', { texto: 'Titular:' }),
        ` ${paciente.nome}`
    );
}

/* ------------------------------------------------------------------ */
/* Próximas consultas                                                  */
/* ------------------------------------------------------------------ */

function cartaoMensagem(texto) {
    return el('div', {}, el('div', {}, el('p', { texto })));
}

function renderConsultas(api, consultas) {
    const secao = document.getElementById('consultas');
    const titulo = campo('titulo-consultas');

    secao.querySelectorAll(':scope > div').forEach((cartao) => cartao.remove());

    if (consultas === null) {
        titulo.textContent = 'Próxima Consulta';
        secao.append(cartaoMensagem('Não foi possível carregar suas consultas. Tente novamente em instantes.'));
        return;
    }

    const proximas = consultas.filter((consulta) => ehProxima(consulta));
    titulo.textContent = proximas.length > 1 ? 'Próximas Consultas' : 'Próxima Consulta';

    if (proximas.length === 0) {
        secao.append(cartaoMensagem('Você não tem consultas agendadas.'));
        return;
    }

    proximas.forEach((consulta) => secao.append(cartaoConsulta(api, consulta)));
}

function cartaoConsulta(api, consulta) {
    const medico = consulta.medico?.nome ?? 'Médico';
    const local = consulta.sala ? `${ENDERECO_CLINICA} · ${consulta.sala.identificacao}` : ENDERECO_CLINICA;

    const info = el('div', {},
        el('h3', { texto: medico }),
        el('p', { texto: consulta.medico?.especialidade?.nome ?? '' }),
        el('p', {}, el('strong', { texto: 'Data:' }), ` ${dataLonga(consulta.data)} às ${horaCurta(consulta.hora)}`),
        el('p', {}, el('strong', { texto: 'Local:' }), ` ${local}`)
    );

    const reagendar = el('button', { texto: 'Reagendar' });
    const cancelar = el('button', { texto: 'Cancelar' });

    cancelar.addEventListener('click', async () => {
        const pergunta = `Cancelar a consulta com ${medico} em ${dataBR(consulta.data)} às ${horaCurta(consulta.hora)}?`;
        if (!window.confirm(pergunta)) return;

        cancelar.disabled = true;
        try {
            await api.put(`/consultas/${consulta.id}/cancelar`);
            const consultas = await api.get('/consultas');
            renderConsultas(api, consultas);
        } catch (erro) {
            cancelar.disabled = false;
            window.alert(erro.response?.data?.mensagem ?? 'Não foi possível cancelar a consulta. Tente novamente.');
        }
    });

    // A separação entre vários cartões seguidos é feita pelo CSS (#consultas > div + div)
    return el('div', {}, info, el('div', {}, reagendar, cancelar));
}

/* ------------------------------------------------------------------ */
/* Exames e receitas                                                   */
/* ------------------------------------------------------------------ */

function urlSegura(url) {
    return /^(https?:\/\/|\/)/i.test(url ?? '');
}

/** Link que mostra/oculta um texto logo abaixo (resultado de exame, receita) */
function linkExpansivel(rotulo, conteudo) {
    const detalhe = el('p', { texto: conteudo, style: 'white-space: pre-line' });
    detalhe.hidden = true;

    const link = el('a', { href: '#', texto: rotulo });
    link.addEventListener('click', (e) => {
        e.preventDefault();
        detalhe.hidden = !detalhe.hidden;
    });

    const fragmento = document.createDocumentFragment();
    fragmento.append(link, detalhe);
    return fragmento;
}

function cartaoExame(exame) {
    let situacao;
    if (exame.status === 'resultado_disponivel') {
        situacao = `Resultado disponível em: ${dataHoraBR(exame.disponibilizado_em ?? exame.solicitado_em)}`;
    } else if (exame.status === 'realizado') {
        situacao = `Realizado em: ${dataHoraBR(exame.solicitado_em)} (aguardando resultado)`;
    } else {
        situacao = `Solicitado em: ${dataHoraBR(exame.solicitado_em)}`;
    }

    const texto = el('div', {}, el('h4', { texto: exame.nome }), el('p', { texto: situacao }));

    if (urlSegura(exame.arquivo_resultado)) {
        texto.append(el('a', { href: exame.arquivo_resultado, target: '_blank', rel: 'noopener', texto: 'Download Resultado (PDF)' }));
    } else if (exame.resultado) {
        texto.append(linkExpansivel('Ver Resultado', exame.resultado));
    }

    return el('div', {}, el('div', { texto: '📄' }), texto);
}

function cartaoReceita(consulta) {
    const texto = el('div', {},
        el('h4', { texto: 'Receita Médica Digital' }),
        el('p', { texto: `Emitida por: ${consulta.medico?.nome ?? 'Médico'} em ${dataBR(consulta.data)}` })
    );
    texto.append(linkExpansivel('Ver Receita Digital', consulta.atendimento.receita));

    return el('div', {}, el('div', { texto: '💊' }), texto);
}

function renderExamesEReceitas(consultas, exames) {
    const lista = campo('lista-exames');
    lista.replaceChildren();

    const mensagem = (texto) => el('p', { texto, style: 'grid-column: 1 / -1' });

    if (consultas === null && exames === null) {
        lista.append(mensagem('Não foi possível carregar seus exames e receitas. Tente novamente em instantes.'));
        return;
    }

    const itens = [];

    for (const exame of exames ?? []) {
        itens.push({ quando: new Date(exame.solicitado_em).getTime(), cartao: cartaoExame(exame) });
    }
    for (const consulta of consultas ?? []) {
        if (consulta.atendimento?.receita) {
            itens.push({ quando: new Date(`${consulta.data}T${consulta.hora}`).getTime(), cartao: cartaoReceita(consulta) });
        }
    }

    if (itens.length === 0) {
        lista.append(mensagem('Nenhum exame ou receita registrado até o momento.'));
        return;
    }

    itens
        .sort((a, b) => b.quando - a.quando)
        .slice(0, 6)
        .forEach((item) => lista.append(item.cartao));
}

/* ------------------------------------------------------------------ */
/* Lembretes de medicamentos (a partir da receita mais recente)         */
/* ------------------------------------------------------------------ */

function renderLembretes(consultas) {
    const lista = campo('lembretes');
    lista.replaceChildren();

    // As consultas vêm em ordem cronológica: a última com receita é a mais recente
    const ultima = [...(consultas ?? [])].reverse().find((consulta) => consulta.atendimento?.receita);

    if (!ultima) {
        lista.append(el('li', {}, el('p', { texto: 'Nenhum medicamento prescrito.' })));
        return;
    }

    ultima.atendimento.receita
        .split('\n')
        .map((linha) => linha.trim())
        .filter(Boolean)
        .slice(0, 4)
        .forEach((linha) => {
            const [medicamento, ...posologia] = linha.split(' — ');
            lista.append(el('li', {},
                el('strong', { texto: medicamento }),
                posologia.length ? el('p', { texto: posologia.join(' — ') }) : null
            ));
        });
}
