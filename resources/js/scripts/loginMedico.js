import axios from 'axios';

document.addEventListener('DOMContentLoaded', () => {
    const form = document.querySelector('form');

    form.addEventListener('submit', async (e) => {
        e.preventDefault();

        document.querySelectorAll('.field-error').forEach(el => el.textContent = '');
        
        const btnSubmit = form.querySelector('button[type="submit"]');
        btnSubmit.disabled = true;
        btnSubmit.textContent = 'Acessando...';

        const payload = {
            email: form.email.value,
            senha: form.senha.value,
            lembrar: form.lembrar.checked
        };

        try {
            const response = await axios.post('/api/login/medico', payload, {
                headers: { 'Accept': 'application/json' }
            });

            localStorage.setItem('token', response.data.token);
            window.location.href = '/medico';

        } catch (error) {
            btnSubmit.disabled = false;
            btnSubmit.textContent = 'Entrar no Portal Médico';

            if (error.response && error.response.status === 422) {
                const errorSpan = document.querySelector('#email').parentElement.querySelector('.field-error');
                if (errorSpan) errorSpan.textContent = 'Acesso negado. Credenciais médicas inválidas.';
            } else {
                alert('Erro no servidor.');
            }
        }
    });
});
