<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/classes/Mailer.php';
require_once __DIR__ . '/includes/funcoes.php';
require_once __DIR__ . '/includes/assinaturas.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: dashboard.php');
    exit;
}

$tipo = trim($_POST['tipo'] ?? '');
$nome_candidato = trim($_POST['nome_candidato'] ?? '');
$cpf_candidato = trim($_POST['cpf_candidato'] ?? '');
$cargo_atual = trim($_POST['cargo_atual'] ?? '');
$cargo_novo = trim($_POST['cargo_novo'] ?? '');
$salario_atual = ($_POST['salario_atual'] !== '') ? $_POST['salario_atual'] : null;
$salario_novo = ($_POST['salario_novo'] !== '') ? $_POST['salario_novo'] : null;
$data_inicio_prevista = ($_POST['data_inicio_prevista'] !== '') ? $_POST['data_inicio_prevista'] : null;
$motivo = trim($_POST['motivo'] ?? '');

$precisa_equipamentos = isset($_POST['precisa_equipamentos']) ? 1 : 0;
$precisa_email_corporativo = isset($_POST['precisa_email_corporativo']) ? 1 : 0;
$precisa_cadastro_sistema = isset($_POST['precisa_cadastro_sistema']) ? 1 : 0;

if ($tipo === '' || $motivo === '') {
    die('Campos obrigatórios não preenchidos.');
}

if ($tipo !== 'admissao' && $nome_candidato === '') {
    die('Nome do funcionário é obrigatório.');
}

$tiposPermitidos = ['admissao', 'demissao', 'mudanca_cargo_salario'];
if (!in_array($tipo, $tiposPermitidos, true)) {
    die('Tipo de solicitação inválido.');
}

$sqlUsuario = "SELECT nome, email, perfil, setor, unidade
               FROM usuarios
               WHERE id = :id
               LIMIT 1";
$stmtUsuario = $pdo->prepare($sqlUsuario);
$stmtUsuario->execute([
    ':id' => $_SESSION['usuario_id']
]);
$usuarioLogado = $stmtUsuario->fetch();

$sql = "INSERT INTO solicitacoes (
            tipo,
            status,
            id_solicitante,
            setor_solicitante,
            unidade_solicitante,
            nome_candidato,
            cpf_candidato,
            cargo_atual,
            cargo_novo,
            salario_atual,
            salario_novo,
            motivo,
            data_inicio_prevista,
            data_solicitacao,
            precisa_equipamentos,
            precisa_email_corporativo,
            precisa_cadastro_sistema
        ) VALUES (
            :tipo,
            'aberto',
            :id_solicitante,
            :setor_solicitante,
            :unidade_solicitante,
            :nome_candidato,
            :cpf_candidato,
            :cargo_atual,
            :cargo_novo,
            :salario_atual,
            :salario_novo,
            :motivo,
            :data_inicio_prevista,
            NOW(),
            :precisa_equipamentos,
            :precisa_email_corporativo,
            :precisa_cadastro_sistema
        )";

$stmt = $pdo->prepare($sql);
$stmt->execute([
    ':tipo' => $tipo,
    ':id_solicitante' => $_SESSION['usuario_id'],
    ':setor_solicitante' => !empty($usuarioLogado['setor']) ? $usuarioLogado['setor'] : null,
    ':unidade_solicitante' => !empty($usuarioLogado['unidade']) ? $usuarioLogado['unidade'] : null,
    ':nome_candidato' => $nome_candidato !== '' ? $nome_candidato : null,
    ':cpf_candidato' => $cpf_candidato !== '' ? $cpf_candidato : null,
    ':cargo_atual' => $cargo_atual !== '' ? $cargo_atual : null,
    ':cargo_novo' => $cargo_novo !== '' ? $cargo_novo : null,
    ':salario_atual' => $salario_atual,
    ':salario_novo' => $salario_novo,
    ':motivo' => $motivo,
    ':data_inicio_prevista' => $data_inicio_prevista,
    ':precisa_equipamentos' => $precisa_equipamentos,
    ':precisa_email_corporativo' => $precisa_email_corporativo,
    ':precisa_cadastro_sistema' => $precisa_cadastro_sistema
]);

$idSolicitacao = $pdo->lastInsertId();

$sqlLog = "INSERT INTO solicitacoes_logs (
                id_solicitacao,
                id_usuario,
                acao,
                status_anterior,
                status_novo,
                observacao,
                data_acao
            ) VALUES (
                :id_solicitacao,
                :id_usuario,
                'criacao',
                NULL,
                'aberto',
                :observacao,
                NOW()
            )";

$stmtLog = $pdo->prepare($sqlLog);
$stmtLog->execute([
    ':id_solicitacao' => $idSolicitacao,
    ':id_usuario' => $_SESSION['usuario_id'],
    ':observacao' => 'Solicitação criada pelo usuário.'
]);

registrarAssinaturaSolicitacao(
    $pdo,
    $idSolicitacao,
    $_SESSION['usuario_id'],
    'aberto',
    'criacao'
);

$sqlBusca = "SELECT 
                s.id,
                s.tipo,
                s.nome_candidato,
                s.motivo,
                s.data_solicitacao,
                u.nome AS solicitante_nome,
                u.email AS solicitante_email
            FROM solicitacoes s
            INNER JOIN usuarios u ON u.id = s.id_solicitante
            WHERE s.id = :id
            LIMIT 1";
$stmtBusca = $pdo->prepare($sqlBusca);
$stmtBusca->execute([':id' => $idSolicitacao]);
$dadosEmail = $stmtBusca->fetch();

if ($dadosEmail) {
    $dadosEmail['tipo_label'] = traduzTipoSolicitacao($dadosEmail['tipo']);
    $dadosEmail['data_solicitacao'] = date('d/m/Y H:i', strtotime($dadosEmail['data_solicitacao']));

    $html = renderTemplate(__DIR__ . '/templates/email_nova_solicitacao.php', [
        'id' => $dadosEmail['id'],
        'tipo_label' => $dadosEmail['tipo_label'],
        'solicitante_nome' => $dadosEmail['solicitante_nome'],
        'solicitante_email' => $dadosEmail['solicitante_email'],
        'nome_candidato' => $dadosEmail['nome_candidato'],
        'data_solicitacao' => $dadosEmail['data_solicitacao'],
        'motivo' => $dadosEmail['motivo'],
        
        'precisa_equipamentos' => $precisa_equipamentos,
        'precisa_email_corporativo' => $precisa_email_corporativo,
        'precisa_cadastro_sistema' => $precisa_cadastro_sistema
    ]);

    $mailer = new Mailer($pdo);
    $destinatariosFixos = $mailer->buscarDestinatariosFixos();

    $mailer->enviar(
        'Nova solicitação RH #' . $dadosEmail['id'],
        $html,
        $destinatariosFixos,
        $dadosEmail['id'],
        'nova_solicitacao'
    );
}

header('Location: dashboard.php?msg=sucesso');
exit;