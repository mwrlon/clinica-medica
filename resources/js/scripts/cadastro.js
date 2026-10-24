import axios from 'axios';

document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('form-cadastro');
    if (!form) {
        console.error('Formulário #form-cadastro não encontrado no HTML.');
        return;
    }

    const btnSubmit = form.querySelector('button[type="submit"]');
    const mensagens = document.getElementById('mensagens');

    function mostrarMensagem(texto) {
        if (mensagens) mensagens.textContent = texto;
        else alert(texto);
    }

    function mostrarErro(campo, texto) {
        const input = form.querySelector(`[name="${campo}"]`);
        const container = input?.closest('.field')?.querySelector('.field-error');
        if (container) container.textContent = texto;
        else mostrarMensagem(texto);
    }

    form.addEventListener('submit', async (e) => {
        e.preventDefault();

        form.querySelectorAll('.field-error').forEach(el => el.textContent = '');
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
            localStorage.setItem('token', response.data.access_token);
            window.location.href = '/paciente';
        } catch (error) {
            if (btnSubmit) {
                btnSubmit.textContent = txtOriginal;
                btnSubmit.disabled = false;
            }

            if (error.response?.status === 422) {
                const errors = error.response.data.errors;
                for (const campo in errors) {
                    mostrarErro(campo, errors[campo][0]);
                }
            } else {
                mostrarMensagem('Ocorreu um erro inesperado ao tentar cadastrar.');
            }
        }
    });
});