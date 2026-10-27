import { iniciarPainel, ligarLogout } from '../lib/sessao.js';
import {
    el, campo, definirTexto, dataBR, dataHoraBR, dataLonga, horaCurta, ehProxima, ativarMenuPorSecao,
} from '../lib/painel.js';
import { criarModal, confirmar, toast, mensagemDeErro } from '../lib/ui.js';
import { criarSeletorHorarios } from '../lib/horarios.js';

const ENDERECO_CLINICA = 'ProSaúde — Rua das Acadias, 482 (Erechim, RS)';

document.addEventListener('DOMContentLoaded', async () => {
    // Proteção de rota via front-end: exige login de paciente
    const api = iniciarPainel('paciente');
    if (!api) return;

    ligarLogout(api, 'paciente');
    ativarMenuPorSecao();

    document.getElementById('btn-agendar')?.addEventListener('click', () => abrirAgendamento(api));

    const [rPerfil] = await Promise.all([
        api.get('/perfil').then((r) => r, () => null),
        carregarDados(api),
    ]);

    if (rPerfil) preencherPerfil(rPerfil.data.usuario);
});

/** Busca consultas e exames e redesenha tudo (usado ao abrir a página e depois de cada ação). */
async function carregarDados(api) {
    const [rConsultas, rExames] = await Promise.allSettled([
        api.get('/consultas'),
        api.get('/exames'),
    ]);

    // null = a requisição falhou (diferente de lista vazia)
    const consultas = rConsultas.status === 'fulfilled' ? rConsultas.value.data : null;
    const exames = rExames.status === 'fulfilled' ? rExames.value.data : null;

    renderConsultas(api, consultas);
    renderExamesEReceitas(consultas, exames);
    renderLembretes(consultas);
}

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

function renderConsultas(api, consultas) {
    const lista = campo('lista-consultas');
    const titulo = campo('titulo-consultas');

    lista.replaceChildren();

    if (consultas === null) {
        titulo.textContent = 'Próxima Consulta';
        lista.append(el('p', { class: 'vazio', texto: 'Não foi possível carregar suas consultas. Tente novamente em instantes.' }));
        return;
    }

    const proximas = consultas.filter((consulta) => ehProxima(consulta));
    titulo.textContent = proximas.length > 1 ? 'Próximas Consultas' : 'Próxima Consulta';

    if (proximas.length === 0) {
        lista.append(el('p', { class: 'vazio', texto: 'Você não tem consultas agendadas. Use o botão "Agendar Nova Consulta" para marcar a próxima.' }));
        return;
    }

    lista.append(...proximas.map((consulta) => cartaoConsulta(api, consulta)));
}

function cartaoConsulta(api, consulta) {
    const medico = consulta.medico?.nome ?? 'Médico';
    const especialidade = consulta.medico?.especialidade?.nome;
    const local = consulta.sala ? `${ENDERECO_CLINICA} · ${consulta.sala.identificacao}` : ENDERECO_CLINICA;

    const info = el('div', { class: 'consulta-info' },
        el('div', { class: 'consulta-topo' },
            el('h3', { class: 'item-titulo consulta-nome', texto: medico }),
            especialidade ? el('span', { class: 'tag', texto: especialidade }) : null
        ),
        el('div', { class: 'consulta-dados' },
            el('p', {}, el('strong', { texto: 'Data:' }), ` ${dataLonga(consulta.data)} às ${horaCurta(consulta.hora)}`),
            el('p', {}, el('strong', { texto: 'Local:' }), ` ${local}`),
            consulta.observacoes ? el('p', {}, el('strong', { texto: 'Motivo:' }), ` ${consulta.observacoes}`) : null
        )
    );

    const reagendar = el('button', { type: 'button', class: 'btn btn-outline btn-sm', texto: 'Reagendar' });
    const cancelar = el('button', { type: 'button', class: 'btn btn-perigo btn-sm', texto: 'Cancelar' });

    reagendar.addEventListener('click', () => abrirReagendamento(api, consulta));
    cancelar.addEventListener('click', () => cancelarConsulta(api, consulta, cancelar));

    return el('article', { class: 'consulta' },
        el('div', { class: 'consulta-barra', 'aria-hidden': 'true' }),
        info,
        el('div', { class: 'consulta-acoes' }, reagendar, cancelar)
    );
}

async function cancelarConsulta(api, consulta, botao) {
    const medico = consulta.medico?.nome ?? 'o médico';

    const confirmado = await confirmar({
        titulo: 'Cancelar consulta?',
        mensagem: `Cancelar a consulta com ${medico} em ${dataBR(consulta.data)} às ${horaCurta(consulta.hora)}? O horário volta a ficar livre para outros pacientes.`,
        confirmarTexto: 'Cancelar consulta',
        voltarTexto: 'Manter consulta',
        perigo: true,
    });
    if (!confirmado) return;

    botao.disabled = true;
    try {
        await api.put(`/consultas/${consulta.id}/cancelar`);
        toast('Consulta cancelada.');
    } catch (erro) {
        toast(mensagemDeErro(erro, 'Não foi possível cancelar a consulta. Tente novamente.'), 'erro');
    }

    // Redesenha nos dois casos: o estado da consulta pode ter mudado
    await carregarDados(api);
}

