<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/permissoes.php';
require_once __DIR__ . '/classes/Usuario.php';

if (!isAdmin($_SESSION['usuario_perfil'])) {
    header('Location: dashboard.php?msg=erro_permissao');
    exit;
}

$usuarioModel = new Usuario($pdo);
$usuarios = $usuarioModel->listarTodos();
$mensagem = $_GET['msg'] ?? '';

include __DIR__ . '/includes/header.php';
?>

<?php if ($mensagem === 'criado'): ?>
    <div class="alert alert-success">Usuário criado com sucesso.</div>
<?php elseif ($mensagem === 'atualizado'): ?>
    <div class="alert alert-success">Usuário atualizado com sucesso.</div>
<?php elseif ($mensagem === 'status'): ?>
    <div class="alert alert-success">Status do usuário alterado com sucesso.</div>
<?php elseif ($mensagem === 'erro_email'): ?>
    <div class="alert alert-error">Já existe um usuário com esse e-mail.</div>
<?php endif; ?>

<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
    <h2 style="margin:0;">Usuários</h2>
    <a href="usuario_novo.php" class="btn">+ Novo usuário</a>
</div>

<div class="card">
    <table style="width:100%; border-collapse:collapse;">
        <thead>
            <tr>
                <th style="text-align:left; padding:10px;">Nome</th>
                <th style="text-align:left; padding:10px;">E-mail</th>
                <th style="text-align:left; padding:10px;">Perfil</th>
                <th style="text-align:left; padding:10px;">Setor</th>
                <th style="text-align:left; padding:10px;">Unidade</th>
                <th style="text-align:left; padding:10px;">Status</th>
                <th style="text-align:left; padding:10px;">Ações</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($usuarios as $usuario): ?>
                <tr>
                    <td style="padding:10px;"><?php echo htmlspecialchars($usuario['nome']); ?></td>
                    <td style="padding:10px;"><?php echo htmlspecialchars($usuario['email']); ?></td>
                    <td style="padding:10px;"><?php echo htmlspecialchars($usuario['perfil']); ?></td>
                    <td style="padding:10px;"><?php echo htmlspecialchars($usuario['setor'] ?? '-'); ?></td>
                    <td style="padding:10px;"><?php echo htmlspecialchars($usuario['unidade'] ?? '-'); ?></td>
                    <td style="padding:10px;"><?php echo (int)$usuario['ativo'] === 1 ? 'Ativo' : 'Inativo'; ?></td>
                    <td style="padding:10px;">
                        <a class="btn" href="usuario_editar.php?id=<?php echo $usuario['id']; ?>">Editar</a>
                        <a class="btn btn-danger"
                           href="usuario_toggle.php?id=<?php echo $usuario['id']; ?>"
                           onclick="return confirm('Deseja alterar o status deste usuário?');">
                           <?php echo (int)$usuario['ativo'] === 1 ? 'Desativar' : 'Ativar'; ?>
                        </a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>