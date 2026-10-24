import axios from 'axios';

document.addEventListener('DOMContentLoaded', async () => {
    const token = localStorage.getItem('token');
    
    if (!token) {
        window.location.href = '/login-medico';
        return;
    }

    const api = axios.create({
        baseURL: '/api',
        headers: {
            'Accept': 'application/json',
            'Authorization': `Bearer ${token}`
        }
    });

    // Logout
    const btnLogout = document.querySelector('header a[href="{{ url(\'/login\') }}"]');
    if (btnLogout) {
        btnLogout.addEventListener('click', async (e) => {
            e.preventDefault();
            try {
                await api.post('/logout');
            } catch (err) {}
            localStorage.removeItem('token');
            window.location.href = '/login-medico';
        });
    }

    // Gerenciar Agenda
    const inputDate = document.querySelector('#agenda input[type="date"]');
    
    const fetchAgenda = async (dataSelecionada) => {
        try {
            // Busca consultas do dia selecionado
            const response = await api.get(`/consultas?data=${dataSelecionada}`);
            const agendamentos = response.data;
            
            console.log(`Agenda para ${dataSelecionada}:`, agendamentos);
            // Aqui seria a lógica de manipular a tag <tbody> com o JS
        } catch (error) {
            if (error.response && error.response.status === 401) {
                localStorage.removeItem('token');
                window.location.href = '/login-medico';
            }
        }
    };

    if (inputDate) {
        // Carrega inicial
        fetchAgenda(inputDate.value);

        // Dispara sempre que o médico alterar o calendário
        inputDate.addEventListener('change', (e) => fetchAgenda(e.target.value));
    }
});