/* ------------------------------------------------------------------ */
/* Agendar nova consulta                                               */
/* ------------------------------------------------------------------ */

async function abrirAgendamento(api) {
    const modal = criarModal({
        titulo: 'Agendar nova consulta',
        subtitulo: 'Escolha a especialidade, o médico e um horário livre.',
    });

    const botaoFechar = el('button', { type: 'button', class: 'btn btn-outline', texto: 'Fechar' });
    botaoFechar.addEventListener('click', () => modal.fechar());

    modal.corpo.append(el('p', { class: 'vazio', texto: 'Carregando médicos…' }));

    let especialidades;
    let medicos;
    try {
        const [rEspecialidades, rMedicos] = await Promise.all([api.get('/especialidades'), api.get('/medicos')]);
        especialidades = rEspecialidades.data;
        medicos = rMedicos.data;
    } catch (erro) {
        modal.corpo.replaceChildren(el('p', { class: 'erro-form', texto: mensagemDeErro(erro, 'Não foi possível carregar os médicos. Tente novamente.') }));
        modal.rodape.append(botaoFechar);
        return;
    }

    const selectEspecialidade = el('select', { id: 'ag-especialidade' },
        el('option', { value: '', texto: 'Todas as especialidades' }),
        ...especialidades.map((e) => el('option', { value: String(e.id), texto: e.nome }))
    );
    const selectMedico = el('select', { id: 'ag-medico' });
    const motivo = el('textarea', {
        id: 'ag-motivo',
        maxlength: '500',
        placeholder: 'Ex.: dor de cabeça frequente, retorno de exame…',
    });
    const erro = el('p', { class: 'erro-form', role: 'alert' });
    erro.hidden = true;

    const confirmarAgendamento = el('button', { type: 'submit', form: 'form-agendar', class: 'btn btn-primary', texto: 'Agendar consulta' });
    confirmarAgendamento.disabled = true;

    const seletor = criarSeletorHorarios({ api, aoMudar: validar });

    function validar() {
        const { data, hora } = seletor.valor();
        confirmarAgendamento.disabled = !(selectMedico.value && data && hora);
    }

    function preencherMedicos() {
        const especialidade = selectEspecialidade.value;
        const lista = medicos.filter((m) => !especialidade || String(m.especialidade_id) === especialidade);

        selectMedico.replaceChildren(
            el('option', { value: '', texto: lista.length ? 'Selecione o médico' : 'Nenhum médico nesta especialidade' }),
            ...lista.map((m) => el('option', {
                value: String(m.id),
                texto: !especialidade && m.especialidade ? `${m.nome} — ${m.especialidade.nome}` : m.nome,
            }))
        );
        seletor.definirMedico(null);
    }

    selectEspecialidade.addEventListener('change', preencherMedicos);
    selectMedico.addEventListener('change', () => seletor.definirMedico(Number(selectMedico.value)));

    const formulario = el('form', { id: 'form-agendar', class: 'form-grid', novalidate: '' },
        el('div', { class: 'form-linha' },
            el('div', { class: 'campo' }, el('label', { for: 'ag-especialidade', texto: 'Especialidade' }), selectEspecialidade),
            el('div', { class: 'campo' }, el('label', { for: 'ag-medico', texto: 'Médico' }), selectMedico)
        ),
        seletor.elemento,
        el('div', { class: 'campo' },
            el('label', { for: 'ag-motivo', texto: 'Motivo da consulta (opcional)' }),
            motivo
        ),
        erro
    );

    formulario.addEventListener('submit', async (evento) => {
        evento.preventDefault();

        const { data, hora } = seletor.valor();
        if (!selectMedico.value || !data || !hora) return;

        erro.hidden = true;
        confirmarAgendamento.disabled = true;
        confirmarAgendamento.textContent = 'Agendando…';

        try {
            await api.post('/consultas', {
                medico_id: Number(selectMedico.value),
                data,
                hora,
                observacoes: motivo.value.trim() || null,
            });

            modal.fechar();
            toast(`Consulta agendada para ${dataBR(data)} às ${hora}.`);
            await carregarDados(api);
        } catch (e) {
            erro.textContent = mensagemDeErro(e, 'Não foi possível agendar a consulta. Tente novamente.');
            erro.hidden = false;
            confirmarAgendamento.textContent = 'Agendar consulta';
            // O horário pode ter sido ocupado por outra pessoa: recarrega os horários livres do dia
            seletor.definirData(data);
        }
    });

    modal.corpo.replaceChildren(formulario);

    const cancelarJanela = el('button', { type: 'button', class: 'btn btn-outline', texto: 'Cancelar' });
    cancelarJanela.addEventListener('click', () => modal.fechar());
    modal.rodape.append(cancelarJanela, confirmarAgendamento);

    preencherMedicos();
    selectEspecialidade.focus();
}

