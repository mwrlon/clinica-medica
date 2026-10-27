import { iniciarPainel, ligarLogout } from '../lib/sessao.js';
import {
    el, campo, definirTexto, dois, hojeISO, horaAgora, dataBR, dataLonga, horaCurta, idade,
    ehProxima, slugDoNome, iniciais, diaDaSemana, ativarMenuPorSecao, MESES_CURTOS,
} from '../lib/painel.js';
import { criarModal, toast, mensagemDeErro } from '../lib/ui.js';
import { criarSeletorHorarios } from '../lib/horarios.js';

const ENDERECO_CLINICA = 'Rua das Acadias, 482 — Erechim, RS';

const STATUS = { agendada: 'Agendado', realizada: 'Concluído', cancelada: 'Cancelado' };
const STATUS_TAG = { agendada: 'tag', realizada: 'tag tag-ok', cancelada: 'tag tag-neutra' };
const STATUS_EXAME = { solicitado: 'Solicitado', realizado: 'Realizado', resultado_disponivel: 'Resultado disponível' };
const SEXO = { M: 'Masculino', F: 'Feminino' };

const EXAMES_COMUNS = [
    'Hemograma completo', 'Glicemia de jejum', 'Colesterol total e frações', 'Triglicerídeos', 'Creatinina',
    'TSH', 'Urina tipo 1', 'Eletrocardiograma', 'Raio-X de tórax', 'Ultrassonografia abdominal',
];

// Paleta do site (resources/css/painel-base.css)
const COR = {
    profundo: '#246DBF',
    vivo: '#5FACF4',
    suave: '#AEDAF4',
    tinta: 'rgba(14, 14, 14, 0.45)',
    tintaClara: 'rgba(14, 14, 14, 0.12)',
};

/** Dados carregados da API e usados pelas telas e janelas */
const estado = {
    api: null,
    medico: null,
    consultas: [],
    exames: [],
    falhou: false, // a lista de consultas não pôde ser carregada
    proxima: null, // próxima consulta por acontecer
};

document.addEventListener('DOMContentLoaded', async () => {
    // Proteção de rota via front-end: exige login de médico
    estado.api = iniciarPainel('medico');
    if (!estado.api) return;

    ligarLogout(estado.api, 'medico');
    ativarMenuPorSecao();
    ligarAcoes();

    const [rPerfil] = await Promise.all([
        estado.api.get('/perfil').then((r) => r, () => null),
        carregarDados(),
    ]);

    if (rPerfil) {
        estado.medico = rPerfil.data.usuario;
        preencherPerfil(estado.medico);
        renderResumo();
    }
});

/** Busca consultas e exames e redesenha tudo (usado ao abrir a página e depois de cada ação). */
async function carregarDados() {
    const [rConsultas, rExames] = await Promise.allSettled([
        estado.api.get('/consultas'),
        estado.api.get('/exames'),
    ]);

    estado.falhou = rConsultas.status !== 'fulfilled';
    estado.consultas = estado.falhou ? [] : rConsultas.value.data;
    estado.exames = rExames.status === 'fulfilled' ? rExames.value.data : [];

    renderMetricas();
    renderProximoPaciente();
    renderAgenda();
    renderPacientes();
    renderGraficos();
    renderResumo();
}

function ligarAcoes() {
    document.getElementById('btn-encaixe')?.addEventListener('click', () => abrirEncaixe());
    document.getElementById('btn-receita')?.addEventListener('click', () => abrirAtendimento({ foco: 'receita' }));
    document.getElementById('btn-exames')?.addEventListener('click', () => abrirAtendimento({ foco: 'exames' }));
    document.getElementById('btn-atestado')?.addEventListener('click', () => abrirDocumento());
    campo('proximo-botao')?.addEventListener('click', () => chamarPaciente());

    // Sempre que o médico alterar o calendário, mostra a agenda do dia escolhido
    entradaDataAgenda()?.addEventListener('change', () => renderAgenda());
    campo('filtro-pacientes')?.addEventListener('input', () => renderPacientes());
}

const entradaDataAgenda = () => document.querySelector('#agenda input[type="date"]');
const dataDaAgenda = () => entradaDataAgenda()?.value || hojeISO();

/** Consulta que o médico já pode atender: agendada, de hoje ou de dia anterior, sem atendimento registrado */
const aguardaAtendimento = (consulta) =>
    consulta.status === 'agendada' && !consulta.atendimento && consulta.data <= hojeISO();

/* ------------------------------------------------------------------ */
/* Perfil e saudação                                                   */
/* ------------------------------------------------------------------ */

/** CRM no banco: "RS-45678" -> "CRM/RS 45678" */
function formatarCrm(crm) {
    const [uf, numero] = (crm ?? '').split('-');
    return numero ? `CRM/${uf} ${numero}` : `CRM ${crm ?? ''}`.trim();
}

/** "Dra. Marina Ferraz" -> "Dra. Marina"; "Marina Ferraz" -> "Marina" */
function nomeCurto(nome) {
    const partes = nome.split(/\s+/);
    return /^(dr|dra)\.?$/i.test(partes[0]) ? partes.slice(0, 2).join(' ') : partes[0];
}

