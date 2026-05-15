<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';

$mensagem = $_GET['msg'] ?? '';

$filtroTipo = trim($_GET['tipo'] ?? '');
$filtroStatus = trim($_GET['status'] ?? '');
$filtroSolicitante = trim($_GET['solicitante'] ?? '');
$filtroNome = trim($_GET['nome'] ?? '');
$filtroDataInicio = trim($_GET['data_inicio'] ?? '');
$filtroDataFim = trim($_GET['data_fim'] ?? '');

$where = ["s.visivel_kanban = 1"];
$params = [];

if ($filtroTipo !== '') {
    $where[] = "s.tipo = :tipo";
    $params[':tipo'] = $filtroTipo;
}

if ($filtroStatus !== '') {
    $where[] = "s.status = :status";
    $params[':status'] = $filtroStatus;
}

if ($filtroSolicitante !== '') {
    $where[] = "u.nome LIKE :solicitante";
    $params[':solicitante'] = '%' . $filtroSolicitante . '%';
}

if ($filtroNome !== '') {
    $where[] = "s.nome_candidato LIKE :nome";
    $params[':nome'] = '%' . $filtroNome . '%';
}

if ($filtroDataInicio !== '') {
    $where[] = "DATE(s.data_solicitacao) >= :data_inicio";
    $params[':data_inicio'] = $filtroDataInicio;
}

if ($filtroDataFim !== '') {
    $where[] = "DATE(s.data_solicitacao) <= :data_fim";
    $params[':data_fim'] = $filtroDataFim;
}

$sql = "SELECT 
            s.id,
            s.tipo,
            s.status,
            s.nome_candidato,
            s.cargo_atual,
            s.cargo_novo,
            s.salario_atual,
            s.salario_novo,
            s.motivo,
            s.observacao_final,
            s.data_solicitacao,
            s.data_aprovacao,
            s.data_resolucao,
            u.nome AS solicitante_nome
        FROM solicitacoes s
        INNER JOIN usuarios u ON u.id = s.id_solicitante";

if (!empty($where)) {
    $sql .= " WHERE " . implode(' AND ', $where);
}

$sql .= " ORDER BY s.data_solicitacao DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$solicitacoes = $stmt->fetchAll();

$abertos = [];
$andamento = [];
$resolvidos = [];
$reprovados = [];

foreach ($solicitacoes as $item) {
    if ($item['status'] === 'aberto') {
        $abertos[] = $item;
    } elseif ($item['status'] === 'em_andamento') {
        $andamento[] = $item;
    } elseif ($item['status'] === 'resolvido') {
        $resolvidos[] = $item;
    } elseif ($item['status'] === 'reprovado') {
        $reprovados[] = $item;
    }
}

function traduzTipo($tipo)
{
    switch ($tipo) {
        case 'admissao':
            return 'Admissão';
        case 'demissao':
            return 'Demissão';
        case 'mudanca_cargo_salario':
            return 'Mudança de Cargo/Salário';
        default:
            return $tipo;
    }
}

function podeAprovar($perfil, $status)
{
    return in_array($perfil, ['diretor', 'admin'], true) && $status === 'aberto';
}

function podeResolver($perfil, $status)
{
    return in_array($perfil, ['rh', 'dho', 'admin'], true) && $status === 'em_andamento';
}
?>
<?php include __DIR__ . '/includes/header.php'; ?>

<?php if ($mensagem === 'sucesso'): ?>
    <div class="alert alert-success">Solicitação cadastrada com sucesso.</div>
<?php elseif ($mensagem === 'aprovado'): ?>
    <div class="alert alert-success">Solicitação enviada para Em andamento.</div>
<?php elseif ($mensagem === 'resolvido'): ?>
    <div class="alert alert-success">Solicitação finalizada com sucesso.</div>
<?php elseif ($mensagem === 'reprovado'): ?>
    <div class="alert alert-error">Solicitação reprovada com sucesso.</div>
<?php elseif ($mensagem === 'erro_permissao'): ?>
    <div class="alert alert-error">Você não tem permissão para executar essa ação.</div>
<?php elseif ($mensagem === 'erro_status'): ?>
    <div class="alert alert-error">Mudança de status inválida.</div>
<?php elseif ($mensagem === 'finalizadas_limpas'): ?>
    <div class="alert alert-success">Solicitações finalizadas removidas do Kanban com sucesso.</div>
<?php elseif ($mensagem === 'reaberto'): ?>
    <div class="alert alert-success">Solicitação reaberta e reenviada para aprovação.</div>
<?php endif; ?>


