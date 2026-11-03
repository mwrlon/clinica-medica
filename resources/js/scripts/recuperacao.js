import { avisar } from '../lib/ui.js';

/**
 * Telas "Esqueci minha senha" e "Redefinir senha": o Laravel responde com uma página nova,
 * então o resultado (sucesso ou link inválido) chega num <div id="aviso-servidor" data-*>.
 * Aqui ele vira um aviso em janela.
 */
document.addEventListener('DOMContentLoaded', async () => {
    const dados = document.getElementById('aviso-servidor')?.dataset;
    if (!dados) return;

    await avisar({
        tipo: dados.tipo || 'info',
        titulo: dados.titulo,
        mensagem: dados.mensagem,
        botao: dados.botao || 'Entendi',
    });

    if (dados.destino) window.location.href = dados.destino;
});
