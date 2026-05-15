<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';

if (!in_array($_SESSION['usuario_perfil'], ['diretor', 'rh', 'admin'], true)) {
    header('Location: dashboard.php?msg=erro_permissao');
    exit;
}

$sql = "UPDATE solicitacoes
        SET visivel_kanban = 0
        WHERE status = 'resolvido'
          AND visivel_kanban = 1";

$stmt = $pdo->prepare($sql);
$stmt->execute();

header('Location: dashboard.php?msg=finalizadas_limpas');
exit;