<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/assinaturas.php';

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$mostrarFinalizacao = isset($_GET['finalizar']) && $_GET['finalizar'] == '1';

if ($id <= 0) {
    header('Location: dashboard.php');
    exit;
}

$sql = "SELECT
            s.*,
            u.nome AS solicitante_nome,
            u.email AS solicitante_email
        FROM solicitacoes s
        INNER JOIN usuarios u ON u.id = s.id_solicitante
        WHERE s.id = :id
        LIMIT 1";
$stmt = $pdo->prepare($sql);
$stmt->execute([':id' => $id]);
$solicitacao = $stmt->fetch();
$assinaturas = buscarAssinaturasSolicitacao($pdo, $id);

if (!$solicitacao) {
    header('Location: dashboard.php');
    exit;
}

$sqlDocs = "SELECT *
            FROM solicitacoes_documentos
            WHERE id_solicitacao = :id_solicitacao
            ORDER BY data_geracao DESC";

$stmtDocs = $pdo->prepare($sqlDocs);
$stmtDocs->execute([':id_solicitacao' => $id]);
$documentos = $stmtDocs->fetchAll();

$perfil = $_SESSION['usuario_perfil'];

$podeAprovar = in_array($perfil, ['diretor', 'admin'], true) && $solicitacao['status'] === 'aberto';

$podeFinalizar = in_array($perfil, ['rh', 'dho', 'admin'], true) && $solicitacao['status'] === 'em_andamento';

$podeReabrir = in_array($perfil, ['rh', 'dho', 'admin'], true) && $solicitacao['status'] === 'reprovado';

function traduzTipo($tipo)
{
    switch ($tipo) {
        case 'admissao':
            return 'Admissão';
        case 'demissao':
            return 'Demissão';
        case 'mudanca_cargo_salario':
            return 'Mudança de Cargo/Salário';
        default:
            return $tipo;
    }
}
?>
<?php include __DIR__ . '/includes/header.php'; ?>

