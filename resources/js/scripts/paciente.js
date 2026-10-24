import axios from 'axios';

document.addEventListener('DOMContentLoaded', async () => {
    const token = localStorage.getItem('token');
    
    // Proteção de rota via Frontend (redireciona se não tiver token)
    if (!token) {
        window.location.href = '/login';
        return;
    }

    // Configuração global para as requisições desta tela
    const api = axios.create({
        baseURL: '/api',
        headers: {
            'Accept': 'application/json',
            'Authorization': `Bearer ${token}`
        }
    });

    // 1. Função de Logout
    const btnLogout = document.querySelector('header a[href="{{ url(\'/login\') }}"]');
    if (btnLogout) {
        btnLogout.addEventListener('click', async (e) => {
            e.preventDefault();
            try {
                await api.post('/logout');
            } catch (err) {
                console.error(err);
            } finally {
                localStorage.removeItem('token');
                window.location.href = '/login';
            }
        });
    }

    // 2. Buscar Consultas
    try {
        const resConsultas = await api.get('/consultas');
        console.log("Minhas Consultas:", resConsultas.data);
        // Exemplo de como você injetaria isso dinamicamente na DOM posteriormente
    } catch (error) {
        if (error.response && error.response.status === 401) {
            localStorage.removeItem('token');
            window.location.href = '/login';
        }
    }

    // 3. Buscar Exames
    try {
        const resExames = await api.get('/exames');
        console.log("Meus Exames:", resExames.data);
    } catch (error) {
        console.error("Erro ao buscar exames:", error);
    }
});