function preencherPerfil(medico) {
    definirTexto('nome', medico.nome);
    definirTexto('especialidade', `${medico.especialidade?.nome ?? 'Médico'} - ${formatarCrm(medico.crm)}`);

    // Foto da equipe (images/equipe-<nome>.jpg); sem foto, o avatar simplesmente não aparece
    const foto = campo('foto');
    if (foto) {
        foto.alt = `Foto ${medico.nome}`;
        foto.addEventListener('load', () => { foto.hidden = false; });
        foto.src = `${foto.dataset.base}/equipe-${slugDoNome(medico.nome)}.jpg`;
    }
}

function renderResumo() {
    const hoje = hojeISO();
    const hora = new Date().getHours();
    const cumprimento = hora < 12 ? 'Bom dia' : hora < 18 ? 'Boa tarde' : 'Boa noite';

    if (estado.medico) definirTexto('saudacao', `${cumprimento}, ${nomeCurto(estado.medico.nome)}! 👋`);

    let texto = `Hoje é ${diaDaSemana(hoje)}, ${dataLonga(hoje)}.`;
    if (!estado.falhou) {
        const pendentes = estado.consultas.filter((c) => c.data === hoje && c.status === 'agendada').length;
        if (pendentes === 0) texto += ' Você não tem consultas pendentes hoje.';
        else texto += ` Você tem ${pendentes} ${pendentes === 1 ? 'consulta pendente' : 'consultas pendentes'} hoje.`;
    }
    definirTexto('resumo-dia', texto);
}

/* ------------------------------------------------------------------ */
/* Cartões de métricas                                                 */
/* ------------------------------------------------------------------ */

function renderMetricas() {
    const hoje = hojeISO();
    const consultas = estado.consultas;

    const doDia = consultas.filter((c) => c.data === hoje && c.status !== 'cancelada');
    const concluidas = doDia.filter((c) => c.status === 'realizada').length;
    definirTexto('metrica-hoje', String(doDia.length));
    definirTexto('metrica-hoje-detalhe', `${concluidas} concluídas · ${doDia.length - concluidas} pendentes`);

    // Presença = consultas realizadas sobre (realizadas + canceladas)
    const realizadas = consultas.filter((c) => c.status === 'realizada').length;
    const canceladas = consultas.filter((c) => c.status === 'cancelada').length;
    const total = realizadas + canceladas;

    if (total === 0) {
        definirTexto('metrica-presenca', '—');
        definirTexto('metrica-presenca-detalhe', 'Ainda sem consultas realizadas ou canceladas');
    } else {
        definirTexto('metrica-presenca', `${Math.round((realizadas / total) * 100)}%`);
        definirTexto('metrica-presenca-detalhe', `${realizadas} realizadas · ${canceladas} canceladas`);
    }
}

/* ------------------------------------------------------------------ */
/* Próximo paciente (barra lateral)                                    */
/* ------------------------------------------------------------------ */

function emMinutos(hora, agora) {
    const minutos = (hhmm) => Number(hhmm.slice(0, 2)) * 60 + Number(hhmm.slice(3, 5));
    const diferenca = minutos(hora) - minutos(agora);

    if (diferenca <= 0) return 'agora';
    if (diferenca < 60) return `em ${diferenca} min`;
    return `em ${Math.floor(diferenca / 60)} h ${dois(diferenca % 60)} min`;
}

function renderProximoPaciente() {
    const hoje = hojeISO();
    const agora = horaAgora();
    const botao = campo('proximo-botao');

    // As consultas vêm em ordem cronológica: a primeira ainda por acontecer é a próxima
    const proxima = estado.consultas.find((c) => ehProxima(c, hoje, agora));
    estado.proxima = proxima ?? null;

    if (!proxima) {
        definirTexto('proximo-nome', 'Nenhum paciente agendado');
        definirTexto('proximo-horario', '');
        definirTexto('proximo-detalhe', '');
        if (botao) botao.hidden = true;
        return;
    }

    const paciente = proxima.paciente;
    definirTexto('proximo-nome', `${paciente.nome} (${idade(paciente.data_nascimento)} anos)`);

    definirTexto(
        'proximo-horario',
        proxima.data === hoje
            ? `Horário: ${horaCurta(proxima.hora)} (${emMinutos(proxima.hora, agora)})`
            : `Data: ${dataBR(proxima.data)} às ${horaCurta(proxima.hora)}`
    );
    definirTexto('proximo-detalhe', proxima.sala ? `Sala: ${proxima.sala.identificacao}` : '');

    // Só dá para chamar quem tem consulta hoje
    if (botao) botao.hidden = proxima.data !== hoje;
}

function chamarPaciente() {
    const consulta = estado.proxima;
    if (!consulta || consulta.data !== hojeISO()) return;

    const modal = criarModal({ titulo: 'Chamar paciente' });

    modal.corpo.append(el('div', { class: 'chamada', role: 'status' },
        el('div', { class: 'avatar', 'aria-hidden': 'true', texto: iniciais(consulta.paciente.nome) }),
        el('h3', { texto: consulta.paciente.nome }),
        el('p', { texto: `Consulta das ${horaCurta(consulta.hora)}${consulta.sala ? ` · ${consulta.sala.identificacao}` : ''}` }),
        el('p', { texto: 'Chame o paciente na recepção e inicie o atendimento quando ele chegar à sala.' })
    ));

    const fechar = el('button', { type: 'button', class: 'btn btn-outline', texto: 'Fechar' });
    const iniciar = el('button', { type: 'button', class: 'btn btn-primary', texto: 'Iniciar atendimento' });
    fechar.addEventListener('click', () => modal.fechar());
    iniciar.addEventListener('click', () => {
        modal.fechar();
        abrirAtendimento({ consulta });
    });
    modal.rodape.append(fechar, iniciar);
    iniciar.focus();
}

