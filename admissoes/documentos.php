<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';

if (!in_array($_SESSION['usuario_perfil'], ['admin', 'diretor', 'rh', 'dho'], true)) {
    header('Location: dashboard.php?msg=erro_permissao');
    exit;
}

$mensagem = $_GET['msg'] ?? '';

$filtroCodigo = trim($_GET['codigo'] ?? '');
$filtroTipo = trim($_GET['tipo'] ?? '');
$filtroSolicitante = trim($_GET['solicitante'] ?? '');
$filtroNome = trim($_GET['nome'] ?? '');
$filtroDataInicio = trim($_GET['data_inicio'] ?? '');
$filtroDataFim = trim($_GET['data_fim'] ?? '');

$where = [];
$params = [];

$where[] = "sd.tipo_documento = 'pdf_final'";

if ($filtroCodigo !== '') {
    $where[] = "sd.codigo_verificacao LIKE :codigo";
    $params[':codigo'] = '%' . $filtroCodigo . '%';
}

if ($filtroTipo !== '') {
    $where[] = "s.tipo = :tipo";
    $params[':tipo'] = $filtroTipo;
}

if ($filtroSolicitante !== '') {
    $where[] = "u.nome LIKE :solicitante";
    $params[':solicitante'] = '%' . $filtroSolicitante . '%';
}

if ($filtroNome !== '') {
    $where[] = "s.nome_candidato LIKE :nome";
    $params[':nome'] = '%' . $filtroNome . '%';
}

if ($filtroDataInicio !== '') {
    $where[] = "DATE(sd.data_geracao) >= :data_inicio";
    $params[':data_inicio'] = $filtroDataInicio;
}

if ($filtroDataFim !== '') {
    $where[] = "DATE(sd.data_geracao) <= :data_fim";
    $params[':data_fim'] = $filtroDataFim;
}

$sql = "SELECT
            sd.id,
            sd.id_solicitacao,
            sd.tipo_documento,
            sd.codigo_verificacao,
            sd.hash_documento,
            sd.nome_arquivo,
            sd.caminho_arquivo,
            sd.tamanho_arquivo,
            sd.data_geracao,
            s.tipo,
            s.status,
            s.nome_candidato,
            s.setor_solicitante,
            s.unidade_solicitante,
            u.nome AS solicitante_nome,
            u.email AS solicitante_email
        FROM solicitacoes_documentos sd
        INNER JOIN solicitacoes s ON s.id = sd.id_solicitacao
        INNER JOIN usuarios u ON u.id = s.id_solicitante";

if (!empty($where)) {
    $sql .= " WHERE " . implode(' AND ', $where);
}

$sql .= " ORDER BY sd.data_geracao DESC, sd.id DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$documentos = $stmt->fetchAll();

function traduzTipoDocumentoRh($tipo)
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

function formatarTamanhoArquivo($bytes)
{
    $bytes = (int)$bytes;

    if ($bytes <= 0) {
        return '-';
    }

    if ($bytes >= 1048576) {
        return number_format($bytes / 1048576, 2, ',', '.') . ' MB';
    }

    if ($bytes >= 1024) {
        return number_format($bytes / 1024, 2, ',', '.') . ' KB';
    }

    return $bytes . ' B';
}

include __DIR__ . '/includes/header.php';
?>

<?php if ($mensagem === 'erro_permissao'): ?>
    <div class="alert alert-error">Você não tem permissão para acessar esta página.</div>
<?php endif; ?>

<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
    <h2 style="margin:0;">Documentos RH</h2>
    <div>
        <strong>Total encontrado:</strong> <?php echo count($documentos); ?>
        <a href="validar_documento.php" class="btn" style="margin-left:10px;">Validar documento</a>
        <a href="dashboard.php" class="btn" style="margin-left:10px;">Voltar ao Kanban</a>
    </div>
</div>

