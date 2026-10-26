import { iniciarPainel, ligarLogout } from '../lib/sessao.js';
import {
    el, campo, definirTexto, dois, hojeISO, horaAgora, dataBR, horaCurta, idade,
    ehProxima, slugDoNome, MESES_CURTOS,
} from '../lib/painel.js';

const STATUS = { agendada: 'Agendado', realizada: 'Concluído', cancelada: 'Cancelado' };

// Paleta do site (resources/css/medico.css)
const COR = {
    profundo: '#246DBF',
    vivo: '#5FACF4',
    suave: '#AEDAF4',
    tinta: 'rgba(14, 14, 14, 0.45)',
    tintaClara: 'rgba(14, 14, 14, 0.12)',
};

document.addEventListener('DOMContentLoaded', async () => {
    // Proteção de rota via front-end: exige login de médico
    const api = iniciarPainel('medico');
    if (!api) return;

    ligarLogout(api, 'medico');

    const [rPerfil, rConsultas] = await Promise.allSettled([
        api.get('/perfil'),
        api.get('/consultas'),
    ]);

    if (rPerfil.status === 'fulfilled') preencherPerfil(rPerfil.value.usuario);

    const falhou = rConsultas.status !== 'fulfilled';
    const consultas = falhou ? [] : rConsultas.value;

    const entradaData = document.querySelector('#agenda input[type="date"]');
    const dataInicial = () => entradaData?.value || hojeISO();

    renderMetricas(consultas);
    renderProximoPaciente(consultas);
    renderGraficos(consultas);
    renderAgenda(consultas, dataInicial(), falhou);

    // Sempre que o médico alterar o calendário, mostra a agenda do dia escolhido
    entradaData?.addEventListener('change', () => renderAgenda(consultas, dataInicial(), falhou));
});

/* ------------------------------------------------------------------ */
/* Perfil                                                              */
/* ------------------------------------------------------------------ */

function preencherPerfil(medico) {
    definirTexto('nome', medico.nome);

    // CRM no banco: "RS-45678" -> "CRM/RS 45678"
    const [uf, numero] = (medico.crm ?? '').split('-');
    const crm = numero ? `CRM/${uf} ${numero}` : `CRM ${medico.crm ?? ''}`.trim();
    definirTexto('especialidade', `${medico.especialidade?.nome ?? 'Médico'} - ${crm}`);

    // Foto da equipe (images/equipe-<nome>.jpg); sem foto, o avatar simplesmente não aparece
    const foto = campo('foto');
    if (foto) {
        foto.alt = `Foto ${medico.nome}`;
        foto.addEventListener('load', () => { foto.hidden = false; });
        foto.src = `${foto.dataset.base}/equipe-${slugDoNome(medico.nome)}.jpg`;
    }
}

/* ------------------------------------------------------------------ */
/* Cartões de métricas                                                 */
/* ------------------------------------------------------------------ */

function renderMetricas(consultas) {
    const hoje = hojeISO();

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

function renderProximoPaciente(consultas) {
    const hoje = hojeISO();
    const agora = horaAgora();
    const botao = campo('proximo-botao');

    // As consultas vêm em ordem cronológica: a primeira ainda por acontecer é a próxima
    const proxima = consultas.find((c) => ehProxima(c, hoje, agora));

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

    if (botao) botao.hidden = false;
}

/* ------------------------------------------------------------------ */
/* Agenda do dia                                                       */
/* ------------------------------------------------------------------ */

function linhaAgenda(consulta) {
    const hoje = hojeISO();

    let acao = null;
    if (consulta.status !== 'cancelada') {
        const hojeEAgendada = consulta.status === 'agendada' && consulta.data === hoje;
        acao = el('button', { texto: hojeEAgendada ? 'Atender Agora' : 'Ver Prontuário' });
    }

    return el('tr', {},
        el('td', { texto: horaCurta(consulta.hora) }),
        el('td', { texto: consulta.paciente?.nome ?? '—' }),
        el('td', { texto: consulta.observacoes || '—' }),
        el('td', {}, el('span', { texto: STATUS[consulta.status] ?? consulta.status })),
        el('td', {}, acao)
    );
}

function renderAgenda(consultas, dataISO, falhou = false) {
    const corpo = campo('agenda');
    corpo.replaceChildren();

    definirTexto('titulo-agenda', dataISO === hojeISO() ? 'Agenda de Hoje' : `Agenda de ${dataBR(dataISO)}`);

    if (falhou) {
        corpo.append(el('tr', {}, el('td', { colspan: '5', texto: 'Não foi possível carregar a agenda. Tente novamente em instantes.' })));
        return;
    }

    const doDia = consultas.filter((c) => c.data === dataISO);

    if (doDia.length === 0) {
        corpo.append(el('tr', {}, el('td', { colspan: '5', texto: 'Nenhuma consulta para esta data.' })));
        return;
    }

    doDia.forEach((consulta) => corpo.append(linhaAgenda(consulta)));
}

/* ------------------------------------------------------------------ */
/* Gráficos (Chart.js é carregado por <script> na própria página)       */
/* ------------------------------------------------------------------ */

function renderGraficos(consultas) {
    if (!window.Chart) return; // sem Chart.js (offline/bloqueado) a seção fica só com os títulos

    window.Chart.defaults.font.family = "'IBM Plex Sans', -apple-system, sans-serif";
    window.Chart.defaults.color = 'rgba(14, 14, 14, 0.7)';

    graficoVolume(consultas.filter((c) => c.status !== 'cancelada'));
    graficoFaixaEtaria(consultas.filter((c) => c.status !== 'cancelada' && c.paciente));
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

    new window.Chart(canvas, {
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
    if (pacientes.size === 0) return;

    const faixas = [
        { rotulo: '0–12 anos', min: 0, max: 12 },
        { rotulo: '13–17 anos', min: 13, max: 17 },
        { rotulo: '18–39 anos', min: 18, max: 39 },
        { rotulo: '40–59 anos', min: 40, max: 59 },
        { rotulo: '60+ anos', min: 60, max: 200 },
    ];

    const idades = [...pacientes.values()].map((p) => idade(p.data_nascimento));
    const contagem = faixas.map((f) => idades.filter((i) => i >= f.min && i <= f.max).length);

    new window.Chart(canvas, {
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
