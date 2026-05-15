<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title><?php echo APP_NAME; ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="assets/css/style.css">
    
</head>
<body>

<?php if (isset($_SESSION['usuario_id'])): ?>
<div class="topbar">
    <div>
        <strong><?php echo APP_NAME; ?></strong>
    </div>
    <div>
        Olá, <?php echo htmlspecialchars($_SESSION['usuario_nome']); ?> |
        Perfil: <?php echo htmlspecialchars($_SESSION['usuario_perfil']); ?>
        <a href="dashboard.php">Kanban</a>
        <a href="solicitacao_nova.php">Nova Solicitação</a>
        <?php if (in_array($_SESSION['usuario_perfil'], ['diretor', 'rh', 'dho', 'admin'])): ?>
            <a href="metricas.php">Métricas</a>
        <?php endif; ?>
        <a href="logout.php">Sair</a>
    </div>
</div>
<?php endif; ?>

<div class="container">