/* ------------------------------------------------------------------ */
/* Agenda do dia                                                       */
/* ------------------------------------------------------------------ */

function linhaAgenda(consulta) {
    const hoje = hojeISO();
    const acoes = el('div', { class: 'acoes-linha' });

    if (aguardaAtendimento(consulta)) {
        const atender = el('button', {
            type: 'button',
            class: 'btn btn-primary btn-sm',
            texto: consulta.data === hoje ? 'Atender Agora' : 'Registrar Atendimento',
        });
        atender.addEventListener('click', () => abrirAtendimento({ consulta }));
        acoes.append(atender);
    }

    if (consulta.status !== 'cancelada' && consulta.paciente) {
        const prontuario = el('button', { type: 'button', class: 'btn btn-outline btn-sm', texto: 'Ver Prontuário' });
        prontuario.addEventListener('click', () => abrirProntuario(consulta.paciente.id));
        acoes.append(prontuario);
    }

    return el('tr', {},
        el('td', { class: 'hora', texto: horaCurta(consulta.hora) }),
        el('td', { texto: consulta.paciente?.nome ?? '—' }),
        el('td', { texto: consulta.observacoes || '—' }),
        el('td', {}, el('span', { class: STATUS_TAG[consulta.status] ?? 'tag', texto: STATUS[consulta.status] ?? consulta.status })),
        el('td', {}, acoes.childElementCount ? acoes : '—')
    );
}

function renderAgenda() {
    const corpo = campo('agenda');
    const dataISO = dataDaAgenda();
    corpo.replaceChildren();

    definirTexto('titulo-agenda', dataISO === hojeISO() ? 'Agenda de Hoje' : `Agenda de ${dataBR(dataISO)}`);

    const linhaVazia = (texto) => el('tr', {}, el('td', { colspan: '5', class: 'vazio', texto }));

    if (estado.falhou) {
        corpo.append(linhaVazia('Não foi possível carregar a agenda. Tente novamente em instantes.'));
        return;
    }

    const doDia = estado.consultas.filter((c) => c.data === dataISO);

    if (doDia.length === 0) {
        corpo.append(linhaVazia('Nenhuma consulta para esta data.'));
        return;
    }

    doDia.forEach((consulta) => corpo.append(linhaAgenda(consulta)));
}

/* ------------------------------------------------------------------ */
/* Meus pacientes                                                      */
/* ------------------------------------------------------------------ */

/** Pacientes com ao menos uma consulta (não cancelada) na agenda do médico, em ordem alfabética */
function pacientesDoMedico() {
    const hoje = hojeISO();
    const agora = horaAgora();
    const mapa = new Map();

    // As consultas vêm em ordem cronológica
    for (const consulta of estado.consultas) {
        if (!consulta.paciente || consulta.status === 'cancelada') continue;

        const item = mapa.get(consulta.paciente.id) ?? { paciente: consulta.paciente, total: 0, ultima: null, proxima: null };
        item.total++;
        if (consulta.status === 'realizada') item.ultima = consulta;
        if (!item.proxima && ehProxima(consulta, hoje, agora)) item.proxima = consulta;
        mapa.set(consulta.paciente.id, item);
    }

    return [...mapa.values()].sort((a, b) => a.paciente.nome.localeCompare(b.paciente.nome, 'pt-BR'));
}

const semAcento = (texto) => texto.normalize('NFD').replace(/[̀-ͯ]/g, '').toLowerCase();

function cartaoPaciente({ paciente, total, ultima, proxima }) {
    const prontuario = el('button', { type: 'button', class: 'btn btn-outline btn-sm', texto: 'Ver Prontuário' });
    prontuario.addEventListener('click', () => abrirProntuario(paciente.id));

    return el('article', { class: 'paciente-card' },
        el('div', { class: 'paciente-card-topo' },
            el('div', { class: 'avatar', 'aria-hidden': 'true', texto: iniciais(paciente.nome) }),
            el('div', {},
                el('h3', { class: 'item-titulo', texto: paciente.nome }),
                el('p', { texto: `${idade(paciente.data_nascimento)} anos · ${paciente.convenio?.nome ?? 'Particular'}` })
            )
        ),
        el('div', {},
            el('p', { texto: ultima ? `Última consulta: ${dataBR(ultima.data)}` : 'Ainda sem consulta realizada' }),
            proxima ? el('p', { texto: `Próxima: ${dataBR(proxima.data)} às ${horaCurta(proxima.hora)}` }) : null,
            el('p', { texto: `${total} ${total === 1 ? 'consulta' : 'consultas'} na sua agenda` })
        ),
        prontuario
    );
}

function renderPacientes() {
    const lista = campo('lista-pacientes');
    const filtro = campo('filtro-pacientes');
    lista.replaceChildren();

    if (estado.falhou) {
        lista.append(el('p', { class: 'vazio', texto: 'Não foi possível carregar seus pacientes. Tente novamente em instantes.' }));
        return;
    }

    const todos = pacientesDoMedico();

    // O filtro só aparece quando a lista já é grande o bastante para precisar dele
    if (filtro) {
        filtro.hidden = todos.length <= 6;
        if (filtro.hidden) filtro.value = '';
    }

    if (todos.length === 0) {
        lista.append(el('p', { class: 'vazio', texto: 'Você ainda não tem pacientes. Eles aparecem aqui assim que marcarem consulta com você.' }));
        return;
    }

    const termo = semAcento((filtro?.value ?? '').trim());
    const visiveis = termo ? todos.filter((item) => semAcento(item.paciente.nome).includes(termo)) : todos;

    if (visiveis.length === 0) {
        lista.append(el('p', { class: 'vazio', texto: 'Nenhum paciente encontrado com esse nome.' }));
        return;
    }

    lista.append(...visiveis.map(cartaoPaciente));
}

