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

$sqlCards = "SELECT
                COUNT(*) AS total_periodo,
                SUM(CASE WHEN s.status = 'aberto' THEN 1 ELSE 0 END) AS aberto,
                SUM(CASE WHEN s.status = 'em_andamento' THEN 1 ELSE 0 END) AS andamento,
                SUM(CASE WHEN s.status = 'resolvido' THEN 1 ELSE 0 END) AS resolvido
            FROM solicitacoes s
            INNER JOIN usuarios u ON u.id = s.id_solicitante
            WHERE $whereSql";

$stmtCards = $pdo->prepare($sqlCards);
foreach ($params as $chave => $valor) {
    if ($chave === ':periodo') {
        $stmtCards->bindValue($chave, (int)$valor, PDO::PARAM_INT);
    } else {
        $stmtCards->bindValue($chave, $valor, PDO::PARAM_STR);
    }
}
$stmtCards->execute();
$cards = $stmtCards->fetch();

$sqlTempo = "SELECT
                AVG(TIMESTAMPDIFF(HOUR, s.data_solicitacao, s.data_resolucao)) AS tempo_medio_horas
             FROM solicitacoes s
             INNER JOIN usuarios u ON u.id = s.id_solicitante
             WHERE $whereSql
             AND s.status = 'resolvido'
             AND s.data_resolucao IS NOT NULL";

$stmtTempo = $pdo->prepare($sqlTempo);
foreach ($params as $chave => $valor) {
    if ($chave === ':periodo') {
        $stmtTempo->bindValue($chave, (int)$valor, PDO::PARAM_INT);
    } else {
        $stmtTempo->bindValue($chave, $valor, PDO::PARAM_STR);
    }
}
$stmtTempo->execute();
$tempo = $stmtTempo->fetch();

$sqlSetores = "SELECT
                    COALESCE(u.setor, 'Não informado') AS setor,
                    COUNT(*) AS total
               FROM solicitacoes s
               INNER JOIN usuarios u ON u.id = s.id_solicitante
               WHERE $whereSql
               GROUP BY u.setor
               ORDER BY total DESC, setor ASC";

$setores = [];
try {
    $stmtSetores = $pdo->prepare($sqlSetores);
    foreach ($params as $chave => $valor) {
        if ($chave === ':periodo') {
            $stmtSetores->bindValue($chave, (int)$valor, PDO::PARAM_INT);
        } else {
            $stmtSetores->bindValue($chave, $valor, PDO::PARAM_STR);
        }
    }
    $stmtSetores->execute();
    $setores = $stmtSetores->fetchAll();
} catch (Exception $e) {
    $setores = [];
}

echo json_encode([
    'cards' => [
        'total_periodo' => (int)($cards['total_periodo'] ?? 0),
        'aberto' => (int)($cards['aberto'] ?? 0),
        'andamento' => (int)($cards['andamento'] ?? 0),
        'resolvido' => (int)($cards['resolvido'] ?? 0),
    ],
    'tempo_medio_horas' => $tempo['tempo_medio_horas'] !== null ? round($tempo['tempo_medio_horas'], 1) : 0,
    'setores' => $setores
]);