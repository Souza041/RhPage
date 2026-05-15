<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: minha_assinatura.php');
    exit;
}

$idUsuario = $_SESSION['usuario_id'];
$nomeExibicao = trim($_POST['nome_exibicao'] ?? '');
$cargoExibicao = trim($_POST['cargo_exibicao'] ?? '');
$assinaturaBase64 = $_POST['assinatura_base64'] ?? '';

if ($nomeExibicao === '' || $cargoExibicao === '' || $assinaturaBase64 === '') {
    die('Dados obrigatórios não preenchidos.');
}

if (strpos($assinaturaBase64, 'data:image/png;base64,') !== 0) {
    die('Formato de assinatura inválido.');
}

$dir = __DIR__ . '/uploads/assinaturas/';

if (!is_dir($dir)) {
    mkdir($dir, 0775, true);
}

$stmt = $pdo->prepare("SELECT assinatura_imagem FROM usuarios WHERE id = :id LIMIT 1");
$stmt->execute([':id' => $idUsuario]);
$assinaturaAntiga = $stmt->fetchColumn();

if (!empty($assinaturaAntiga)) {
    $caminhoAntigo = __DIR__ . '/' . ltrim($assinaturaAntiga, '/');
    if (is_file($caminhoAntigo)) {
        unlink($caminhoAntigo);
    }
}

$base64Limpo = str_replace('data:image/png;base64,', '', $assinaturaBase64);
$base64Limpo = str_replace(' ', '+', $base64Limpo);
$imagemBinaria = base64_decode($base64Limpo);

if ($imagemBinaria === false) {
    die('Erro ao processar assinatura.');
}

$nomeArquivo = 'assinatura_usuario_' . $idUsuario . '_' . date('YmdHis') . '.png';
$caminhoAbsoluto = $dir . $nomeArquivo;
$caminhoRelativo = 'uploads/assinaturas/' . $nomeArquivo;

file_put_contents($caminhoAbsoluto, $imagemBinaria);

if (!is_file($caminhoAbsoluto)) {
    die('Falha ao salvar assinatura.');
}

$sql = "UPDATE usuarios
        SET assinatura_imagem = :assinatura_imagem,
            assinatura_nome_exibicao = :nome_exibicao,
            assinatura_cargo_exibicao = :cargo_exibicao
        WHERE id = :id";

$stmt = $pdo->prepare($sql);
$stmt->execute([
    ':assinatura_imagem' => $caminhoRelativo,
    ':nome_exibicao' => $nomeExibicao,
    ':cargo_exibicao' => $cargoExibicao,
    ':id' => $idUsuario
]);

header('Location: minha_assinatura.php?msg=salvo');
exit;