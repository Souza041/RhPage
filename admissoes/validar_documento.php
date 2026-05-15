<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';

$codigo = trim($_GET['codigo'] ?? $_POST['codigo'] ?? '');
$documento = null;
$erro = '';

if ($codigo !== '') {
    $sql = "SELECT
                sd.*,
                s.id AS solicitacao_id,
                s.tipo,
                s.status,
                s.nome_candidato,
                s.setor_solicitante,
                s.unidade_solicitante,
                u.nome AS solicitante_nome,
                u.email AS solicitante_email
            FROM solicitacoes_documentos sd
            INNER JOIN solicitacoes s ON s.id = sd.id_solicitacao
            INNER JOIN usuarios u ON u.id = s.id_solicitante
            WHERE sd.codigo_verificacao = :codigo
            LIMIT 1";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':codigo' => $codigo
    ]);

    $documento = $stmt->fetch();

    if (!$documento) {
        $erro = 'Nenhum documento encontrado para o código informado.';
    }
}

function traduzTipoValidacao($tipo)
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

include __DIR__ . '/includes/header.php';
?>

<div class="card" style="max-width: 900px; margin: 0 auto;">
    <h2>Validar Documento</h2>
    <p style="color:#666;">
        Informe o código de verificação para consultar a autenticidade do documento gerado pelo sistema.
    </p>

    <form method="GET" action="validar_documento.php" style="margin-bottom:20px;">
        <div class="form-group">
            <label>Código de verificação</label>
            <input
                type="text"
                name="codigo"
                value="<?php echo htmlspecialchars($codigo); ?>"
                placeholder="Ex.: RH-20260423-14-AB12CD"
                required
            >
        </div>

        <button type="submit" class="btn btn-success">Validar</button>
        <a href="validar_documento.php" class="btn btn-danger">Limpar</a>
    </form>

    <?php if ($erro !== ''): ?>
        <div class="alert alert-error">
            <?php echo htmlspecialchars($erro); ?>
        </div>
    <?php endif; ?>

    <?php if ($documento): ?>
        <div class="alert alert-success">
            Documento válido e localizado com sucesso.
        </div>

        <div style="margin-top:20px;">
            <h3>Dados do documento</h3>

            <table style="width:100%; border-collapse:collapse;">
                <tr>
                    <td style="padding:10px; border-bottom:1px solid #eee;"><strong>Código de verificação</strong></td>
                    <td style="padding:10px; border-bottom:1px solid #eee;"><?php echo htmlspecialchars($documento['codigo_verificacao']); ?></td>
                </tr>
                <tr>
                    <td style="padding:10px; border-bottom:1px solid #eee;"><strong>Nome do arquivo</strong></td>
                    <td style="padding:10px; border-bottom:1px solid #eee;"><?php echo htmlspecialchars($documento['nome_arquivo']); ?></td>
                </tr>
                <tr>
                    <td style="padding:10px; border-bottom:1px solid #eee;"><strong>Hash do documento</strong></td>
                    <td style="padding:10px; border-bottom:1px solid #eee; word-break:break-all;"><?php echo htmlspecialchars($documento['hash_documento']); ?></td>
                </tr>
                <tr>
                    <td style="padding:10px; border-bottom:1px solid #eee;"><strong>Data de geração</strong></td>
                    <td style="padding:10px; border-bottom:1px solid #eee;"><?php echo date('d/m/Y H:i', strtotime($documento['data_geracao'])); ?></td>
                </tr>
            </table>
        </div>

        <div style="margin-top:25px;">
            <h3>Dados da solicitação</h3>

            <table style="width:100%; border-collapse:collapse;">
                <tr>
                    <td style="padding:10px; border-bottom:1px solid #eee;"><strong>Número da solicitação</strong></td>
                    <td style="padding:10px; border-bottom:1px solid #eee;">#<?php echo (int)$documento['solicitacao_id']; ?></td>
                </tr>
                <tr>
                    <td style="padding:10px; border-bottom:1px solid #eee;"><strong>Tipo</strong></td>
                    <td style="padding:10px; border-bottom:1px solid #eee;"><?php echo htmlspecialchars(traduzTipoValidacao($documento['tipo'])); ?></td>
                </tr>
                <tr>
                    <td style="padding:10px; border-bottom:1px solid #eee;"><strong>Status</strong></td>
                    <td style="padding:10px; border-bottom:1px solid #eee;"><?php echo htmlspecialchars($documento['status']); ?></td>
                </tr>
                <tr>
                    <td style="padding:10px; border-bottom:1px solid #eee;"><strong>Solicitante</strong></td>
                    <td style="padding:10px; border-bottom:1px solid #eee;"><?php echo htmlspecialchars($documento['solicitante_nome']); ?></td>
                </tr>
                <tr>
                    <td style="padding:10px; border-bottom:1px solid #eee;"><strong>E-mail do solicitante</strong></td>
                    <td style="padding:10px; border-bottom:1px solid #eee;"><?php echo htmlspecialchars($documento['solicitante_email']); ?></td>
                </tr>
                <tr>
                    <td style="padding:10px; border-bottom:1px solid #eee;"><strong>Nome do candidato / funcionário</strong></td>
                    <td style="padding:10px; border-bottom:1px solid #eee;"><?php echo htmlspecialchars($documento['nome_candidato']); ?></td>
                </tr>
                <tr>
                    <td style="padding:10px; border-bottom:1px solid #eee;"><strong>Setor solicitante</strong></td>
                    <td style="padding:10px; border-bottom:1px solid #eee;"><?php echo htmlspecialchars($documento['setor_solicitante'] ?? '-'); ?></td>
                </tr>
                <tr>
                    <td style="padding:10px; border-bottom:1px solid #eee;"><strong>Unidade / Filial</strong></td>
                    <td style="padding:10px; border-bottom:1px solid #eee;"><?php echo htmlspecialchars($documento['unidade_solicitante'] ?? '-'); ?></td>
                </tr>
            </table>
        </div>

        <div style="margin-top:20px;">
            <a class="btn" href="<?php echo htmlspecialchars($documento['caminho_arquivo']); ?>" target="_blank">
                Visualizar / Baixar PDF
            </a>
        </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>