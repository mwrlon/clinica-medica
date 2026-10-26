import axios from 'axios';
import { limparErros, mostrarErro, salvarSessao, PAINEL } from '../lib/sessao.js';

document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('form-cadastro');
    if (!form) {
        console.error('Formulário #form-cadastro não encontrado no HTML.');
        return;
    }

    const btnSubmit = form.querySelector('button[type="submit"]');
    const mensagens = document.getElementById('mensagens');

    form.addEventListener('submit', async (e) => {
        e.preventDefault();

        limparErros(form);
        if (mensagens) mensagens.textContent = '';

        const txtOriginal = btnSubmit?.textContent;
        if (btnSubmit) {
            btnSubmit.textContent = 'Criando conta...';
            btnSubmit.disabled = true;
        }

        const payload = {
            nome: form.nome.value,
            cpf: form.cpf.value,
            data_nascimento: form.data_nascimento.value,
            sexo: form.sexo ? form.sexo.value : '',
            email: form.email.value,
            telefone: form.telefone.value,
            senha: form.senha.value,
            confirmar_senha: form.confirmar_senha.value,
            termos: form.termos.checked,
        };

        try {
            const response = await axios.post('/api/cadastrar', payload, {
                headers: { 'Accept': 'application/json' }
            });

            salvarSessao(response.data.access_token, 'paciente', true);
            window.location.href = PAINEL.paciente;
        } catch (error) {
            if (btnSubmit) {
                btnSubmit.textContent = txtOriginal;
                btnSubmit.disabled = false;
            }

            if (error.response?.status === 422 && error.response.data?.errors) {
                const errors = error.response.data.errors;
                for (const campo in errors) {
                    mostrarErro(form, campo, errors[campo][0]);
                }
            } else {
                mostrarErro(form, 'mensagem-geral', 'Ocorreu um erro inesperado ao tentar cadastrar.');
            }
        }
    });
});