/* ------------------------------------------------------------------ */
/* Prontuário (somente leitura)                                        */
/* ------------------------------------------------------------------ */

function blocoHistorico(rotulo, conteudo) {
    return el('div', { class: 'historico-bloco' }, el('span', { class: 'rotulo', texto: rotulo }), conteudo);
}

function itemHistorico(consulta) {
    const item = el('article', { class: 'historico-item' },
        el('div', { class: 'historico-topo' },
            el('strong', { texto: `${dataBR(consulta.data)} às ${horaCurta(consulta.hora)}` }),
            el('span', { class: STATUS_TAG[consulta.status] ?? 'tag', texto: STATUS[consulta.status] ?? consulta.status })
        )
    );

    if (consulta.observacoes) item.append(blocoHistorico('Motivo da consulta', el('p', { texto: consulta.observacoes })));

    const atendimento = consulta.atendimento;
    if (!atendimento) {
        if (consulta.status === 'agendada') item.append(el('p', { class: 'texto-fraco', texto: 'Atendimento ainda não registrado.' }));
        return item;
    }

    item.append(blocoHistorico('Diagnóstico', el('p', { texto: atendimento.diagnostico })));
    if (atendimento.receita) item.append(blocoHistorico('Receita', el('p', { texto: atendimento.receita })));
    if (atendimento.observacoes) item.append(blocoHistorico('Observações', el('p', { texto: atendimento.observacoes })));

    const exames = estado.exames.filter((exame) => exame.atendimento_id === atendimento.id);
    if (exames.length) {
        item.append(blocoHistorico('Exames solicitados', el('div', { class: 'chips' },
            ...exames.map((exame) => el('span', { class: 'tag', texto: `${exame.nome} · ${STATUS_EXAME[exame.status] ?? exame.status}` }))
        )));
    }

    return item;
}

function abrirProntuario(pacienteId) {
    const todas = estado.consultas.filter((c) => c.paciente?.id === pacienteId);
    const paciente = todas[0]?.paciente;
    if (!paciente) return;

    const historico = todas
        .filter((c) => c.status !== 'cancelada')
        .sort((a, b) => `${b.data} ${b.hora}`.localeCompare(`${a.data} ${a.hora}`));

    const modal = criarModal({
        titulo: 'Prontuário',
        subtitulo: `${paciente.nome} · ${idade(paciente.data_nascimento)} anos`,
        largo: true,
    });

    const dado = (rotulo, valor) => el('div', {}, el('dt', { class: 'rotulo', texto: rotulo }), el('dd', { texto: valor || '—' }));

    modal.corpo.append(
        el('dl', { class: 'dados-paciente' },
            dado('CPF', paciente.cpf),
            dado('Nascimento', dataBR(paciente.data_nascimento)),
            dado('Sexo', SEXO[paciente.sexo] ?? paciente.sexo),
            dado('Telefone', paciente.telefone),
            dado('E-mail', paciente.email),
            dado('Convênio', paciente.convenio?.nome ?? 'Particular')
        ),
        el('h3', { texto: 'Histórico de consultas' }),
        historico.length
            ? el('div', {}, ...historico.map(itemHistorico))
            : el('p', { class: 'vazio', texto: 'Nenhuma consulta registrada.' })
    );

    const fechar = el('button', { type: 'button', class: 'btn btn-outline', texto: 'Fechar' });
    fechar.addEventListener('click', () => modal.fechar());
    modal.rodape.append(fechar);

    const pendente = historico.slice().reverse().find(aguardaAtendimento);
    if (pendente) {
        const atender = el('button', { type: 'button', class: 'btn btn-primary', texto: 'Atender Agora' });
        atender.addEventListener('click', () => {
            modal.fechar();
            abrirAtendimento({ consulta: pendente });
        });
        modal.rodape.append(atender);
    }
}

/* ------------------------------------------------------------------ */
/* Atendimento: diagnóstico, receita e exames                          */
/* ------------------------------------------------------------------ */

/** Janela de aviso simples, com um botão opcional de ação */
function avisoEmJanela({ titulo, texto, acao = null }) {
    const modal = criarModal({ titulo });
    modal.corpo.append(el('p', { texto }));

    const fechar = el('button', { type: 'button', class: 'btn btn-outline', texto: 'Fechar' });
    fechar.addEventListener('click', () => modal.fechar());
    modal.rodape.append(fechar);

    if (acao) {
        const botao = el('button', { type: 'button', class: 'btn btn-primary', texto: acao.texto });
        botao.addEventListener('click', () => {
            modal.fechar();
            acao.aoClicar();
        });
        modal.rodape.append(botao);
    }
}

/**
 * Registra o atendimento de uma consulta (POST /api/atendimentos): diagnóstico, receita,
 * exames solicitados e observações. Sem "consulta", o médico escolhe entre as pendentes.
 * "foco" diz qual campo recebe o cursor ao abrir: diagnostico | receita | exames.
 */
