<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/permissoes.php';
require_once __DIR__ . '/classes/Usuario.php';

if (!isAdmin($_SESSION['usuario_perfil'])) {
    header('Location: dashboard.php?msg=erro_permissao');
    exit;
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    header('Location: usuarios.php');
    exit;
}

$usuarioModel = new Usuario($pdo);
$usuarioModel->alternarStatus($id);

header('Location: usuarios.php?msg=status');
exit;