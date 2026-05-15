<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/classes/Mailer.php';
require_once __DIR__ . '/includes/funcoes.php';
require_once __DIR__ . '/includes/assinaturas.php';
require_once __DIR__ . '/includes/pdf_solicitacao.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: dashboard.php');
    exit;
}

$id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
$acao = trim($_POST['acao'] ?? '');
$perfil = $_SESSION['usuario_perfil'];
$usuarioId = $_SESSION['usuario_id'];

if ($id <= 0 || $acao === '') {
    header('Location: dashboard.php?msg=erro_status');
    exit;
}

$sql = "SELECT * FROM solicitacoes WHERE id = :id LIMIT 1";
$stmt = $pdo->prepare($sql);
$stmt->execute([':id' => $id]);
$solicitacao = $stmt->fetch();

if (!$solicitacao) {
    header('Location: dashboard.php?msg=erro_status');
    exit;
}

try {
    $pdo->beginTransaction();

    if ($acao === 'aprovar') {
        if (!in_array($perfil, ['diretor', 'admin'], true)) {
            $pdo->rollBack();
            header('Location: dashboard.php?msg=erro_permissao');
            exit;
        }

        if ($solicitacao['status'] !== 'aberto') {
            $pdo->rollBack();
            header('Location: dashboard.php?msg=erro_status');
            exit;
        }

        $sqlUpdate = "UPDATE solicitacoes
                      SET status = 'em_andamento',
                          id_aprovador = :id_aprovador,
                          data_aprovacao = NOW(),
                          data_atualizacao = NOW()
                      WHERE id = :id";
        $stmtUpdate = $pdo->prepare($sqlUpdate);
        $stmtUpdate->execute([
            ':id_aprovador' => $usuarioId,
            ':id' => $id
        ]);

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
                        'aprovacao_diretor',
                        'aberto',
                        'em_andamento',
                        :observacao,
                        NOW()
                    )";
        $stmtLog = $pdo->prepare($sqlLog);
        $stmtLog->execute([
            ':id_solicitacao' => $id,
            ':id_usuario' => $usuarioId,
            ':observacao' => 'Solicitação aprovada pelo diretor.'
        ]);

        registrarAssinaturaSolicitacao(
            $pdo,
            $id,
            $usuarioId,
            'em_andamento',
            'aprovacao_diretor'
        );

        $pdo->commit();

        $sqlEmail = "SELECT
                s.id,
                s.tipo,
                s.nome_candidato,
                s.data_aprovacao,
                u.nome AS solicitante_nome,
                u.email AS solicitante_email
             FROM solicitacoes s
             INNER JOIN usuarios u ON u.id = s.id_solicitante
             WHERE s.id = :id
             LIMIT 1";
        $stmtEmail = $pdo->prepare($sqlEmail);
        $stmtEmail->execute([':id' => $id]);
        $dadosEmail = $stmtEmail->fetch();

        if ($dadosEmail) {
            $dadosEmail['tipo_label'] = traduzTipoSolicitacao($dadosEmail['tipo']);
            $dadosEmail['data_aprovacao'] = !empty($dadosEmail['data_aprovacao'])
                ? date('d/m/Y H:i', strtotime($dadosEmail['data_aprovacao']))
                : '-';

            $html = renderTemplate(__DIR__ . '/templates/email_status_andamento.php', [
                'id' => $dadosEmail['id'],
                'tipo_label' => $dadosEmail['tipo_label'],
                'solicitante_nome' => $dadosEmail['solicitante_nome'],
                'nome_candidato' => $dadosEmail['nome_candidato'],
                'data_aprovacao' => $dadosEmail['data_aprovacao']
            ]);

            $mailer = new Mailer($pdo);

            $destinatarios = [
                [
                    'nome' => $dadosEmail['solicitante_nome'],
                    'email' => $dadosEmail['solicitante_email']
                ]
            ];

            $mailer->enviar(
                'Solicitação RH #' . $dadosEmail['id'] . ' em andamento',
                $html,
                $destinatarios,
                $dadosEmail['id'],
                'status_em_andamento'
            );
        }

        header('Location: dashboard.php?msg=aprovado');
        exit;
    }

    if ($acao === 'reprovar') {

        if (!in_array($perfil, ['diretor', 'admin'], true)) {
            $pdo->rollBack();
            header('Location: dashboard.php?msg=erro_permissao');
            exit;
        }

        if ($solicitacao['status'] !== 'aberto') {
            $pdo->rollBack();
            header('Location: dashboard.php?msg=erro_status');
            exit;
        }

        $motivoReprovacao = trim($_POST['motivo_reprovacao'] ?? '');

        if ($motivoReprovacao === '') {
            $pdo->rollBack();
            die('O motivo da reprovação é obrigatório.');
        }

        $sqlUpdate = "UPDATE solicitacoes
                    SET status = 'reprovado',
                        motivo_reprovacao = :motivo_reprovacao,
                        data_reprovacao = NOW(),
                        data_atualizacao = NOW()
                    WHERE id = :id";

        $stmtUpdate = $pdo->prepare($sqlUpdate);
        $stmtUpdate->execute([
            ':motivo_reprovacao' => $motivoReprovacao,
            ':id' => $id
        ]);

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
                        'reprovacao_diretor',
                        'aberto',
                        'reprovado',
                        :observacao,
                        NOW()
                    )";

        $stmtLog = $pdo->prepare($sqlLog);
        $stmtLog->execute([
            ':id_solicitacao' => $id,
            ':id_usuario' => $usuarioId,
            ':observacao' => $motivoReprovacao
        ]);

        registrarAssinaturaSolicitacao(
            $pdo,
            $id,
            $usuarioId,
            'reprovado',
            'reprovacao_diretor'
        );

        $pdo->commit();

        $sqlEmail = "SELECT
                s.id,
                s.tipo,
                s.nome_candidato,
                s.motivo_reprovacao,
                s.data_solicitacao,
                s.precisa_equipamentos,
                s.precisa_email_corporativo,
                s.precisa_cadastro_sistema,
                u.nome AS solicitante_nome,
                u.email AS solicitante_email
            FROM solicitacoes s
            INNER JOIN usuarios u ON u.id = s.id_solicitante
            WHERE s.id = :id
            LIMIT 1";

        $stmtEmail = $pdo->prepare($sqlEmail);
        $stmtEmail->execute([':id' => $id]);
        $dadosEmail = $stmtEmail->fetch();

        if ($dadosEmail) {
            $dadosEmail['tipo_label'] = traduzTipoSolicitacao($dadosEmail['tipo']);
            $dadosEmail['data_solicitacao'] = date('d/m/Y H:i', strtotime($dadosEmail['data_solicitacao']));

            $html = renderTemplate(__DIR__ . '/templates/email_status_reprovado.php', [
                'id' => $dadosEmail['id'],
                'tipo_label' => $dadosEmail['tipo_label'],
                'solicitante_nome' => $dadosEmail['solicitante_nome'],
                'solicitante_email' => $dadosEmail['solicitante_email'],
                'nome_candidato' => $dadosEmail['nome_candidato'],
                'data_solicitacao' => $dadosEmail['data_solicitacao'],
                'motivo_reprovacao' => $dadosEmail['motivo_reprovacao'],
                'precisa_equipamentos' => $dadosEmail['precisa_equipamentos'],
                'precisa_email_corporativo' => $dadosEmail['precisa_email_corporativo'],
                'precisa_cadastro_sistema' => $dadosEmail['precisa_cadastro_sistema']
            ]);

            $mailer = new Mailer($pdo);

            $destinatarios = [
                [
                    'nome' => $dadosEmail['solicitante_nome'],
                    'email' => $dadosEmail['solicitante_email']
                ]
            ];

            $mailer->enviar(
                'Solicitação RH #' . $dadosEmail['id'] . ' reprovada',
                $html,
                $destinatarios,
                $dadosEmail['id'],
                'status_reprovado'
            );
        }

        header('Location: dashboard.php?msg=reprovado');
        exit;
    }

    if ($acao === 'reabrir') {

        if (!in_array($perfil, ['rh', 'dho', 'admin'], true)) {
            $pdo->rollBack();
            header('Location: dashboard.php?msg=erro_permissao');
            exit;
        }

        if ($solicitacao['status'] !== 'reprovado') {
            $pdo->rollBack();
            header('Location: dashboard.php?msg=erro_status');
            exit;
        }

        $observacaoReabertura = trim($_POST['observacao_reabertura'] ?? '');

        if ($observacaoReabertura === '') {
            $pdo->rollBack();
            die('A observação da reabertura é obrigatória.');
        }

        $sqlUpdate = "UPDATE solicitacoes
                    SET status = 'aberto',
                        observacao_reabertura = :observacao_reabertura,
                        data_reabertura = NOW(),
                        data_atualizacao = NOW()
                    WHERE id = :id";

        $stmtUpdate = $pdo->prepare($sqlUpdate);
        $stmtUpdate->execute([
            ':observacao_reabertura' => $observacaoReabertura,
            ':id' => $id
        ]);

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
                        'reabertura_rh',
                        'reprovado',
                        'aberto',
                        :observacao,
                        NOW()
                    )";

        $stmtLog = $pdo->prepare($sqlLog);
        $stmtLog->execute([
            ':id_solicitacao' => $id,
            ':id_usuario' => $usuarioId,
            ':observacao' => $observacaoReabertura
        ]);

        registrarAssinaturaSolicitacao(
            $pdo,
            $id,
            $usuarioId,
            'aberto',
            'reabertura_rh'
        );

        $pdo->commit();

        $sqlEmail = "SELECT
                s.id,
                s.tipo,
                s.nome_candidato,
                s.observacao_reabertura,
                s.data_solicitacao,
                s.precisa_equipamentos,
                s.precisa_email_corporativo,
                s.precisa_cadastro_sistema,
                u.nome AS solicitante_nome,
                u.email AS solicitante_email
            FROM solicitacoes s
            INNER JOIN usuarios u ON u.id = s.id_solicitante
            WHERE s.id = :id
            LIMIT 1";

        $stmtEmail = $pdo->prepare($sqlEmail);
        $stmtEmail->execute([':id' => $id]);
        $dadosEmail = $stmtEmail->fetch();

        if ($dadosEmail) {
            $dadosEmail['tipo_label'] = traduzTipoSolicitacao($dadosEmail['tipo']);
            $dadosEmail['data_solicitacao'] = date('d/m/Y H:i', strtotime($dadosEmail['data_solicitacao']));

            $html = renderTemplate(__DIR__ . '/templates/email_status_reaberto.php', [
                'id' => $dadosEmail['id'],
                'tipo_label' => $dadosEmail['tipo_label'],
                'solicitante_nome' => $dadosEmail['solicitante_nome'],
                'solicitante_email' => $dadosEmail['solicitante_email'],
                'nome_candidato' => $dadosEmail['nome_candidato'],
                'data_solicitacao' => $dadosEmail['data_solicitacao'],
                'observacao_reabertura' => $dadosEmail['observacao_reabertura'],
                'precisa_equipamentos' => $dadosEmail['precisa_equipamentos'],
                'precisa_email_corporativo' => $dadosEmail['precisa_email_corporativo'],
                'precisa_cadastro_sistema' => $dadosEmail['precisa_cadastro_sistema']
            ]);

            $mailer = new Mailer($pdo);
            $destinatariosFixos = $mailer->buscarDestinatariosFixos();

            $mailer->enviar(
                'Solicitação RH #' . $dadosEmail['id'] . ' reaberta para aprovação',
                $html,
                $destinatariosFixos,
                $dadosEmail['id'],
                'status_reaberto'
            );
        }

        header('Location: dashboard.php?msg=reaberto');
        exit;
    }

    if ($acao === 'resolver') {
        if (!in_array($perfil, ['rh', 'dho', 'admin'], true)) {
            $pdo->rollBack();
            header('Location: dashboard.php?msg=erro_permissao');
            exit;
        }

        if ($solicitacao['status'] !== 'em_andamento') {
            $pdo->rollBack();
            header('Location: dashboard.php?msg=erro_status');
            exit;
        }

        $observacaoFinal = trim($_POST['observacao_final'] ?? '');
        $dataInicioReal = trim($_POST['data_inicio_real'] ?? '');
        $situacaoVaga = trim($_POST['situacao_vaga'] ?? '');

        $rhNomeCandidato = trim($_POST['rh_nome_candidato'] ?? '');
        $rhCpf = trim($_POST['rh_cpf'] ?? '');
        $rhDataNascimento = trim($_POST['rh_data_nascimento'] ?? '');
        $rhSetor = trim($_POST['rh_setor'] ?? '');
        $rhFilial = trim($_POST['rh_filial'] ?? '');
        $rhAdmissao = trim($_POST['rh_admissao'] ?? '');
        $rhHorarioTrabalho = trim($_POST['rh_horario_trabalho'] ?? '');
        $rhFuncao = trim($_POST['rh_funcao'] ?? '');

        if ($observacaoFinal === '') {
            $pdo->rollBack();
            die('A observação final é obrigatória.');
        }

        $observacaoCompleta = $observacaoFinal;

        if ($situacaoVaga !== '') {
            $observacaoCompleta .= "\nSituação da vaga: " . $situacaoVaga;
        }

        if ($dataInicioReal !== '') {
            $observacaoCompleta .= "\nData de início: " . date('d/m/Y', strtotime($dataInicioReal));
        }

        if ($solicitacao['tipo'] === 'admissao' && ($rhNomeCandidato === '' || $rhCpf === '')) {
            $pdo->rollBack();
            die('Nome do candidato e CPF são obrigatórios para finalizar uma admissão.');
        }

        $sqlUpdate = "UPDATE solicitacoes
              SET status = 'resolvido',
                  id_responsavel = :id_responsavel,
                  observacao_final = :observacao_final,
                  situacao_vaga = :situacao_vaga,
                  data_resolucao = NOW(),
                  data_inicio_real = :data_inicio_real,
                  rh_nome_candidato = :rh_nome_candidato,
                  rh_cpf = :rh_cpf,
                  rh_data_nascimento = :rh_data_nascimento,
                  rh_setor = :rh_setor,
                  rh_filial = :rh_filial,
                  rh_admissao = :rh_admissao,
                  rh_horario_trabalho = :rh_horario_trabalho,
                  rh_funcao = :rh_funcao,
                  data_atualizacao = NOW()
              WHERE id = :id";
        $stmtUpdate = $pdo->prepare($sqlUpdate);
        $stmtUpdate->execute([
            ':id_responsavel' => $usuarioId,
            ':observacao_final' => $observacaoCompleta,
            ':situacao_vaga' => $situacaoVaga !== '' ? $situacaoVaga : null,
            ':data_inicio_real' => $dataInicioReal !== '' ? $dataInicioReal : null,
            ':id' => $id,
            ':rh_nome_candidato' => $rhNomeCandidato !== '' ? $rhNomeCandidato : null,
            ':rh_cpf' => $rhCpf !== '' ? $rhCpf : null,
            ':rh_data_nascimento' => $rhDataNascimento !== '' ? $rhDataNascimento : null,
            ':rh_setor' => $rhSetor !== '' ? $rhSetor : null,
            ':rh_filial' => $rhFilial !== '' ? $rhFilial : null,
            ':rh_admissao' => $rhAdmissao !== '' ? $rhAdmissao : null,
            ':rh_horario_trabalho' => $rhHorarioTrabalho !== '' ? $rhHorarioTrabalho : null,
            ':rh_funcao' => $rhFuncao !== '' ? $rhFuncao : null,
        ]);

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
                        'finalizacao_rh',
                        'em_andamento',
                        'resolvido',
                        :observacao,
                        NOW()
                    )";
        $stmtLog = $pdo->prepare($sqlLog);
        $stmtLog->execute([
            ':id_solicitacao' => $id,
            ':id_usuario' => $usuarioId,
            ':observacao' => $observacaoCompleta
        ]);

        registrarAssinaturaSolicitacao(
            $pdo,
            $id,
            $usuarioId,
            'resolvido',
            'finalizacao_rh'
        );

        gerarPdfFinalSolicitacao($pdo, $id, $usuarioId);

        $pdo->commit();

        $sqlEmail = "SELECT
                s.id,
                s.tipo,
                s.nome_candidato,
                s.rh_nome_candidato,
                s.rh_cpf,
                s.rh_data_nascimento,
                s.rh_setor,
                s.rh_filial,
                s.rh_admissao,
                s.rh_horario_trabalho,
                s.rh_funcao,
                s.precisa_equipamentos,
                s.precisa_email_corporativo,
                s.precisa_cadastro_sistema,
                s.observacao_final,
                s.data_resolucao,
                u.nome AS solicitante_nome,
                u.email AS solicitante_email
            FROM solicitacoes s
            INNER JOIN usuarios u ON u.id = s.id_solicitante
            WHERE s.id = :id
            LIMIT 1";
        $stmtEmail = $pdo->prepare($sqlEmail);
        $stmtEmail->execute([':id' => $id]);
        $dadosEmail = $stmtEmail->fetch();

        if ($dadosEmail) {
            $dadosEmail['tipo_label'] = traduzTipoSolicitacao($dadosEmail['tipo']);
            $dadosEmail['data_resolucao'] = !empty($dadosEmail['data_resolucao'])
                ? date('d/m/Y H:i', strtotime($dadosEmail['data_resolucao']))
                : '-';

            $html = renderTemplate(__DIR__ . '/templates/email_status_resolvido.php', [
                'id' => $dadosEmail['id'],
                'tipo_label' => $dadosEmail['tipo_label'],
                'solicitante_nome' => $dadosEmail['solicitante_nome'],
                'nome_candidato' => $dadosEmail['nome_candidato'],
                'data_resolucao' => $dadosEmail['data_resolucao'],
                'observacao_final' => $dadosEmail['observacao_final'],
                'rh_nome_candidato' => $dadosEmail['rh_nome_candidato'],
                'rh_cpf' => $dadosEmail['rh_cpf'],
                'rh_data_nascimento' => $dadosEmail['rh_data_nascimento'],
                'rh_setor' => $dadosEmail['rh_setor'],
                'rh_filial' => $dadosEmail['rh_filial'],
                'rh_admissao' => $dadosEmail['rh_admissao'],
                'rh_horario_trabalho' => $dadosEmail['rh_horario_trabalho'],
                'rh_funcao' => $dadosEmail['rh_funcao'],
                'precisa_equipamentos' => $dadosEmail['precisa_equipamentos'],
                'precisa_email_corporativo' => $dadosEmail['precisa_email_corporativo'],
                'precisa_cadastro_sistema' => $dadosEmail['precisa_cadastro_sistema']
            ]);

            $mailer = new Mailer($pdo);

            $destinatarios = [
                [
                    'nome' => $dadosEmail['solicitante_nome'],
                    'email' => $dadosEmail['solicitante_email']
                ]
            ];

            $mailer->enviar(
                'Solicitação RH #' . $dadosEmail['id'] . ' resolvida',
                $html,
                $destinatarios,
                $dadosEmail['id'],
                'status_resolvido'
            );
        }
        header('Location: dashboard.php?msg=resolvido');
        exit;
    }

    $pdo->rollBack();
    header('Location: dashboard.php?msg=erro_status');
    exit;

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    die('Erro ao processar solicitação: ' . $e->getMessage());
}