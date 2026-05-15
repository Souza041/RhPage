<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/permissoes.php';
require_once __DIR__ . '/config/config.php';

if (!isAdmin($_SESSION['usuario_perfil'])) {
    header('Location: dashboard.php?msg=erro_permissao');
    exit;
}

include __DIR__ . '/includes/header.php';
?>


<div class="card">
    <h2>Novo usuário</h2>

    <form method="POST" action="usuario_salvar.php">
        <div class="form-group">
            <label>Nome</label>
            <input type="text" name="nome" required>
        </div>

        <div class="form-group">
            <label>E-mail</label>
            <input type="email" name="email" required>
        </div>

        <div class="form-group">
            <label>Senha</label>
            <input type="password" name="senha" required>
        </div>

        <div class="form-group">
            <label>Perfil</label>
            <select name="perfil" required>
                <option value="admin">Admin</option>
                <option value="diretor">Diretor</option>
                <option value="rh">RH</option>
                <option value="dho">DHO</option>
                <option value="gerente">Gerente</option>
                <option value="supervisor">Supervisor</option>
            </select>
        </div>

        <div class="row">
            <div class="col-6">
                <div class="form-group">
                    <label>Setor</label>
                    <input type="text" name="setor">
                </div>
            </div>

            <div class="col-6">
                <div class="form-group">
                    <label>Unidade</label>
                    <input type="text" name="unidade">
                </div>
            </div>
        </div>

        <button type="submit" class="btn btn-success">Salvar</button>
        <a href="usuarios.php" class="btn btn-danger">Cancelar</a>
    </form>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>