/* ------------------------------------------------------------------ */
/* Reagendar                                                           */
/* ------------------------------------------------------------------ */

function abrirReagendamento(api, consulta) {
    const medico = consulta.medico?.nome ?? 'Médico';
    const dataAtual = consulta.data.slice(0, 10);
    const horaAtual = horaCurta(consulta.hora);

    const modal = criarModal({
        titulo: 'Reagendar consulta',
        subtitulo: `${medico} · atualmente em ${dataBR(dataAtual)} às ${horaAtual}`,
    });

    const erro = el('p', { class: 'erro-form', role: 'alert' });
    erro.hidden = true;

    const confirmarNovoHorario = el('button', { type: 'submit', form: 'form-reagendar', class: 'btn btn-primary', texto: 'Confirmar novo horário' });
    confirmarNovoHorario.disabled = true;

    const seletor = criarSeletorHorarios({ api, medicoId: consulta.medico_id, ignorar: consulta.id, aoMudar: validar });

    function validar() {
        const { data, hora } = seletor.valor();
        const igualAoAtual = data === dataAtual && hora === horaAtual;
        confirmarNovoHorario.disabled = !(data && hora) || igualAoAtual;
    }

    const formulario = el('form', { id: 'form-reagendar', class: 'form-grid', novalidate: '' }, seletor.elemento, erro);

    formulario.addEventListener('submit', async (evento) => {
        evento.preventDefault();

        const { data, hora } = seletor.valor();
        if (!data || !hora) return;

        erro.hidden = true;
        confirmarNovoHorario.disabled = true;
        confirmarNovoHorario.textContent = 'Reagendando…';

        try {
            await api.put(`/consultas/${consulta.id}/reagendar`, { data, hora });

            modal.fechar();
            toast(`Consulta reagendada para ${dataBR(data)} às ${hora}.`);
            await carregarDados(api);
        } catch (e) {
            erro.textContent = mensagemDeErro(e, 'Não foi possível reagendar a consulta. Tente novamente.');
            erro.hidden = false;
            confirmarNovoHorario.textContent = 'Confirmar novo horário';
            seletor.definirData(data);
        }
    });

    modal.corpo.append(formulario);

    const cancelarJanela = el('button', { type: 'button', class: 'btn btn-outline', texto: 'Cancelar' });
    cancelarJanela.addEventListener('click', () => modal.fechar());
    modal.rodape.append(cancelarJanela, confirmarNovoHorario);
}

/* ------------------------------------------------------------------ */
/* Exames e receitas                                                   */
/* ------------------------------------------------------------------ */

function urlSegura(url) {
    return /^(https?:\/\/|\/)/i.test(url ?? '');
}

/** Botão que mostra/oculta um texto logo abaixo (resultado de exame, receita) */
function linkExpansivel(rotulo, conteudo) {
    const detalhe = el('p', { class: 'detalhe-texto', texto: conteudo });
    detalhe.hidden = true;

    const botao = el('button', { type: 'button', class: 'link-acao', 'aria-expanded': 'false', texto: rotulo });
    botao.addEventListener('click', () => {
        detalhe.hidden = !detalhe.hidden;
        botao.setAttribute('aria-expanded', String(!detalhe.hidden));
    });

    const fragmento = document.createDocumentFragment();
    fragmento.append(botao, detalhe);
    return fragmento;
}

function itemComIcone(icone, texto) {
    return el('article', { class: 'item-exame' },
        el('div', { class: 'avatar avatar-suave', 'aria-hidden': 'true', texto: icone }),
        texto
    );
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

    const texto = el('div', { class: 'item-exame-texto' },
        el('h3', { class: 'item-titulo', texto: exame.nome }),
        el('p', { texto: situacao })
    );

    if (urlSegura(exame.arquivo_resultado)) {
        texto.append(el('a', { class: 'link-acao', href: exame.arquivo_resultado, target: '_blank', rel: 'noopener', texto: 'Download Resultado (PDF)' }));
    } else if (exame.resultado) {
        texto.append(linkExpansivel('Ver Resultado', exame.resultado));
    }

    return itemComIcone('📄', texto);
}

function cartaoReceita(consulta) {
    const texto = el('div', { class: 'item-exame-texto' },
        el('h3', { class: 'item-titulo', texto: 'Receita Médica Digital' }),
        el('p', { texto: `Emitida por: ${consulta.medico?.nome ?? 'Médico'} em ${dataBR(consulta.data)}` })
    );
    texto.append(linkExpansivel('Ver Receita Digital', consulta.atendimento.receita));

    return itemComIcone('💊', texto);
}

function renderExamesEReceitas(consultas, exames) {
    const lista = campo('lista-exames');
    lista.replaceChildren();

    const mensagem = (texto) => el('p', { class: 'vazio', texto });

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