<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
    <h2 style="margin:0;">Dashboard / Kanban</h2>
    <div>
        <?php if ($_SESSION['usuario_perfil'] === 'admin'): ?>
            <a href="usuarios.php" class="btn">Usuários</a>
        <?php endif; ?>
        <?php if (in_array($_SESSION['usuario_perfil'], ['admin', 'diretor', 'rh', 'dho'], true)): ?>
            <a href="documentos.php" class="btn">Documentos RH</a>
            <a href="validar_documento.php" class="btn">Validar documento</a>
        <?php endif; ?>
        <?php if (in_array($_SESSION['usuario_perfil'], ['diretor', 'rh', 'admin'], true)): ?>
            <a href="limpar_finalizadas.php"
                class="btn btn-danger"
                onclick="return confirm('Deseja remover do kanban todas as solicitações finalizdas?');">
                Limpar finalizadas
            </a>
        <?php endif; ?>
        <strong>Total encontrado:</strong> <?php echo count($solicitacoes); ?>
        <a href="minha_assinatura.php" class="btn">Minha assinatura</a>
        <a href="solicitacao_nova.php" class="btn">+ Nova Solicitação</a>
    </div>
</div>

<div class="filter-toggle">
    <button class="btn" onclick="toggleFiltros()">
        🔍 Filtros
    </button>
</div>

<div class="card filtros-kanban" id="filtrosKanban">
    <form method="GET">
        <div class="row">
            <div class="col-6">
                <div class="form-group">
                    <label>Tipo</label>
                    <select name="tipo">
                        <option value="">Todos</option>
                        <option value="admissao" <?php echo $filtroTipo === 'admissao' ? 'selected' : ''; ?>>Admissão</option>
                        <option value="demissao" <?php echo $filtroTipo === 'demissao' ? 'selected' : ''; ?>>Demissão</option>
                        <option value="mudanca_cargo_salario" <?php echo $filtroTipo === 'mudanca_cargo_salario' ? 'selected' : ''; ?>>Mudança de Cargo/Salário</option>
                    </select>
                </div>
            </div>

            <div class="col-6">
                <div class="form-group">
                    <label>Status</label>
                    <select name="status">
                        <option value="">Todos</option>
                        <option value="aberto" <?php echo $filtroStatus === 'aberto' ? 'selected' : ''; ?>>Aberto</option>
                        <option value="em_andamento" <?php echo $filtroStatus === 'em_andamento' ? 'selected' : ''; ?>>Em andamento</option>
                        <option value="resolvido" <?php echo $filtroStatus === 'resolvido' ? 'selected' : ''; ?>>Resolvido</option>
                    </select>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-6">
                <div class="form-group">
                    <label>Solicitante</label>
                    <input type="text" name="solicitante" value="<?php echo htmlspecialchars($filtroSolicitante); ?>">
                </div>
            </div>

            <div class="col-6">
                <div class="form-group">
                    <label>Nome do candidato / funcionário</label>
                    <input type="text" name="nome" value="<?php echo htmlspecialchars($filtroNome); ?>">
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-6">
                <div class="form-group">
                    <label>Data inicial</label>
                    <input type="date" name="data_inicio" value="<?php echo htmlspecialchars($filtroDataInicio); ?>">
                </div>
            </div>

            <div class="col-6">
                <div class="form-group">
                    <label>Data final</label>
                    <input type="date" name="data_fim" value="<?php echo htmlspecialchars($filtroDataFim); ?>">
                </div>
            </div>
        </div>

        <button type="submit" class="btn">Filtrar</button>
        <a href="dashboard.php" class="btn btn-danger">Limpar</a>
    </form>
</div>

