<?php
ob_start();
?>
<h2>Solicitação aprovada</h2>

<p>A solicitação foi aprovada pelo diretor e mudou para <strong>Em andamento</strong>.</p>

<ul>
    <li><strong>ID:</strong> <?php echo (int) $dados['id']; ?></li>
    <li><strong>Tipo:</strong> <?php echo htmlspecialchars($dados['tipo_label']); ?></li>
    <li><strong>Solicitante:</strong> <?php echo htmlspecialchars($dados['solicitante_nome']); ?></li>
    <li><strong>Nome candidato/funcionário:</strong> <?php echo htmlspecialchars($dados['nome_candidato']); ?></li>
    <li><strong>Data aprovação:</strong> <?php echo htmlspecialchars($dados['data_aprovacao']); ?></li>
</ul>
<?php
return ob_get_clean();