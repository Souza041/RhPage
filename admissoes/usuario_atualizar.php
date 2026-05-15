<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/permissoes.php';
require_once __DIR__ . '/classes/Usuario.php';

if (!isAdmin($_SESSION['usuario_perfil'])) {
    header('Location: dashboard.php?msg=erro_permissao');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: usuarios.php');
    exit;
}

$id = (int)($_POST['id'] ?? 0);
$nome = trim($_POST['nome'] ?? '');
$email = trim($_POST['email'] ?? '');
$senha = trim($_POST['senha'] ?? '');
$perfil = trim($_POST['perfil'] ?? '');
$setor = trim($_POST['setor'] ?? '');
$unidade = trim($_POST['unidade'] ?? '');
$ativo = isset($_POST['ativo']) ? (int)$_POST['ativo'] : 1;

$usuarioModel = new Usuario($pdo);
$usuarioAtual = $usuarioModel->buscarPorId($id);

if (!$usuarioAtual) {
    header('Location: usuarios.php');
    exit;
}

$emailExistente = $usuarioModel->buscarPorEmail($email);
if ($emailExistente && (int)$emailExistente['id'] !== $id) {
    header('Location: usuarios.php?msg=erro_email');
    exit;
}

$usuarioModel->atualizar($id, [
    'nome' => $nome,
    'email' => $email,
    'perfil' => $perfil,
    'setor' => $setor,
    'unidade' => $unidade,
    'ativo' => $ativo
]);

if ($senha !== '') {
    $usuarioModel->atualizarSenha($id, password_hash($senha, PASSWORD_DEFAULT));
}

header('Location: usuarios.php?msg=atualizado');
exit;