function abrirAtendimento({ consulta = null, foco = 'diagnostico' } = {}) {
    const pendentes = estado.consultas.filter(aguardaAtendimento);

    if (!consulta && pendentes.length === 0) {
        avisoEmJanela({
            titulo: 'Nenhuma consulta para atender',
            texto: 'Receitas e exames são registrados durante o atendimento de uma consulta, e você não tem consultas pendentes de hoje. '
                + 'Marque um encaixe para atender um paciente agora ou espere o dia da consulta agendada.',
            acao: { texto: 'Nova consulta encaixe', aoClicar: () => abrirEncaixe() },
        });
        return;
    }

    const modal = criarModal({
        titulo: 'Registrar atendimento',
        subtitulo: 'Diagnóstico, receita e exames solicitados. Ao salvar, a consulta fica como concluída.',
        largo: true,
    });

    /* --- consulta atendida --- */
    let seletorConsulta = null;
    let cabecalhoConsulta;

    if (consulta) {
        cabecalhoConsulta = el('div', { class: 'resumo-consulta' },
            el('div', { class: 'avatar', 'aria-hidden': 'true', texto: iniciais(consulta.paciente.nome) }),
            el('div', {},
                el('strong', { class: 'item-titulo', texto: consulta.paciente.nome }),
                el('small', { texto: `${dataBR(consulta.data)} às ${horaCurta(consulta.hora)}${consulta.sala ? ` · ${consulta.sala.identificacao}` : ''}` })
            )
        );
    } else {
        const padrao = pendentes.find((c) => c.id === estado.proxima?.id) ?? pendentes[0];
        seletorConsulta = el('select', { id: 'at-consulta' },
            ...pendentes.map((c) => el('option', {
                value: String(c.id),
                texto: `${dataBR(c.data)} ${horaCurta(c.hora)} — ${c.paciente.nome}`,
            }))
        );
        seletorConsulta.value = String(padrao.id);
        cabecalhoConsulta = el('div', { class: 'campo' }, el('label', { for: 'at-consulta', texto: 'Consulta' }), seletorConsulta);
    }

    /* --- campos --- */
    const diagnostico = el('textarea', { id: 'at-diagnostico', maxlength: '5000', required: '' });
    const receita = el('textarea', {
        id: 'at-receita',
        maxlength: '5000',
        placeholder: 'Omeprazol 20mg — 1 comprimido às 08:00 (jejum)',
    });
    const observacoes = el('textarea', { id: 'at-observacoes', maxlength: '5000' });

    const entradaExame = el('input', { id: 'at-exame', type: 'text', maxlength: '150', list: 'at-sugestoes', autocomplete: 'off', placeholder: 'Ex.: Hemograma completo' });
    const sugestoes = el('datalist', { id: 'at-sugestoes' }, ...EXAMES_COMUNS.map((nome) => el('option', { value: nome })));
    const botaoAdicionar = el('button', { type: 'button', class: 'btn btn-outline', texto: 'Adicionar' });
    const chips = el('div', { class: 'chips', 'aria-live': 'polite' });
    const exames = [];

    const erro = el('p', { class: 'erro-form', role: 'alert' });
    erro.hidden = true;

    function desenharExames() {
        chips.replaceChildren(...exames.map((nome, indice) => {
            const remover = el('button', { type: 'button', 'aria-label': `Remover ${nome}`, texto: '×' });
            remover.addEventListener('click', () => {
                exames.splice(indice, 1);
                desenharExames();
            });
            return el('span', { class: 'chip' }, nome, remover);
        }));
    }

    function adicionarExame() {
        const nome = entradaExame.value.trim();
        if (!nome) return;

        if (exames.length >= 20) {
            erro.textContent = 'No máximo 20 exames por atendimento.';
            erro.hidden = false;
        } else if (!exames.some((existente) => existente.toLowerCase() === nome.toLowerCase())) {
            exames.push(nome);
            erro.hidden = true;
            desenharExames();
        }

        entradaExame.value = '';
        entradaExame.focus();
    }

    botaoAdicionar.addEventListener('click', adicionarExame);
    entradaExame.addEventListener('keydown', (evento) => {
        if (evento.key === 'Enter') {
            evento.preventDefault(); // Enter adiciona o exame, não envia o formulário
            adicionarExame();
        }
    });

    const salvar = el('button', { type: 'submit', form: 'form-atendimento', class: 'btn btn-primary', texto: 'Registrar atendimento' });

    const formulario = el('form', { id: 'form-atendimento', class: 'form-grid', novalidate: '' },
        cabecalhoConsulta,
        el('div', { class: 'campo' }, el('label', { for: 'at-diagnostico', texto: 'Diagnóstico *' }), diagnostico),
        el('div', { class: 'campo' },
            el('label', { for: 'at-receita', texto: 'Receita (opcional)' }),
            receita,
            el('p', { class: 'campo-ajuda', texto: 'Um medicamento por linha, no formato "Medicamento — posologia". O paciente vê a receita no painel dele.' })
        ),
        el('div', { class: 'campo' },
            el('label', { for: 'at-exame', texto: 'Exames solicitados (opcional)' }),
            el('div', { class: 'linha-adicionar' }, entradaExame, botaoAdicionar),
            sugestoes,
            chips
        ),
        el('div', { class: 'campo' }, el('label', { for: 'at-observacoes', texto: 'Observações (opcional)' }), observacoes),
        erro
    );

    formulario.addEventListener('submit', async (evento) => {
        evento.preventDefault();

        // Exame digitado e ainda não adicionado também vai junto
        if (entradaExame.value.trim()) adicionarExame();

        if (!diagnostico.value.trim()) {
            erro.textContent = 'Informe o diagnóstico para registrar o atendimento.';
            erro.hidden = false;
            diagnostico.focus();
            return;
        }

        const consultaId = consulta ? consulta.id : Number(seletorConsulta.value);

        erro.hidden = true;
        salvar.disabled = true;
        salvar.textContent = 'Salvando…';

        try {
            await estado.api.post('/atendimentos', {
                consulta_id: consultaId,
                diagnostico: diagnostico.value.trim(),
                receita: receita.value.trim() || null,
                observacoes: observacoes.value.trim() || null,
                exames: exames.map((nome) => ({ nome })),
            });

            modal.fechar();
            toast(exames.length
                ? `Atendimento registrado e ${exames.length} ${exames.length === 1 ? 'exame solicitado' : 'exames solicitados'}.`
                : 'Atendimento registrado.');
            await carregarDados();
        } catch (e) {
            erro.textContent = mensagemDeErro(e, 'Não foi possível registrar o atendimento. Tente novamente.');
            erro.hidden = false;
            salvar.disabled = false;
            salvar.textContent = 'Registrar atendimento';
        }
    });

    modal.corpo.append(formulario);

    const cancelar = el('button', { type: 'button', class: 'btn btn-outline', texto: 'Cancelar' });
    cancelar.addEventListener('click', () => modal.fechar());
    modal.rodape.append(cancelar, salvar);

    ({ diagnostico, receita, exames: entradaExame })[foco]?.focus();
}