<div class="kanban">
    <div class="kanban-column"
         data-status="aberto"
         ondragover="allowDrop(event)"
         ondrop="drop(event)">
        <h3>Aberto (<?php echo count($abertos); ?>)</h3>
        <?php foreach ($abertos as $item): ?>
            <div class="kanban-card status-<?php echo $item['status']; ?>" draggable="true">
                <div class="card-header-kanban">
                    <span class="tipo-badge tipo-<?php echo $item['tipo']; ?>">
                        <?php echo traduzTipo($item['tipo']); ?>
                    </span>
                </div>

                <h4>
                    #<?php echo $item['id']; ?>
                </h4><br>
                <?php if (!empty($item['nome_candidato'])): ?>
                    Nome: <?php echo htmlspecialchars($item['nome_candidato']); ?><br>
                <?php endif; ?>
                Solicitante: <?php echo htmlspecialchars($item['solicitante_nome']); ?><br>
                <?php if (!empty($item['cargo_novo'])): ?>
                    Cargo: <?php echo htmlspecialchars($item['cargo_novo']); ?><br>
                <?php endif; ?>
                <small>Data: <?php echo date('d/m/Y H:i', strtotime($item['data_solicitacao'])); ?></small>

                <div style="margin-top:10px;">
                    <a class="btn" href="solicitacao_visualizar.php?id=<?php echo $item['id']; ?>">Ver detalhes</a>

                    <?php if (podeAprovar($_SESSION['usuario_perfil'], $item['status'])): ?>
                        <form method="POST" action="solicitacao_status.php" style="display:inline-block;">
                            <input type="hidden" name="id" value="<?php echo $item['id']; ?>">
                            <input type="hidden" name="acao" value="aprovar">
                            <button type="submit" class="btn btn-success">Enviar para Em andamento</button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="kanban-column"
         data-status="em_andamento"
         ondragover="allowDrop(event)"
         ondrop="drop(event)">
        <h3>Em andamento (<?php echo count($andamento); ?>)</h3>
        <?php foreach ($andamento as $item): ?>
            <div class="kanban-card status-<?php echo $item['status']; ?>" draggable="true">
                <div class="card-header-kanban">
                    <span class="tipo-badge tipo-<?php echo $item['tipo']; ?>">
                        <?php echo traduzTipo($item['tipo']); ?>
                    </span>
                </div>

                <h4>
                    #<?php echo $item['id']; ?>
                </h4><br>
                <?php if (!empty($item['nome_candidato'])): ?>
                    Nome: <?php echo htmlspecialchars($item['nome_candidato']); ?><br>
                <?php endif; ?>
                Solicitante: <?php echo htmlspecialchars($item['solicitante_nome']); ?><br>
                <?php if (!empty($item['cargo_novo'])): ?>
                    Cargo: <?php echo htmlspecialchars($item['cargo_novo']); ?><br>
                <?php endif; ?>
                <small>Data: <?php echo date('d/m/Y H:i', strtotime($item['data_solicitacao'])); ?></small>

                <div style="margin-top:10px;">
                    <a class="btn" href="solicitacao_visualizar.php?id=<?php echo $item['id']; ?>">Ver detalhes</a>

                    <?php if (podeResolver($_SESSION['usuario_perfil'], $item['status'])): ?>
                        <a class="btn btn-success" href="solicitacao_visualizar.php?id=<?php echo $item['id']; ?>&finalizar=1">Finalizar</a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="kanban-column"
         data-status="resolvido"
         ondragover="allowDrop(event)"
         ondrop="drop(event)">
        <h3>Resolvido (<?php echo count($resolvidos); ?>)</h3>
        <?php foreach ($resolvidos as $item): ?>
            <div class="kanban-card status-<?php echo $item['status']; ?>" draggable="true">
                <div class="card-header-kanban">
                    <span class="tipo-badge tipo-<?php echo $item['tipo']; ?>">
                        <?php echo traduzTipo($item['tipo']); ?>
                    </span>
                </div>

                <h4>
                    #<?php echo $item['id']; ?>
                </h4><br>
                <?php if (!empty($item['nome_candidato'])): ?>
                    Nome: <?php echo htmlspecialchars($item['nome_candidato']); ?><br>
                <?php endif; ?>
                Solicitante: <?php echo htmlspecialchars($item['solicitante_nome']); ?><br>
                <?php if (!empty($item['cargo_novo'])): ?>
                    Cargo: <?php echo htmlspecialchars($item['cargo_novo']); ?><br>
                <?php endif; ?>
                <small>Data: <?php echo date('d/m/Y H:i', strtotime($item['data_solicitacao'])); ?></small>

                <div style="margin-top:10px;">
                    <a class="btn" href="solicitacao_visualizar.php?id=<?php echo $item['id']; ?>">Ver detalhes</a>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="kanban-column"
         data-status="reprovado"
         ondragover="allowDrop(event)"
         ondrop="drop(event)">
        <h3>Reprovado (<?php echo count($reprovados); ?>)</h3>
        <?php foreach ($reprovados as $item): ?>
            <div class="kanban-card status-<?php echo $item['status']; ?>" draggable="true">
                <div class="card-header-kanban">
                    <span class="tipo-badge tipo-<?php echo $item['tipo']; ?>">
                        <?php echo traduzTipo($item['tipo']); ?>
                    </span>
                </div>

                <h4>
                    #<?php echo $item['id']; ?>
                </h4><br>
                <?php if (!empty($item['nome_candidato'])): ?>
                    Nome: <?php echo htmlspecialchars($item['nome_candidato']); ?><br>
                <?php endif; ?>
                Solicitante: <?php echo htmlspecialchars($item['solicitante_nome']); ?><br>
                <?php if (!empty($item['cargo_novo'])): ?>
                    Cargo: <?php echo htmlspecialchars($item['cargo_novo']); ?><br>
                <?php endif; ?>
                <small>Data: <?php echo date('d/m/Y H:i', strtotime($item['data_solicitacao'])); ?></small>

                <div style="margin-top:10px;">
                    <a class="btn" href="solicitacao_visualizar.php?id=<?php echo $item['id']; ?>">Ver detalhes</a>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<script>
function toggleFiltros() {
    const filtro = document.getElementById('filtrosKanban');

    if (filtro.style.display === 'none' || filtro.style.display === '') {
        filtro.style.display = 'block';
    } else {
        filtro.style.display = 'none';
    }
}

let draggedCard = null;

document.querySelectorAll('.kanban-card').forEach(card => {
    
    card.addEventListener('dragstart', function () {
        draggedCard = this;
    });
});

function allowDrop(ev) {
    ev.preventDefault();
}

function drop(ev) {
    ev.preventDefault();

    const coluna = ev.currentTarget;

    if (draggedCard) {
        coluna.appendChild(draggedCard);

        const novoStatus = coluna.dataset.status;

        console.log('Novo status', novoStatus);
    }
}
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>