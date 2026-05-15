<?php

$hash = '';
$senha = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $senha = $_POST['senha'] ?? '';
    if ($senha !== '') {
        $hash = password_hash($senha, PASSWORD_DEFAULT);
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Gerar Hash</title>
</head>
<body>
    <form method="POST">
        <label>Senha:</label>
        <input type="text" name="senha" value="<?php echo htmlspecialchars($senha); ?>" required>
        <button type="submit">Gerar hash</button>
    </form>

    <?php if ($hash): ?>
        <p><strong>Hash gerado:</strong></p>
        <textarea rows="4" cols="100"><?php echo htmlspecialchars($hash); ?></textarea>
    <?php endif; ?>
</body>
</html>