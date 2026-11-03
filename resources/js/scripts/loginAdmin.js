import { entrar } from '../lib/sessao.js';

document.addEventListener('DOMContentLoaded', () => {
    const form = document.querySelector('form');

    form.addEventListener('submit', (e) => {
        e.preventDefault();

        entrar({
            form,
            endpoint: '/api/login/admin',
            perfil: 'admin',
            textoPadrao: 'Entrar na administração',
            textoCarregando: 'Acessando...',
        });
    });
});