/* ------------------------------------------------------------------ */
/* Nova consulta encaixe                                               */
/* ------------------------------------------------------------------ */

function abrirEncaixe() {
    if (!estado.medico) {
        toast('Não foi possível identificar o seu cadastro. Recarregue a página e tente de novo.', 'erro');
        return;
    }

    const modal = criarModal({
        titulo: 'Nova consulta encaixe',
        subtitulo: 'Busque o paciente e escolha o dia e o horário. Como médico, você pode marcar fora da grade da clínica.',
    });

    let pacienteId = null;
    let pacienteNome = '';
    let timer = null;
    let buscaAtual = 0; // descarta respostas atrasadas da busca

    const busca = el('input', { id: 'en-busca', type: 'search', autocomplete: 'off', placeholder: 'Nome ou CPF do paciente' });
    const ajudaBusca = el('p', { class: 'campo-ajuda', texto: 'Digite ao menos 3 caracteres para buscar.' });
    const resultados = el('div', { class: 'busca-resultados', role: 'radiogroup', 'aria-label': 'Pacientes encontrados' });
    const escolhido = el('p', { class: 'campo-ajuda' });
    escolhido.hidden = true;

    const motivo = el('textarea', { id: 'en-motivo', maxlength: '500', placeholder: 'Ex.: retorno, dor persistente…' });
    const erro = el('p', { class: 'erro-form', role: 'alert' });
    erro.hidden = true;

    const marcar = el('button', { type: 'submit', form: 'form-encaixe', class: 'btn btn-primary', texto: 'Marcar encaixe' });
    marcar.disabled = true;

    const seletor = criarSeletorHorarios({ api: estado.api, medicoId: estado.medico.id, horaLivre: true, aoMudar: validar });

    function validar() {
        const { data, hora } = seletor.valor();
        marcar.disabled = !(pacienteId && data && hora);
    }

    function opcaoPaciente(paciente) {
        const radio = el('input', { type: 'radio', name: 'en-paciente', value: String(paciente.id) });
        radio.addEventListener('change', () => {
            pacienteId = paciente.id;
            pacienteNome = paciente.nome;
            escolhido.textContent = `Paciente escolhido: ${paciente.nome}`;
            escolhido.hidden = false;
            validar();
        });

        return el('label', { class: 'paciente-opcao' },
            radio,
            el('div', { class: 'avatar', 'aria-hidden': 'true', texto: iniciais(paciente.nome) }),
            el('div', {},
                el('strong', { class: 'item-titulo', texto: paciente.nome }),
                el('small', { texto: `CPF ${paciente.cpf} · ${idade(paciente.data_nascimento)} anos` })
            )
        );
    }

    async function buscar(termo) {
        const minha = ++buscaAtual;
        ajudaBusca.textContent = 'Buscando…';

        try {
            const { data } = await estado.api.get('/pacientes', { params: { busca: termo } });
            if (minha !== buscaAtual) return;

            resultados.replaceChildren(...data.map(opcaoPaciente));
            ajudaBusca.textContent = data.length
                ? `${data.length} ${data.length === 1 ? 'paciente encontrado' : 'pacientes encontrados'}. Selecione um.`
                : 'Nenhum paciente encontrado. O paciente precisa ter cadastro no site.';
        } catch (e) {
            if (minha !== buscaAtual) return;
            resultados.replaceChildren();
            ajudaBusca.textContent = mensagemDeErro(e, 'Não foi possível buscar agora. Tente novamente.');
        }
    }

    busca.addEventListener('input', () => {
        clearTimeout(timer);
        const termo = busca.value.trim();

        if (termo.length < 3) {
            buscaAtual++; // invalida buscas em andamento
            resultados.replaceChildren();
            ajudaBusca.textContent = 'Digite ao menos 3 caracteres para buscar.';
            return;
        }

        timer = setTimeout(() => buscar(termo), 300);
    });

    const formulario = el('form', { id: 'form-encaixe', class: 'form-grid', novalidate: '' },
        el('div', { class: 'campo' },
            el('label', { for: 'en-busca', texto: 'Paciente' }),
            busca,
            ajudaBusca,
            resultados,
            escolhido
        ),
        seletor.elemento,
        el('div', { class: 'campo' }, el('label', { for: 'en-motivo', texto: 'Motivo (opcional)' }), motivo),
        erro
    );

    formulario.addEventListener('submit', async (evento) => {
        evento.preventDefault();

        const { data, hora } = seletor.valor();
        if (!pacienteId || !data || !hora) return;

        erro.hidden = true;
        marcar.disabled = true;
        marcar.textContent = 'Marcando…';

        try {
            await estado.api.post('/consultas', {
                paciente_id: pacienteId,
                data,
                hora,
                observacoes: motivo.value.trim() || null,
            });

            modal.fechar();
            toast(`Encaixe marcado: ${pacienteNome} em ${dataBR(data)} às ${hora}.`);

            // Mostra na agenda o dia do encaixe
            const entrada = entradaDataAgenda();
            if (entrada) entrada.value = data;
            await carregarDados();
        } catch (e) {
            erro.textContent = mensagemDeErro(e, 'Não foi possível marcar o encaixe. Tente novamente.');
            erro.hidden = false;
            marcar.textContent = 'Marcar encaixe';
            seletor.definirData(data); // o horário pode ter sido ocupado: recarrega os livres
        }
    });

    modal.corpo.append(formulario);

    const cancelar = el('button', { type: 'button', class: 'btn btn-outline', texto: 'Cancelar' });
    cancelar.addEventListener('click', () => modal.fechar());
    modal.rodape.append(cancelar, marcar);

    seletor.definirData(hojeISO());
    busca.focus();
}

