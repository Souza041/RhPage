<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';

$idUsuario = $_SESSION['usuario_id'];

$stmt = $pdo->prepare("SELECT nome, email, perfil, assinatura_imagem, assinatura_nome_exibicao, assinatura_cargo_exibicao FROM usuarios WHERE id = :id LIMIT 1");
$stmt->execute([':id' => $idUsuario]);
$usuario = $stmt->fetch();

include __DIR__ . '/includes/header.php';
?>

<div class="card">
    <h2>Minha Assinatura</h2>

    <p>Cadastre ou atualize sua assinatura. Ao salvar uma nova, a anterior será substituída.</p>

    <?php if (!empty($_GET['msg']) && $_GET['msg'] === 'salvo'): ?>
        <div class="alert alert-success">Assinatura salva com sucesso.</div>
    <?php endif; ?>

    <?php if (!empty($usuario['assinatura_imagem'])): ?>
        <p><strong>Assinatura atual:</strong></p>
        <div style="background:#f8f9fa; padding:15px; border-radius:8px; margin-bottom:20px;">
            <img src="<?php echo htmlspecialchars($usuario['assinatura_imagem']); ?>" style="max-width:300px; max-height:120px;">
        </div>
    <?php endif; ?>

    <form method="POST" action="salvar_assinatura.php" id="formAssinatura">
        <div class="form-group">
            <label>Nome para exibição</label>
            <input type="text" name="nome_exibicao" value="<?php echo htmlspecialchars($usuario['assinatura_nome_exibicao'] ?: $usuario['nome']); ?>" required>
        </div>

        <div class="form-group">
            <label>Cargo para exibição</label>
            <input type="text" name="cargo_exibicao" value="<?php echo htmlspecialchars($usuario['assinatura_cargo_exibicao'] ?: ucfirst($usuario['perfil'])); ?>" required>
        </div>

        <label>Assine abaixo:</label>
        <canvas id="canvasAssinatura" width="700" height="220" style="border:1px solid #ccc; border-radius:8px; background:#fff; width:100%; max-width:700px;"></canvas>

        <input type="hidden" name="assinatura_base64" id="assinatura_base64">

        <div style="margin-top:15px;">
            <button type="button" class="btn btn-danger" onclick="limparCanvas()">Limpar</button>
            <button type="submit" class="btn btn-success">Salvar assinatura</button>
            <a href="dashboard.php" class="btn">Voltar</a>
        </div>
    </form>
</div>

<script>
var canvas = document.getElementById('canvasAssinatura');
var ctx = canvas.getContext('2d');
var desenhando = false;

ctx.lineWidth = 2;
ctx.lineCap = 'round';

function posicao(e) {
    var rect = canvas.getBoundingClientRect();

    if (e.touches && e.touches.length > 0) {
        return {
            x: e.touches[0].clientX - rect.left,
            y: e.touches[0].clientY - rect.top
        };
    }

    return {
        x: e.clientX - rect.left,
        y: e.clientY - rect.top
    };
}

function iniciar(e) {
    e.preventDefault();
    desenhando = true;
    var p = posicao(e);
    ctx.beginPath();
    ctx.moveTo(p.x, p.y);
}

function desenhar(e) {
    if (!desenhando) return;
    e.preventDefault();
    var p = posicao(e);
    ctx.lineTo(p.x, p.y);
    ctx.stroke();
}

function parar(e) {
    e.preventDefault();
    desenhando = false;
}

function limparCanvas() {
    ctx.clearRect(0, 0, canvas.width, canvas.height);
}

canvas.addEventListener('mousedown', iniciar);
canvas.addEventListener('mousemove', desenhar);
canvas.addEventListener('mouseup', parar);
canvas.addEventListener('mouseleave', parar);

canvas.addEventListener('touchstart', iniciar);
canvas.addEventListener('touchmove', desenhar);
canvas.addEventListener('touchend', parar);

document.getElementById('formAssinatura').addEventListener('submit', function(e) {
    document.getElementById('assinatura_base64').value = canvas.toDataURL('image/png');
});
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>