@extends('layouts.institucional')

@section('titulo', 'Política de privacidade')

@section('conteudo')
    <article class="documento">
        <header class="documento-topo">
            <span class="tag">Documento legal</span>
            <h1>Política de privacidade</h1>
            <p class="documento-meta">Última atualização: 28 de outubro de 2026</p>
        </header>

        <p>A ProSaúde respeita a sua privacidade. Esta política explica quais dados coletamos, para que os usamos, com quem os compartilhamos e como você pode exercer os seus direitos, conforme a Lei Geral de Proteção de Dados (Lei nº 13.709/2018, a LGPD).</p>

        <h2>1. Quem é o responsável pelos dados</h2>
        <p>O controlador dos dados é a ProSaúde, CNPJ 00.000.000/0001-00, com sede na Rua das Acácias, 482, Erechim, RS. O contato para assuntos de privacidade é <a href="mailto:contato@prosaude.com.br">contato@prosaude.com.br</a>.</p>

        <h2>2. Quais dados coletamos</h2>
        <ul>
            <li><strong>Dados de cadastro:</strong> nome, CPF, data de nascimento, sexo, telefone, e-mail e, se você informar, endereço e convênio.</li>
            <li><strong>Dados de acesso:</strong> senha (guardada apenas de forma criptografada) e informações técnicas necessárias para manter a sua sessão aberta.</li>
            <li><strong>Dados de saúde (sensíveis):</strong> consultas agendadas e realizadas, diagnósticos, receitas, exames e observações registradas pelos profissionais durante o atendimento.</li>
        </ul>

        <h2>3. Para que usamos os dados</h2>
        <ul>
            <li>Criar e manter a sua conta e permitir o agendamento, o reagendamento e o cancelamento de consultas.</li>
            <li>Prestar o atendimento de saúde e manter o seu prontuário, com exames e receitas disponíveis na sua área.</li>
            <li>Entrar em contato com você sobre consultas, remarcações e redefinição de senha.</li>
            <li>Cumprir obrigações legais e regulatórias, e proteger a clínica em eventuais disputas.</li>
            <li>Manter a segurança do sistema e prevenir fraudes e acessos indevidos.</li>
        </ul>

        <h2>4. Bases legais</h2>
        <p>Tratamos os seus dados para a execução do contrato de prestação de serviços de saúde, para o cumprimento de obrigações legais e regulatórias, para a tutela da saúde em procedimentos realizados por profissionais de saúde e, quando necessário, com o seu consentimento, que pode ser retirado a qualquer momento.</p>

        <h2>5. Com quem compartilhamos</h2>
        <ul>
            <li><strong>Profissionais da clínica</strong>, que acessam apenas os dados necessários para atender você.</li>
            <li><strong>Convênios e operadoras</strong>, quando o atendimento for realizado por meio de um plano, na medida exigida para a cobrança.</li>
            <li><strong>Prestadores de serviço</strong> de tecnologia e hospedagem, que seguem as nossas instruções e o dever de sigilo.</li>
            <li><strong>Autoridades</strong>, quando houver obrigação legal ou ordem judicial.</li>
        </ul>
        <p>Não vendemos os seus dados e não os usamos para publicidade de terceiros.</p>

        <h2>6. Por quanto tempo guardamos</h2>
        <p>Os dados de cadastro ficam guardados enquanto a sua conta existir. Os registros do prontuário são mantidos pelo prazo mínimo exigido pela legislação e pelas normas do Conselho Federal de Medicina (hoje, 20 anos a partir do último registro). Depois disso, os dados são excluídos ou anonimizados.</p>

        <h2>7. Segurança</h2>
        <p>Adotamos medidas técnicas e organizacionais para proteger os seus dados: senhas criptografadas, acesso restrito por perfil (paciente, médico e administração), sessões que expiram e registro de acessos. Nenhum sistema é totalmente imune a falhas; se ocorrer um incidente que possa afetar você, avisaremos você e a Autoridade Nacional de Proteção de Dados, como a lei determina.</p>

        <h2>8. Armazenamento no seu navegador</h2>
        <p>O site não usa cookies de publicidade nem de rastreamento. Para manter você conectado, guardamos no navegador um código de acesso: na memória temporária da aba, ou no armazenamento local se você marcar “Lembrar de mim”. Ao clicar em “Sair”, esse código é apagado.</p>

        <h2>9. Seus direitos</h2>
        <p>Você pode, a qualquer momento e mediante pedido:</p>
        <ul>
            <li>confirmar que tratamos os seus dados e acessá-los;</li>
            <li>corrigir dados incompletos, inexatos ou desatualizados (também pela aba “Meu Perfil”);</li>
            <li>pedir a anonimização, o bloqueio ou a eliminação de dados desnecessários ou tratados em desacordo com a lei;</li>
            <li>pedir a portabilidade dos dados e informações sobre com quem os compartilhamos;</li>
            <li>retirar o consentimento e se opor a tratamentos que não dependam dele.</li>
        </ul>
        <p>Alguns dados, como os do prontuário, precisam ser mantidos por obrigação legal e não podem ser apagados antes do prazo. Responderemos aos pedidos no prazo previsto em lei.</p>

        <h2>10. Alterações desta política</h2>
        <p>Podemos atualizar esta política. A data da última atualização fica no topo da página e, em mudanças relevantes, avisaremos você pelo site ou por e-mail.</p>

        <h2>11. Fale conosco</h2>
        <p>Para exercer seus direitos ou tirar dúvidas, escreva para <a href="mailto:contato@prosaude.com.br">contato@prosaude.com.br</a> ou ligue para (51) 99999-9999. Veja também os <a href="{{ url('/termos') }}">termos de uso</a>.</p>
    </article>
@endsection
