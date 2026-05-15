<?php
require_once __DIR__ . '/includes/auth.php';

if (!in_array($_SESSION['usuario_perfil'], ['diretor', 'rh', 'dho', 'admin'], true)) {
    header('Location: dashboard.php?msg=acesso_negado');
    exit;
}

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
include __DIR__ . '/includes/header.php';

$periodo = isset($_GET['periodo']) ? (int) $_GET['periodo'] : 30;
$periodosPermitidos = [7, 30, 90, 180];

if (!in_array($periodo, $periodosPermitidos, true)) {
    $periodo = 30;
}
?>

<style>
.metricas-topo {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
}

.metricas-filtros-rapidos {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}

.btn-periodo {
    display: inline-block;
    padding: 10px 16px;
    border-radius: 10px;
    background: #f2f2f2;
    color: #000;
    text-decoration: none;
    border: 1px solid #ddd;
    font-weight: 600;
}

.btn-periodo.ativo {
    background: #000;
    color: #fff;
    border-color: #000;
}

.metricas-abas {
    display: flex;
    gap: 10px;
    margin-bottom: 20px;
    flex-wrap: wrap;
}

.aba-metrica {
    border: 1px solid #ddd;
    background: #f5f5f5;
    color: #000;
    padding: 10px 16px;
    border-radius: 10px;
    cursor: pointer;
    font-weight: 600;
}

.aba-metrica.ativa {
    background: #000;
    color: #fff;
    border-color: #000;
}

.metricas-grid-cards {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 20px;
    margin-bottom: 20px;
}

.metrica-card {
    background: #fff;
    border-radius: 16px;
    padding: 18px;
    box-shadow: 0 2px 10px rgba(0,0,0,.08);
}

.metrica-card .titulo {
    font-size: 14px;
    color: #555;
    margin-bottom: 8px;
}

.metrica-card .valor {
    font-size: 40px;
    font-weight: bold;
    color: #0d2b57;
}

.metricas-grid-principal {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: 20px;
    margin-bottom: 20px;
}

.bloco-metrica {
    background: #fff;
    border-radius: 16px;
    padding: 18px;
    box-shadow: 0 2px 10px rgba(0,0,0,.08);
}

.bloco-metrica h3 {
    margin-top: 0;
    margin-bottom: 15px;
}

.metricas-grid-inferior {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
}

.kpi-linha {
    display: flex;
    justify-content: space-between;
    padding: 8px 0;
    border-bottom: 1px solid #eee;
}

.kpi-linha:last-child {
    border-bottom: none;
}

.tabela-metrica {
    width: 100%;
    border-collapse: collapse;
}

.tabela-metrica th,
.tabela-metrica td {
    text-align: left;
    padding: 10px 8px;
    border-bottom: 1px solid #eee;
}

.tabela-metrica th {
    font-weight: bold;
}

.conteudo-aba {
    display: none;
}

.conteudo-aba.ativo {
    display: block;
}

.filtros-avancados {
    background: #fff;
    border-radius: 16px;
    padding: 18px;
    box-shadow: 0 2px 10px rgba(0,0,0,.08);
    margin-bottom: 20px;
}

canvas {
    max-width: 100%;
}