<div class="card">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
        <h2 style="margin:0;">Solicitação #<?php echo $solicitacao['id']; ?></h2>
        <a href="dashboard.php" class="btn">Voltar</a>
    </div>

    <div class="row">
        <div class="col-6">
            <p><strong>Tipo:</strong> <?php echo traduzTipo($solicitacao['tipo']); ?></p>
            <p><strong>Status:</strong> <?php echo htmlspecialchars($solicitacao['status']); ?></p>
            <p><strong>Solicitante:</strong> <?php echo htmlspecialchars($solicitacao['solicitante_nome']); ?></p>
            <p><strong>E-mail do solicitante:</strong> <?php echo htmlspecialchars($solicitacao['solicitante_email']); ?></p>
            <p>
                <strong>Nome candidato / funcionário:</strong>
                <?php
                    echo htmlspecialchars(
                        !empty($solicitacao['rh_nome_candidato'])
                            ? $solicitacao['rh_nome_candidato']
                            : $solicitacao['nome_candidato']
                    );
                ?>
            </p>

            <p>
                <strong>CPF:</strong>
                <?php
                    echo htmlspecialchars(
                        !empty($solicitacao['rh_cpf'])
                            ? $solicitacao['rh_cpf']
                            : $solicitacao['cpf_candidato']
                    );
                ?>
            </p>
        </div>

        <div class="col-6">
            <p><strong>Cargo atual:</strong> <?php echo htmlspecialchars($solicitacao['cargo_atual']); ?></p>
            <p><strong>Novo cargo:</strong> <?php echo htmlspecialchars($solicitacao['cargo_novo']); ?></p>
            <p><strong>Salário atual:</strong> <?php echo $solicitacao['salario_atual'] !== null ? 'R$ ' . number_format($solicitacao['salario_atual'], 2, ',', '.') : '-'; ?></p>
            <p><strong>Novo salário:</strong> <?php echo $solicitacao['salario_novo'] !== null ? 'R$ ' . number_format($solicitacao['salario_novo'], 2, ',', '.') : '-'; ?></p>
            <p><strong>Data prevista de início:</strong> <?php echo !empty($solicitacao['data_inicio_prevista']) ? date('d/m/Y', strtotime($solicitacao['data_inicio_prevista'])) : '-'; ?></p>
            <p><strong>Data da solicitação:</strong> <?php echo date('d/m/Y H:i', strtotime($solicitacao['data_solicitacao'])); ?></p>

            <?php if (
                $solicitacao['precisa_equipamentos'] ||
                $solicitacao['precisa_email_corporativo'] ||
                $solicitacao['precisa_cadastro_sistema']
            ): ?>

                <hr style="margin:20px 0;">

                <p><strong>Necessidades da admissão:</strong></p>

                <ul style="margin-top:8px;">

                    <?php if ($solicitacao['precisa_equipamentos']): ?>
                        <li>Equipamentos</li>
                    <?php endif; ?>

                    <?php if ($solicitacao['precisa_email_corporativo']): ?>
                        <li>E-mail corporativo</li>
                    <?php endif; ?>

                    <?php if ($solicitacao['precisa_cadastro_sistema']): ?>
                        <li>Cadastro no sistema</li>
                    <?php endif; ?>

                </ul>

            <?php endif; ?>
        </div>
    </div>

    <hr style="margin:20px 0;">

    <p><strong>Motivo / Observação inicial:</strong></p>
    <div style="background:#f8f9fa; padding:12px; border-radius:6px;">
        <?php echo nl2br(htmlspecialchars($solicitacao['motivo'])); ?>
    </div>

    <?php if (!empty($solicitacao['observacao_final'])): ?>
        <hr style="margin:20px 0;">
        <p><strong>Observação final:</strong></p>
        <div style="background:#eef7ee; padding:12px; border-radius:6px;">
            <?php echo nl2br(htmlspecialchars($solicitacao['observacao_final'])); ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($solicitacao['motivo_reprovacao'])): ?>
        <hr style="margin:20px 0;">

        <p><strong>Motivo da reprovação:</strong></p>

        <div style="background:#fff1f2; padding:12px; border-radius:6px; border-left:4px solid #dc2626;">
            <?php echo nl2br(htmlspecialchars($solicitacao['motivo_reprovacao'])); ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($assinaturas)): ?>
        <hr style="margin:20px 0;">
        <h3>Assinaturas do processo</h3>

        <?php
        $acoesAssinaturaPrincipal = [
            'criacao',
            'aprovacao_diretor',
            'finalizacao_rh'
        ];
        ?>

        <?php foreach ($assinaturas as $assinatura): ?>

            <?php if (!in_array($assinatura['acao'], $acoesAssinaturaPrincipal, true)) continue; ?>

            <div style="background:#f8f9fa; padding:12px; border-radius:8px; margin-bottom:12px;">

                <p style="margin:0 0 8px;">
                    <strong><?php echo htmlspecialchars($assinatura['nome_exibicao']); ?></strong>
                    - <?php echo htmlspecialchars($assinatura['cargo_exibicao']); ?>
                </p>

                <p style="margin:0 0 8px;">
                    <strong>Etapa:</strong> <?php echo htmlspecialchars($assinatura['etapa']); ?>
                    |
                    <strong>Ação:</strong> <?php echo htmlspecialchars($assinatura['acao']); ?>
                    |
                    <strong>Data:</strong> <?php echo date('d/m/Y H:i', strtotime($assinatura['data_assinatura'])); ?>
                </p>

                <p style="margin:0 0 8px;">
                    <strong>E-mail:</strong> <?php echo htmlspecialchars($assinatura['email_usuario']); ?>
                </p>

                <?php if (!empty($assinatura['assinatura_imagem']) && file_exists(__DIR__ . '/' . $assinatura['assinatura_imagem'])): ?>
                    <div>
                        <img src="<?php echo htmlspecialchars($assinatura['assinatura_imagem']); ?>"
                             alt="Assinatura"
                            style="max-height:90px; border:1px solid #ddd; padding:6px; background:#fff;">
                    </div>
                <?php endif; ?>

            </div>

        <?php endforeach; ?>

        <h3>Histórico do processo</h3>

        <?php foreach ($assinaturas as $assinatura): ?>
            <?php if (in_array($assinatura['acao'], $acoesAssinaturaPrincipal, true)) continue; ?>

            <div style="background:#f8f9fa; padding:10px; border-radius:8px; margin-bottom:8px;">
                <strong>Ação:</strong> <?php echo htmlspecialchars($assinatura['acao']); ?> |
                <strong>Etapa:</strong> <?php echo htmlspecialchars($assinatura['etapa']); ?> |
                <strong>Data:</strong> <?php echo date('d/m/Y H:i', strtotime($assinatura['data_assinatura'])); ?> |
                <strong>Usuário:</strong> <?php echo htmlspecialchars($assinatura['nome_exibicao']); ?>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>

    <?php if (!empty($documentos)): ?>
        <hr style="margin:20px 0;">
        <h3>Documentos gerados</h3>

        <?php foreach ($documentos as $documento): ?>
            <div style="background:#f8f9fa; padding:12px; border-radius:8px; margin-bottom:12px;">
                <p style="margin:0 0 8px;">
                    <strong><?php echo htmlspecialchars($documento['nome_arquivo']); ?></strong>
                </p>

                <p style="margin:0 0 8px;">
                    <strong>Tipo:</strong> <?php echo htmlspecialchars($documento['tipo_documento']); ?>
                    |
                    <strong>Data:</strong> <?php echo date('d/m/Y H:i', strtotime($documento['data_geracao'])); ?>
                </p>

                <a class="btn" href="<?php echo htmlspecialchars($documento['caminho_arquivo']); ?>" target="_blank">Visualizar / Baixar</a>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>

    <?php if ($podeAprovar): ?>
        <div style="display:flex; gap:12px; align-items:flex-start; flex-wrap:wrap;">

            <form method="POST"
                action="solicitacao_status.php"
                onsubmit="return confirm('Deseja aprovar esta solicitação e enviar para Em andamento?');"
                style="margin-top:38px;">

                <input type="hidden" name="id" value="<?php echo $solicitacao['id']; ?>">
                <input type="hidden" name="acao" value="aprovar">

                <button type="submit" class="btn btn-success">
                    Aprovar solicitação
                </button>
            </form>

            <form method="POST"
                action="solicitacao_status.php"
                onsubmit="return confirm('Deseja reprovar esta solicitação?');"
                style="display:flex; gap:10px; align-items:flex-end; flex-wrap:wrap;">

                <input type="hidden" name="id" value="<?php echo $solicitacao['id']; ?>">
                <input type="hidden" name="acao" value="reprovar">

                <div class="form-group" style="margin-bottom:0;">
                    <label>Motivo da reprovação *</label>
                    <textarea name="motivo_reprovacao"
                            rows="3"
                            required
                            style="width:320px;"
                            placeholder="Explique o motivo da reprovação para o RH ajustar a solicitação."></textarea>
                </div>

                <button type="submit" class="btn btn-danger">
                    Reprovar solicitação
                </button>
            </form>

        </div>
    <?php endif; ?>

    <?php if ($podeFinalizar && $mostrarFinalizacao): ?>
        <hr style="margin:20px 0;">
        <h3>Finalizar solicitação</h3>

        <form method="POST" action="solicitacao_status.php"
                onsubmit="return confirm('Deseja finalizar esta solicitação? Esta ação é irreversível.');">
            <input type="hidden" name="id" value="<?php echo $solicitacao['id']; ?>">
            <input type="hidden" name="acao" value="resolver">

            <div class="form-group">
                <label>Observação final *</label>
                <textarea name="observacao_final" rows="5" required placeholder="Ex.: vaga cancelada, data de início definida, documentação concluída, etc."></textarea>
            </div>

            <div class="row">
                <div class="col-6">
                    <div class="form-group">
                        <label>Data de início</label>
                        <input type="date" name="data_inicio_real">
                    </div>
                </div>

                <div class="col-6">
                    <div class="form-group">
                        <label>Situação da vaga</label>
                        <select name="situacao_vaga">
                            <option value="">Selecione</option>
                            <option value="preenchida">Preenchida</option>
                            <option value="cancelada">Cancelada</option>
                            <option value="ajuste_interno">Ajuste interno</option>
                        </select>
                    </div>
                </div>
            </div>

            <?php if ($solicitacao['tipo'] === 'admissao'): ?>

                <hr style="margin:25px 0;">

                <h3>Dados para cadastro TI / RH</h3>

                <div class="row">
                    <div class="col-6">
                        <div class="form-group">
                            <label>Nome do candidato *</label>
                            <input type="text"
                                name="rh_nome_candidato"
                                required
                                placeholder="Nome completo">
                        </div>
                    </div>

                    <div class="col-6">
                        <div class="form-group">
                            <label>CPF *</label>
                            <input type="text"
                                name="rh_cpf"
                                required
                                placeholder="000.000.000-00">
                        </div>
                    </div>
                </div>

                <div class="row">

                    <div class="col-6">
                        <div class="form-group">
                            <label>Data de nascimento</label>
                            <input type="date" name="rh_data_nascimento">
                        </div>
                    </div>

                    <div class="col-6">
                        <div class="form-group">
                            <label>Setor</label>
                            <input type="text"
                                name="rh_setor"
                                placeholder="Ex.: Administrativo / Gestão">
                        </div>
                    </div>

                </div>

                <div class="row">

                    <div class="col-6">
                        <div class="form-group">
                            <label>Filial</label>
                            <input type="text"
                                name="rh_filial"
                                placeholder="Ex.: MTZ">
                        </div>
                    </div>

                    <div class="col-6">
                        <div class="form-group">
                            <label>Data de admissão</label>
                            <input type="date" name="rh_admissao">
                        </div>
                    </div>

                </div>

                <div class="row">

                    <div class="col-6">
                        <div class="form-group">
                            <label>Horário de trabalho</label>
                            <input type="text"
                                name="rh_horario_trabalho"
                                placeholder="Seg a Sex - 07:00 às 17:00">
                        </div>
                    </div>

                    <div class="col-6">
                        <div class="form-group">
                            <label>Função</label>
                            <input type="text"
                                name="rh_funcao"
                                placeholder="Ex.: Gestor de Relacionamento - Pleno">
                        </div>
                    </div>

                </div>

            <?php endif; ?>

            <button type="submit" class="btn btn-success">Confirmar resolução</button>
        </form>
    <?php elseif ($podeFinalizar): ?>
        <hr style="margin:20px 0;">
        <a href="solicitacao_visualizar.php?id=<?php echo $solicitacao['id']; ?>&finalizar=1" class="btn btn-success">Finalizar solicitação</a>
    <?php endif; ?>

    <?php if ($podeReabrir): ?>
        <hr style="margin:20px 0;">

        <h3>Reabrir solicitação</h3>

        <form method="POST"
            action="solicitacao_status.php"
            onsubmit="return confirm('Deseja reabrir esta solicitação e reenviar para aprovação?');">

            <input type="hidden" name="id" value="<?php echo $solicitacao['id']; ?>">
            <input type="hidden" name="acao" value="reabrir">

            <div class="form-group">
                <label>Observação do ajuste *</label>
                <textarea name="observacao_reabertura"
                        rows="4"
                        required
                        placeholder="Descreva o que foi ajustado antes de reenviar para aprovação."></textarea>
            </div>

            <button type="submit" class="btn btn-success">
                Reabrir e reenviar para aprovação
            </button>
        </form>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>