/* ------------------------------------------------------------------ */
/* Atestados e laudos (documento para imprimir)                        */
/* ------------------------------------------------------------------ */

function textoModelo(tipo, paciente, dias) {
    const cpf = paciente.cpf ? `, portador(a) do CPF ${paciente.cpf}` : '';

    if (tipo === 'atestado') {
        return `Atesto, para os devidos fins, que ${paciente.nome}${cpf}, foi atendido(a) nesta data e necessita de ${dias} ${dias === 1 ? 'dia' : 'dias'} de afastamento de suas atividades, a contar de hoje.`;
    }

    return `Laudo médico referente a ${paciente.nome}${cpf}.\n\nDescreva aqui os achados, a conclusão e as recomendações.`;
}

/** Monta o documento em #area-impressao e abre a impressão (que também permite salvar em PDF). */
function imprimirDocumento({ titulo, texto }) {
    const area = document.getElementById('area-impressao');
    const medico = estado.medico;
    const hoje = hojeISO();

    area.replaceChildren(
        el('div', { class: 'doc-clinica' },
            el('strong', { texto: 'ProSaúde' }),
            el('span', { texto: ENDERECO_CLINICA })
        ),
        el('h1', { texto: titulo }),
        ...texto.split(/\n{2,}/).filter((p) => p.trim()).map((paragrafo) => el('p', { texto: paragrafo.trim() })),
        el('p', { class: 'doc-data', texto: `Erechim, ${dataLonga(hoje)}` }),
        el('p', { class: 'doc-assinatura' },
            el('strong', { texto: medico.nome }),
            el('br'),
            `${medico.especialidade?.nome ?? 'Médico'} · ${formatarCrm(medico.crm)}`
        )
    );

    // Não deixa dados de paciente no HTML depois de imprimir
    window.addEventListener('afterprint', () => area.replaceChildren(), { once: true });
    setTimeout(() => window.print(), 50);
}

function abrirDocumento() {
    if (!estado.medico) {
        toast('Não foi possível identificar o seu cadastro. Recarregue a página e tente de novo.', 'erro');
        return;
    }

    const pacientes = pacientesDoMedico().map((item) => item.paciente);

    if (pacientes.length === 0) {
        avisoEmJanela({
            titulo: 'Nenhum paciente ainda',
            texto: 'Atestados e laudos são emitidos para pacientes da sua agenda. Assim que alguém marcar consulta com você, ele aparece aqui.',
        });
        return;
    }

    const modal = criarModal({
        titulo: 'Atestados / Laudos',
        subtitulo: 'Gera o documento para imprimir ou salvar em PDF. Revise o texto antes e assine o papel depois de impresso.',
    });

    const tipo = el('select', { id: 'doc-tipo' },
        el('option', { value: 'atestado', texto: 'Atestado médico' }),
        el('option', { value: 'laudo', texto: 'Laudo médico' })
    );
    const seletorPaciente = el('select', { id: 'doc-paciente' },
        ...pacientes.map((p, indice) => el('option', { value: String(indice), texto: p.nome }))
    );
    const dias = el('input', { id: 'doc-dias', type: 'number', min: '1', max: '60', value: '1' });
    const campoDias = el('div', { class: 'campo' }, el('label', { for: 'doc-dias', texto: 'Dias de afastamento' }), dias);
    const texto = el('textarea', { id: 'doc-texto', rows: '8', maxlength: '3000' });

    let editado = false;

    const numeroDeDias = () => Math.min(60, Math.max(1, Math.floor(Number(dias.value)) || 1));
    const paciente = () => pacientes[Number(seletorPaciente.value)];

    function regerar() {
        texto.value = textoModelo(tipo.value, paciente(), numeroDeDias());
        editado = false;
    }

    tipo.addEventListener('change', () => {
        campoDias.hidden = tipo.value !== 'atestado';
        regerar();
    });
    seletorPaciente.addEventListener('change', () => { if (!editado) regerar(); });
    dias.addEventListener('input', () => { if (!editado) regerar(); });
    texto.addEventListener('input', () => { editado = true; });

    const erro = el('p', { class: 'erro-form', role: 'alert' });
    erro.hidden = true;

    const emitir = el('button', { type: 'submit', form: 'form-documento', class: 'btn btn-primary', texto: 'Imprimir / Salvar PDF' });

    const formulario = el('form', { id: 'form-documento', class: 'form-grid', novalidate: '' },
        el('div', { class: 'form-linha' },
            el('div', { class: 'campo' }, el('label', { for: 'doc-tipo', texto: 'Documento' }), tipo),
            el('div', { class: 'campo' }, el('label', { for: 'doc-paciente', texto: 'Paciente' }), seletorPaciente)
        ),
        campoDias,
        el('div', { class: 'campo' }, el('label', { for: 'doc-texto', texto: 'Texto do documento' }), texto),
        erro
    );

    formulario.addEventListener('submit', (evento) => {
        evento.preventDefault();

        if (!texto.value.trim()) {
            erro.textContent = 'O documento está vazio. Escreva o texto antes de imprimir.';
            erro.hidden = false;
            texto.focus();
            return;
        }

        const titulo = tipo.value === 'atestado' ? 'Atestado Médico' : 'Laudo Médico';
        modal.fechar();
        imprimirDocumento({ titulo, texto: texto.value });
    });

    modal.corpo.append(formulario);

    const cancelar = el('button', { type: 'button', class: 'btn btn-outline', texto: 'Cancelar' });
    cancelar.addEventListener('click', () => modal.fechar());
    modal.rodape.append(cancelar, emitir);

    regerar();
    tipo.focus();
}

