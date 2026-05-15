<?php
ob_start();
?>
<h2>Nova solicitação aberta</h2>

<p>Uma nova solicitação foi cadastrada no sistema.</p>

<ul>
    <li><strong>ID:</strong> <?php echo (int) $dados['id']; ?></li>
    <li><strong>Tipo:</strong> <?php echo htmlspecialchars($dados['tipo_label']); ?></li>
    <li><strong>Solicitante:</strong> <?php echo htmlspecialchars($dados['solicitante_nome']); ?></li>
    <li><strong>E-mail:</strong> <?php echo htmlspecialchars($dados['solicitante_email']); ?></li>
    <li><strong>Nome candidato/funcionário:</strong> <?php echo htmlspecialchars($dados['nome_candidato']); ?></li>
    <li><strong>Data/Hora:</strong> <?php echo htmlspecialchars($dados['data_solicitacao']); ?></li>
</ul>

<?php if (
    !empty($dados['precisa_equipamentos']) ||
    !empty($dados['precisa_email_corporativo']) ||
    !empty($dados['precisa_cadastro_sistema'])
): ?>
    <p><strong>Necessidades da admissão:</strong></p>
    <ul>
        <?php if (!empty($dados['precisa_equipamentos'])): ?>
            <li>Equipamentos</li>
        <?php endif; ?>

        <?php if (!empty($dados['precisa_email_corporativo'])): ?>
            <li>E-mail corporativo</li>
        <?php endif; ?>

        <?php if (!empty($dados['precisa_cadastro_sistema'])): ?>
            <li>Cadastro no sistema</li>
        <?php endif; ?>
    </ul>
<?php endif; ?>

<p><strong>Motivo:</strong><br><?php echo nl2br(htmlspecialchars($dados['motivo'])); ?></p>
<?php
return ob_get_clean();