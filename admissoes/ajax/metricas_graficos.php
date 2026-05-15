<?php
require_once __DIR__ . '/../includes/auth.php';

if (!in_array($_SESSION['usuario_perfil'], ['diretor', 'rh', 'dho', 'admin'], true)) {
    http_response_code(403);
    echo json_encode([
        'erro' => 'Acesso negado.'
    ]);
    exit;
}

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json; charset=utf-8');

$periodo = isset($_GET['periodo']) ? (int) $_GET['periodo'] : 30;
$solicitante = trim($_GET['solicitante'] ?? '');
$nomeCandidato = trim($_GET['nome_candidato'] ?? '');
$status = trim($_GET['status'] ?? '');
$tipo = trim($_GET['tipo'] ?? '');

$where = [];
$params = [];

$where[] = "s.data_solicitacao >= DATE_SUB(NOW(), INTERVAL :periodo DAY)";
$params[':periodo'] = $periodo;

if ($solicitante !== '') {
    $where[] = "u.nome LIKE :solicitante";
    $params[':solicitante'] = '%' . $solicitante . '%';
}

if ($nomeCandidato !== '') {
    $where[] = "s.nome_candidato LIKE :nome_candidato";
    $params[':nome_candidato'] = '%' . $nomeCandidato . '%';
}

if ($status !== '') {
    $where[] = "s.status = :status";
    $params[':status'] = $status;
}

if ($tipo !== '') {
    $where[] = "s.tipo = :tipo";
    $params[':tipo'] = $tipo;
}

$whereSql = implode(' AND ', $where);

$sqlDia = "SELECT
                DATE(s.data_solicitacao) AS dia,
                COUNT(*) AS total
           FROM solicitacoes s
           INNER JOIN usuarios u ON u.id = s.id_solicitante
           WHERE $whereSql
           GROUP BY DATE(s.data_solicitacao)
           ORDER BY dia ASC";

$stmtDia = $pdo->prepare($sqlDia);
foreach ($params as $chave => $valor) {
    if ($chave === ':periodo') {
        $stmtDia->bindValue($chave, (int)$valor, PDO::PARAM_INT);
    } else {
        $stmtDia->bindValue($chave, $valor, PDO::PARAM_STR);
    }
}
$stmtDia->execute();
$dadosDia = $stmtDia->fetchAll();

$sqlTipos = "SELECT
                s.tipo,
                COUNT(*) AS total
             FROM solicitacoes s
             INNER JOIN usuarios u ON u.id = s.id_solicitante
             WHERE $whereSql
             GROUP BY s.tipo
             ORDER BY total DESC";

$stmtTipos = $pdo->prepare($sqlTipos);
foreach ($params as $chave => $valor) {
    if ($chave === ':periodo') {
        $stmtTipos->bindValue($chave, (int)$valor, PDO::PARAM_INT);
    } else {
        $stmtTipos->bindValue($chave, $valor, PDO::PARAM_STR);
    }
}
$stmtTipos->execute();
$dadosTipos = $stmtTipos->fetchAll();

$sqlSla = "SELECT
                SUM(CASE WHEN s.status = 'resolvido' AND TIMESTAMPDIFF(HOUR, s.data_solicitacao, s.data_resolucao) <= 24 THEN 1 ELSE 0 END) AS dentro_prazo,
                SUM(CASE WHEN s.status = 'em_andamento' AND TIMESTAMPDIFF(HOUR, s.data_solicitacao, NOW()) BETWEEN 20 AND 24 THEN 1 ELSE 0 END) AS em_vencimento,
                SUM(CASE WHEN (s.status = 'em_andamento' AND TIMESTAMPDIFF(HOUR, s.data_solicitacao, NOW()) > 24)
                          OR (s.status = 'resolvido' AND TIMESTAMPDIFF(HOUR, s.data_solicitacao, s.data_resolucao) > 24)
                         THEN 1 ELSE 0 END) AS vencidos
           FROM solicitacoes s
           INNER JOIN usuarios u ON u.id = s.id_solicitante
           WHERE $whereSql";

$stmtSla = $pdo->prepare($sqlSla);
foreach ($params as $chave => $valor) {
    if ($chave === ':periodo') {
        $stmtSla->bindValue($chave, (int)$valor, PDO::PARAM_INT);
    } else {
        $stmtSla->bindValue($chave, $valor, PDO::PARAM_STR);
    }
}
$stmtSla->execute();
$dadosSla = $stmtSla->fetch();

function traduzTipoMetrica($tipo)
{
    switch ($tipo) {
        case 'admissao':
            return 'Admissão';
        case 'demissao':
            return 'Demissão';
        case 'mudanca_cargo_salario':
            return 'Mudança';
        default:
            return $tipo;
    }
}

$tiposFormatados = [];
foreach ($dadosTipos as $linha) {
    $tiposFormatados[] = [
        'label' => traduzTipoMetrica($linha['tipo']),
        'total' => (int)$linha['total']
    ];
}

echo json_encode([
    'por_dia' => $dadosDia,
    'por_tipo' => $tiposFormatados,
    'sla' => [
        'dentro_prazo' => (int)($dadosSla['dentro_prazo'] ?? 0),
        'em_vencimento' => (int)($dadosSla['em_vencimento'] ?? 0),
        'vencidos' => (int)($dadosSla['vencidos'] ?? 0)
    ]
]);