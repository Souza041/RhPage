<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/permissoes.php';
require_once __DIR__ . '/classes/Usuario.php';

if ($_SESSION['usuario_perfil'] !== 'admin') {
    header('Location: dashboard.php?msg=erro_permissao');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: usuarios.php');
    exit;
}

$nome = trim($_POST['nome'] ?? '');
$email = trim($_POST['email'] ?? '');
$senha = trim($_POST['senha'] ?? '');
$perfil = trim($_POST['perfil'] ?? '');
$setor = trim($_POST['setor'] ?? '');
$unidade = trim($_POST['unidade'] ?? '');

if ($nome === '' || $email === '' || $senha === '' || $perfil === '') {
    die('Campos obrigatórios não preenchidos.');
}

$perfisPermitidos = ['admin', 'diretor', 'rh', 'dho', 'gerente', 'supervisor'];

if (!in_array($perfil, $perfisPermitidos, true)) {
    die('Perfil inválido.');
}

$usuarioModel = new Usuario($pdo);

if ($usuarioModel->buscarPorEmail($email)) {
    header('Location: usuario_novo.php?msg=erro_email');
    exit;
}

$usuarioModel->criar([
    'nome' => $nome,
    'email' => $email,
    'senha' => password_hash($senha, PASSWORD_DEFAULT),
    'perfil' => $perfil,
    'setor' => $setor,
    'unidade' => $unidade,
    'ativo' => 1
]);

header('Location: usuarios.php?msg=criado');
exit;