@media (max-width: 1100px) {
    .metricas-grid-cards {
        grid-template-columns: repeat(2, 1fr);
    }

    .metricas-grid-principal,
    .metricas-grid-inferior {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 700px) {
    .metricas-topo {
        flex-direction: column;
        align-items: flex-start;
        gap: 15px;
    }

    .metricas-grid-cards {
        grid-template-columns: 1fr;
    }
}
</style>

<div class="metricas-topo">
    <h1 style="margin:0;">Dashboard de Métricas</h1>

    <div class="metricas-filtros-rapidos">
        <a href="metricas.php?periodo=7" class="btn-periodo <?php echo $periodo === 7 ? 'ativo' : ''; ?>">7 dias</a>
        <a href="metricas.php?periodo=30" class="btn-periodo <?php echo $periodo === 30 ? 'ativo' : ''; ?>">30 dias</a>
        <a href="metricas.php?periodo=90" class="btn-periodo <?php echo $periodo === 90 ? 'ativo' : ''; ?>">90 dias</a>
        <a href="metricas.php?periodo=180" class="btn-periodo <?php echo $periodo === 180 ? 'ativo' : ''; ?>">180 dias</a>
    </div>
</div>

<div class="filtros-avancados">
    <form id="formFiltrosMetricas">
        <div class="row">
            <div class="col-6">
                <div class="form-group">
                    <label>Solicitante</label>
                    <input type="text" name="solicitante" id="filtro_solicitante">
                </div>
            </div>

            <div class="col-6">
                <div class="form-group">
                    <label>Nome do candidato / funcionário</label>
                    <input type="text" name="nome_candidato" id="filtro_nome_candidato">
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-6">
                <div class="form-group">
                    <label>Status</label>
                    <select name="status" id="filtro_status">
                        <option value="">Todos</option>
                        <option value="aberto">Aberto</option>
                        <option value="em_andamento">Em andamento</option>
                        <option value="resolvido">Resolvido</option>
                    </select>
                </div>
            </div>

            <div class="col-6">
                <div class="form-group">
                    <label>Tipo</label>
                    <select name="tipo" id="filtro_tipo">
                        <option value="">Todos</option>
                        <option value="admissao">Admissão</option>
                        <option value="demissao">Demissão</option>
                        <option value="mudanca_cargo_salario">Mudança de Cargo/Salário</option>
                    </select>
                </div>
            </div>
        </div>

        <button type="button" class="btn" id="btnAplicarFiltros">Aplicar filtros</button>
        <button type="button" class="btn btn-danger" id="btnLimparFiltros">Limpar</button>
    </form>
</div>

<div class="metricas-abas">
    <button class="aba-metrica ativa" data-aba="resumo">Resumo</button>
    <button class="aba-metrica" data-aba="admissoes">Admissões</button>
    <button class="aba-metrica" data-aba="demissoes">Demissões</button>
    <button class="aba-metrica" data-aba="mudancas">Mudanças</button>
    <button class="aba-metrica" data-aba="sla">SLA</button>
</div>

<div id="aba-resumo" class="conteudo-aba ativo">
    <div class="metricas-grid-cards">
        <div class="metrica-card">
            <div class="titulo">Pedidos no período</div>
            <div class="valor" id="card_total_periodo">0</div>
        </div>

        <div class="metrica-card">
            <div class="titulo">Em aberto</div>
            <div class="valor" id="card_aberto">0</div>
        </div>

        <div class="metrica-card">
            <div class="titulo">Em andamento</div>
            <div class="valor" id="card_andamento">0</div>
        </div>

        <div class="metrica-card">
            <div class="titulo">Concluídos</div>
            <div class="valor" id="card_resolvido">0</div>
        </div>
    </div>

    <div class="metricas-grid-principal">
        <div class="bloco-metrica">
            <h3>Pedidos por dia</h3>
            <canvas id="graficoPedidosDia" height="120"></canvas>
        </div>

        <div class="bloco-metrica">
            <h3>SLA</h3>
            <canvas id="graficoSla" height="180"></canvas>
            <div style="margin-top:15px;">
                <strong>Tempo médio para concluir:</strong>
                <span id="tempo_medio_conclusao">0 h</span>
            </div>
        </div>
    </div>

    <div class="metricas-grid-inferior">
        <div class="bloco-metrica">
            <h3>Pedidos por tipo</h3>
            <canvas id="graficoTipos" height="140"></canvas>
        </div>

        <div class="bloco-metrica">
            <h3>Pedidos por setor</h3>
            <table class="tabela-metrica" id="tabela_setores">
                <thead>
                    <tr>
                        <th>Setor</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td colspan="2">Carregando...</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div id="aba-admissoes" class="conteudo-aba">
    <div class="bloco-metrica">
        <h3>Métricas de Admissões</h3>
        <div id="conteudo_admissoes"></div>
    </div>
</div>

<div id="aba-demissoes" class="conteudo-aba">
    <div class="bloco-metrica">
        <h3>Métricas de Demissões</h3>
        <div id="conteudo_demissoes"></div>
    </div>
</div>

<div id="aba-mudancas" class="conteudo-aba">
    <div class="bloco-metrica">
        <h3>Métricas de Mudanças de Cargo/Salário</h3>
        <div id="conteudo_mudancas"></div>
    </div>
</div>

<div id="aba-sla" class="conteudo-aba">
    <div class="bloco-metrica">
        <h3>Indicadores de SLA</h3>
        <div id="conteudo_sla"></div>
    </div>
</div>

<script>
window.METRICAS_CONFIG = {
    periodo: <?php echo (int)$periodo; ?>
};
</script>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="assets/js/metricas.js"></script>

<?php include __DIR__ . '/includes/footer.php'; ?>