<div class="card" style="margin-bottom:20px;">
    <form method="GET" action="documentos.php">
        <div class="row">
            <div class="col-6">
                <div class="form-group">
                    <label>Código de verificação</label>
                    <input type="text" name="codigo" value="<?php echo htmlspecialchars($filtroCodigo); ?>">
                </div>
            </div>

            <div class="col-6">
                <div class="form-group">
                    <label>Tipo</label>
                    <select name="tipo">
                        <option value="">Todos</option>
                        <option value="admissao" <?php echo $filtroTipo === 'admissao' ? 'selected' : ''; ?>>Admissão</option>
                        <option value="demissao" <?php echo $filtroTipo === 'demissao' ? 'selected' : ''; ?>>Demissão</option>
                        <option value="mudanca_cargo_salario" <?php echo $filtroTipo === 'mudanca_cargo_salario' ? 'selected' : ''; ?>>Mudança de Cargo/Salário</option>
                    </select>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-6">
                <div class="form-group">
                    <label>Solicitante</label>
                    <input type="text" name="solicitante" value="<?php echo htmlspecialchars($filtroSolicitante); ?>">
                </div>
            </div>

            <div class="col-6">
                <div class="form-group">
                    <label>Nome do candidato / funcionário</label>
                    <input type="text" name="nome" value="<?php echo htmlspecialchars($filtroNome); ?>">
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-6">
                <div class="form-group">
                    <label>Data inicial</label>
                    <input type="date" name="data_inicio" value="<?php echo htmlspecialchars($filtroDataInicio); ?>">
                </div>
            </div>

            <div class="col-6">
                <div class="form-group">
                    <label>Data final</label>
                    <input type="date" name="data_fim" value="<?php echo htmlspecialchars($filtroDataFim); ?>">
                </div>
            </div>
        </div>

        <button type="submit" class="btn">Filtrar</button>
        <a href="documentos.php" class="btn btn-danger">Limpar</a>
    </form>
</div>

<div class="card">
    <?php if (empty($documentos)): ?>
        <p style="margin:0;">Nenhum documento encontrado.</p>
    <?php else: ?>
        <div style="overflow-x:auto;">
            <table style="width:100%; border-collapse:collapse;">
                <thead>
                    <tr>
                        <th style="text-align:left; padding:10px; border-bottom:1px solid #ddd;">Solicitação</th>
                        <th style="text-align:left; padding:10px; border-bottom:1px solid #ddd;">Tipo</th>
                        <th style="text-align:left; padding:10px; border-bottom:1px solid #ddd;">Solicitante</th>
                        <th style="text-align:left; padding:10px; border-bottom:1px solid #ddd;">Nome</th>
                        <th style="text-align:left; padding:10px; border-bottom:1px solid #ddd;">Código</th>
                        <th style="text-align:left; padding:10px; border-bottom:1px solid #ddd;">Data</th>
                        <th style="text-align:left; padding:10px; border-bottom:1px solid #ddd;">Tamanho</th>
                        <th style="text-align:left; padding:10px; border-bottom:1px solid #ddd;">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($documentos as $documento): ?>
                        <tr>
                            <td style="padding:10px; border-bottom:1px solid #eee;">
                                #<?php echo (int)$documento['id_solicitacao']; ?><br>
                                <small><?php echo htmlspecialchars($documento['status']); ?></small>
                            </td>

                            <td style="padding:10px; border-bottom:1px solid #eee;">
                                <?php echo htmlspecialchars(traduzTipoDocumentoRh($documento['tipo'])); ?>
                            </td>

                            <td style="padding:10px; border-bottom:1px solid #eee;">
                                <?php echo htmlspecialchars($documento['solicitante_nome']); ?><br>
                                <small><?php echo htmlspecialchars($documento['solicitante_email']); ?></small>
                            </td>

                            <td style="padding:10px; border-bottom:1px solid #eee;">
                                <?php echo htmlspecialchars($documento['nome_candidato']); ?><br>
                                <small>
                                    <?php echo htmlspecialchars($documento['setor_solicitante'] ?? '-'); ?>
                                    /
                                    <?php echo htmlspecialchars($documento['unidade_solicitante'] ?? '-'); ?>
                                </small>
                            </td>

                            <td style="padding:10px; border-bottom:1px solid #eee; word-break:break-word;">
                                <?php echo htmlspecialchars($documento['codigo_verificacao']); ?>
                            </td>

                            <td style="padding:10px; border-bottom:1px solid #eee;">
                                <?php echo date('d/m/Y H:i', strtotime($documento['data_geracao'])); ?>
                            </td>

                            <td style="padding:10px; border-bottom:1px solid #eee;">
                                <?php echo htmlspecialchars(formatarTamanhoArquivo($documento['tamanho_arquivo'])); ?>
                            </td>

                            <td style="padding:10px; border-bottom:1px solid #eee; white-space:nowrap;">
                                <a class="btn" href="solicitacao_visualizar.php?id=<?php echo (int)$documento['id_solicitacao']; ?>">Ver solicitação</a>
                                <a class="btn" href="<?php echo htmlspecialchars($documento['caminho_arquivo']); ?>" target="_blank">PDF</a>
                                <a class="btn" href="validar_documento.php?codigo=<?php echo urlencode($documento['codigo_verificacao']); ?>">Validar</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>