import { iniciarPainel, ligarLogout } from '../lib/sessao.js';
import { definirTexto } from '../lib/painel.js';
import { montarPerfil } from '../lib/perfil.js';
import { estado } from '../lib/admin/comum.js';
import { carregarResumo } from '../lib/admin/resumo.js';
import { iniciarMedicos, carregarMedicos } from '../lib/admin/medicos.js';
import { iniciarPacientes, carregarPacientes } from '../lib/admin/pacientes.js';
import { iniciarConsultas, carregarConsultas } from '../lib/admin/consultas.js';
import { iniciarCadastros, carregarCadastros } from '../lib/admin/cadastros.js';

/** Cada aba e a função que (re)carrega os dados dela sempre que é aberta. */
const ABAS = {
    resumo: carregarResumo,
    medicos: carregarMedicos,
    pacientes: carregarPacientes,
    consultas: carregarConsultas,
    cadastros: carregarCadastros,
    perfil: null,
};

document.addEventListener('DOMContentLoaded', async () => {
    // Proteção de rota via front-end: exige login de administrador
    estado.api = iniciarPainel('admin');
    if (!estado.api) return;

    ligarLogout(estado.api, 'admin');

    iniciarMedicos();
    iniciarPacientes();
    iniciarConsultas();
    iniciarCadastros();
    ligarAbas();

    // Médicos e especialidades alimentam formulários e filtros de outras abas
    if (abaAtual() !== 'medicos') carregarMedicos({ silencioso: true });
    if (abaAtual() !== 'cadastros') carregarCadastros({ silencioso: true });

    const resposta = await estado.api.get('/perfil').then((r) => r, () => null);
    if (resposta) {
        preencherPerfil(resposta.data.usuario);
        montarPerfil({ api: estado.api, perfil: 'admin', usuario: resposta.data.usuario, aoAtualizar: preencherPerfil });
    }
});

function preencherPerfil(admin) {
    definirTexto('nome', admin.nome);
    definirTexto('saudacao', `Olá, ${admin.nome.split(' ')[0]}! 👋`);
}

/* ------------------------------------------------------------------ */
/* Abas (uma seção por vez, escolhida pelo #endereço da página)         */
/* ------------------------------------------------------------------ */

function abaAtual() {
    const id = window.location.hash.slice(1);
    return id in ABAS ? id : 'resumo';
}

function mostrarAba(id) {
    document.querySelectorAll('main .aba').forEach((secao) => {
        secao.hidden = secao.id !== id;
    });

    document.querySelectorAll('[data-campo="abas"] a').forEach((link) => {
        const ativo = link.getAttribute('href') === `#${id}`;
        link.classList.toggle('ativo', ativo);
        if (ativo) link.setAttribute('aria-current', 'page');
        else link.removeAttribute('aria-current');
    });

    ABAS[id]?.();
}

function ligarAbas() {
    window.addEventListener('hashchange', () => {
        mostrarAba(abaAtual());
        window.scrollTo(0, 0);
    });

    // Link para a aba em que já está (ou "Ver todas" dentro de uma aba): recarrega os dados
    document.querySelectorAll('a[href^="#"]').forEach((link) => {
        link.addEventListener('click', () => {
            if (link.hash === window.location.hash) mostrarAba(abaAtual());
        });
    });

    mostrarAba(abaAtual());
}
