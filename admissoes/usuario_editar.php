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

$usuarioModel = new Usuario($pdo);
$usuario = $usuarioModel->buscarPorId($id);

if (!$usuario) {
    header('Location: usuarios.php');
    exit;
}

include __DIR__ . '/includes/header.php';
?>

<div class="card">
    <h2>Editar usuário</h2>

    <form method="POST" action="usuario_atualizar.php">
        <input type="hidden" name="id" value="<?php echo $usuario['id']; ?>">

        <div class="form-group">
            <label>Nome</label>
            <input type="text" name="nome" value="<?php echo htmlspecialchars($usuario['nome']); ?>" required>
        </div>

        <div class="form-group">
            <label>E-mail</label>
            <input type="email" name="email" value="<?php echo htmlspecialchars($usuario['email']); ?>" required>
        </div>

        <div class="form-group">
            <label>Nova senha (deixe em branco para manter)</label>
            <input type="password" name="senha">
        </div>

        <div class="form-group">
            <label>Perfil</label>
            <select name="perfil" required>
                <?php
                $perfis = ['admin', 'diretor', 'rh', 'dho', 'gerente', 'supervisor'];
                foreach ($perfis as $perfil) {
                    $selected = $usuario['perfil'] === $perfil ? 'selected' : '';
                    echo '<option value="' . $perfil . '" ' . $selected . '>' . ucfirst($perfil) . '</option>';
                }
                ?>
            </select>
        </div>

        <div class="row">
            <div class="col-6">
                <div class="form-group">
                    <label>Setor</label>
                    <input type="text" name="setor" value="<?php echo htmlspecialchars($usuario['setor'] ?? ''); ?>">
                </div>
            </div>

            <div class="col-6">
                <div class="form-group">
                    <label>Unidade</label>
                    <input type="text" name="unidade" value="<?php echo htmlspecialchars($usuario['unidade'] ?? ''); ?>">
                </div>
            </div>
        </div>

        <div class="form-group">
            <label>Status</label>
            <select name="ativo">
                <option value="1" <?php echo (int)$usuario['ativo'] === 1 ? 'selected' : ''; ?>>Ativo</option>
                <option value="0" <?php echo (int)$usuario['ativo'] === 0 ? 'selected' : ''; ?>>Inativo</option>
            </select>
        </div>

        <button type="submit" class="btn btn-success">Atualizar</button>
        <a href="usuarios.php" class="btn btn-danger">Cancelar</a>
    </form>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>