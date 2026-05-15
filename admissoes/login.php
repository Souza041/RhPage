<?php
session_start();
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';

if (isset($_SESSION['usuario_id'])) {
    header('Location: dashboard.php');
    exit;
}

$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $senha = trim($_POST['senha'] ?? '');

    if ($email === '' || $senha === '') {
        $erro = 'Preencha e-mail e senha.';
    } else {
        $sql = "SELECT id, nome, email, senha, perfil, ativo
                FROM usuarios
                WHERE email = :email
                LIMIT 1";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':email' => $email]);
        $usuario = $stmt->fetch();

        if ($usuario && (int)$usuario['ativo'] === 1 && password_verify($senha, $usuario['senha'])) {
            $_SESSION['usuario_id'] = $usuario['id'];
            $_SESSION['usuario_nome'] = $usuario['nome'];
            $_SESSION['usuario_email'] = $usuario['email'];
            $_SESSION['usuario_perfil'] = $usuario['perfil'];

            header('Location: dashboard.php');
            exit;
        } else {
            $erro = 'Usuário ou senha inválidos.';
        }
    }
}
?>
<?php include __DIR__ . '/includes/header.php'; ?>

<div class="card" style="max-width: 420px; margin: 60px auto;">
    <h2>Login</h2>

    <?php if ($erro): ?>
        <div class="alert alert-error"><?php echo htmlspecialchars($erro); ?></div>
    <?php endif; ?>

    <form method="POST">
        <div class="form-group">
            <label>E-mail</label>
            <input type="email" name="email" required>
        </div>

        <div class="form-group">
            <label>Senha</label>
            <input type="password" name="senha" required>
        </div>

        <button type="submit" class="btn">Entrar</button>
    </form>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>