/* ------------------------------------------------------------------ */
/* Gráficos (Chart.js é carregado por <script> na própria página)       */
/* ------------------------------------------------------------------ */

function renderGraficos() {
    if (!window.Chart) return; // sem Chart.js (offline/bloqueado) a seção fica só com os títulos

    window.Chart.defaults.font.family = "'IBM Plex Sans', -apple-system, sans-serif";
    window.Chart.defaults.color = 'rgba(14, 14, 14, 0.7)';

    const ativas = estado.consultas.filter((c) => c.status !== 'cancelada');
    graficoVolume(ativas);
    graficoFaixaEtaria(ativas.filter((c) => c.paciente));
}

/** Cria um gráfico no canvas, descartando o anterior (os dados são redesenhados depois de cada ação) */
function novoGrafico(canvas, configuracao) {
    window.Chart.getChart(canvas)?.destroy();
    return new window.Chart(canvas, configuracao);
}

function graficoVolume(consultas) {
    const canvas = document.getElementById('chartConsultas');
    if (!canvas) return;

    // Últimos 6 meses, terminando no mês atual
    const hoje = new Date();
    const meses = [];
    for (let i = 5; i >= 0; i--) {
        const d = new Date(hoje.getFullYear(), hoje.getMonth() - i, 1);
        meses.push({
            chave: `${d.getFullYear()}-${dois(d.getMonth() + 1)}`,
            rotulo: `${MESES_CURTOS[d.getMonth()]}/${String(d.getFullYear()).slice(2)}`,
            total: 0,
        });
    }

    for (const consulta of consultas) {
        const mes = meses.find((m) => m.chave === consulta.data.slice(0, 7));
        if (mes) mes.total++;
    }

    novoGrafico(canvas, {
        type: 'bar',
        data: {
            labels: meses.map((m) => m.rotulo),
            datasets: [{
                label: 'Consultas',
                data: meses.map((m) => m.total),
                backgroundColor: COR.vivo,
                hoverBackgroundColor: COR.profundo,
                borderRadius: 6,
            }],
        },
        options: {
            aspectRatio: 2,
            plugins: { legend: { display: false } },
            scales: { y: { beginAtZero: true, ticks: { precision: 0 } } },
        },
    });
}

function graficoFaixaEtaria(consultas) {
    const canvas = document.getElementById('chartPacientes');
    if (!canvas) return;

    // Cada paciente conta uma vez, mesmo com várias consultas
    const pacientes = new Map();
    consultas.forEach((c) => pacientes.set(c.paciente.id, c.paciente));

    if (pacientes.size === 0) {
        window.Chart.getChart(canvas)?.destroy();
        return;
    }

    const faixas = [
        { rotulo: '0–12 anos', min: 0, max: 12 },
        { rotulo: '13–17 anos', min: 13, max: 17 },
        { rotulo: '18–39 anos', min: 18, max: 39 },
        { rotulo: '40–59 anos', min: 40, max: 59 },
        { rotulo: '60+ anos', min: 60, max: 200 },
    ];

    const idades = [...pacientes.values()].map((p) => idade(p.data_nascimento));
    const contagem = faixas.map((f) => idades.filter((i) => i >= f.min && i <= f.max).length);

    novoGrafico(canvas, {
        type: 'doughnut',
        data: {
            labels: faixas.map((f) => f.rotulo),
            datasets: [{
                data: contagem,
                backgroundColor: [COR.profundo, COR.vivo, COR.suave, COR.tinta, COR.tintaClara],
                borderWidth: 0,
            }],
        },
        options: {
            aspectRatio: 2,
            plugins: { legend: { position: 'right' } },
        },
    });
}
