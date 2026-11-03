@extends('layouts.institucional')

@section('titulo', 'Termos de uso')

@section('conteudo')
    <article class="documento">
        <header class="documento-topo">
            <span class="tag">Documento legal</span>
            <h1>Termos de uso</h1>
            <p class="documento-meta">Última atualização: 28 de outubro de 2026</p>
        </header>

        <p>Estes termos explicam como funciona o uso do site e da área do paciente da ProSaúde. Ao criar uma conta ou usar os serviços online, você declara que leu e concorda com o que está descrito abaixo.</p>

        <h2>1. O que é o serviço</h2>
        <p>A ProSaúde oferece, pela internet, o agendamento e o acompanhamento de consultas, o acesso a exames e receitas emitidos pela clínica e a atualização dos seus dados cadastrais. O site não presta atendimento médico: a consulta, o diagnóstico e a prescrição acontecem sempre com um profissional da clínica.</p>

        <div class="documento-destaque" role="note">
            <strong>Em caso de urgência ou emergência</strong>
            <p>Não use o site para pedir ajuda. Ligue para o SAMU (192) ou procure o pronto-atendimento mais próximo.</p>
        </div>

        <h2>2. Cadastro e conta</h2>
        <ul>
            <li>Você precisa ter 18 anos ou mais para criar uma conta. Menores de idade devem ser cadastrados por um responsável legal.</li>
            <li>As informações do cadastro (nome, CPF, data de nascimento, telefone e e-mail) devem ser verdadeiras e estar atualizadas. Você pode corrigi-las na aba “Meu Perfil”.</li>
            <li>A senha é pessoal e intransferível. Você é responsável por tudo o que for feito com a sua conta; se suspeitar de uso indevido, troque a senha imediatamente e avise a clínica.</li>
            <li>Cada CPF e cada e-mail podem ter apenas uma conta.</li>
        </ul>

        <h2>3. Agendamento, reagendamento e cancelamento</h2>
        <ul>
            <li>Os horários exibidos seguem o expediente da clínica e a agenda de cada médico: segunda a sexta, das 7h às 19h, e sábado, das 8h às 12h.</li>
            <li>A consulta só está garantida depois que o sistema confirma o agendamento. Dois pacientes não podem ocupar o mesmo horário.</li>
            <li>Você pode reagendar ou cancelar pela sua área enquanto a consulta estiver agendada. Ao cancelar, o horário volta a ficar livre para outros pacientes; por isso, avise o quanto antes.</li>
            <li>A clínica pode remarcar uma consulta por motivo de força maior (por exemplo, indisponibilidade do médico) e entrará em contato pelos dados do seu cadastro.</li>
        </ul>

        <h2>4. Uso adequado</h2>
        <p>Você se compromete a não tentar acessar contas de outras pessoas, não interferir no funcionamento do sistema, não usar o site para enviar conteúdo ilícito ou ofensivo e não agendar consultas sem intenção de comparecer.</p>

        <h2>5. Informações de saúde</h2>
        <p>Receitas, exames e demais registros exibidos na sua área são os emitidos pelos profissionais da clínica. O conteúdo do site e das mensagens automáticas é informativo e não substitui a orientação do seu médico.</p>

        <h2>6. Privacidade</h2>
        <p>O tratamento dos seus dados pessoais e de saúde está descrito na <a href="{{ url('/privacidade') }}">política de privacidade</a>, que faz parte destes termos.</p>

        <h2>7. Disponibilidade do serviço</h2>
        <p>Trabalhamos para manter o site no ar, mas ele pode ficar indisponível por manutenção ou por falhas fora do nosso controle. Quando isso acontecer, a recepção continua atendendo pelos canais de contato da clínica.</p>

        <h2>8. Propriedade intelectual</h2>
        <p>O nome ProSaúde, o logotipo, os textos, as imagens e o código do site pertencem à clínica ou são usados com autorização, e não podem ser copiados ou reutilizados sem permissão.</p>

        <h2>9. Alterações destes termos</h2>
        <p>Podemos atualizar estes termos para refletir mudanças no serviço ou na lei. A data da última atualização fica no topo desta página; quando a mudança for relevante, avisaremos pelo site ou por e-mail.</p>

        <h2>10. Contato e foro</h2>
        <p>Dúvidas sobre estes termos podem ser enviadas para <a href="mailto:contato@prosaude.com.br">contato@prosaude.com.br</a> ou pelo telefone (51) 99999-9999. Fica eleito o foro da comarca de Erechim, RS, para resolver eventuais conflitos, sem prejuízo dos direitos do consumidor previstos em lei.</p>
    </article>
@endsection
