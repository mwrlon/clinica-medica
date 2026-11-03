import axios from 'axios';
import { limparErros, mostrarErro, salvarSessao, PAINEL } from '../lib/sessao.js';
import { avisar, avisarErro } from '../lib/ui.js';

document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('form-cadastro');
    if (!form) {
        console.error('Formulário #form-cadastro não encontrado no HTML.');
        return;
    }

    const btnSubmit = form.querySelector('button[type="submit"]');

    form.addEventListener('submit', async (e) => {
        e.preventDefault();

        limparErros(form);

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

            const primeiroNome = (response.data.usuario?.nome ?? '').trim().split(/\s+/)[0];

            await avisar({
                tipo: 'sucesso',
                titulo: 'Conta criada com sucesso!',
                mensagem: `${primeiroNome ? `${primeiroNome}, sua` : 'Sua'} conta ProSaúde está pronta. Estamos levando você para a sua área.`,
                botao: 'Ir para minha área',
                fecharEm: 3200,
            });

            window.location.href = PAINEL.paciente;
        } catch (error) {
            if (btnSubmit) {
                btnSubmit.textContent = txtOriginal;
                btnSubmit.disabled = false;
            }

            if (error.response?.status === 422 && error.response.data?.errors) {
                const errors = error.response.data.errors;
                const problemas = [];
                let primeiroCampo = null;

                // O problema aparece abaixo do campo e, junto com os demais, em um aviso
                for (const campo in errors) {
                    problemas.push(errors[campo][0]);

                    if (form.querySelector(`[name="${campo}"]`)) {
                        mostrarErro(form, campo, errors[campo][0]);
                        primeiroCampo ??= campo;
                    }
                }

                await avisar({
                    tipo: 'aviso',
                    titulo: 'Revise os dados informados',
                    mensagem: 'Ainda não foi possível criar a sua conta:',
                    itens: problemas,
                    botao: 'Corrigir dados',
                });

                if (primeiroCampo) form.querySelector(`[name="${primeiroCampo}"]`)?.focus();
            } else {
                avisarErro(error, {
                    padrao: 'Ocorreu um erro inesperado ao tentar cadastrar.',
                    titulo: 'Não foi possível criar a conta',
                });
            }
        }
    });
});
