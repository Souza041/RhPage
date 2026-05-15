<?php
ob_start();
?>
<h2>Solicitação resolvida</h2>

<p>A solicitação foi concluída pelo RH/DHO e mudou para <strong>Resolvido</strong>.</p>

<ul>
    <li><strong>ID:</strong> <?php echo (int) $dados['id']; ?></li>
    <li><strong>Tipo:</strong> <?php echo htmlspecialchars($dados['tipo_label']); ?></li>
    <li><strong>Solicitante:</strong> <?php echo htmlspecialchars($dados['solicitante_nome']); ?></li>
    <li><strong>Nome candidato/funcionário:</strong> <?php echo htmlspecialchars($dados['nome_candidato']); ?></li>
    <li><strong>Data resolução:</strong> <?php echo htmlspecialchars($dados['data_resolucao']); ?></li>
</ul>

<?php if (!empty($dados['rh_nome_candidato']) || !empty($dados['rh_cpf'])): ?>

    <h3>Dados para cadastro TI</h3>

    <ul>
        <li><strong>Nome:</strong> <?php echo htmlspecialchars($dados['rh_nome_candidato'] ?? '-'); ?></li>
        <li><strong>CPF:</strong> <?php echo htmlspecialchars($dados['rh_cpf'] ?? '-'); ?></li>
        <li><strong>Data de nascimento:</strong>
            <?php echo !empty($dados['rh_data_nascimento']) ? date('d/m/Y', strtotime($dados['rh_data_nascimento'])) : '-'; ?>
        </li>
        <li><strong>Setor:</strong> <?php echo htmlspecialchars($dados['rh_setor'] ?? '-'); ?></li>
        <li><strong>Filial:</strong> <?php echo htmlspecialchars($dados['rh_filial'] ?? '-'); ?></li>
        <li><strong>Admissão:</strong>
            <?php echo !empty($dados['rh_admissao']) ? date('d/m/Y', strtotime($dados['rh_admissao'])) : '-'; ?>
        </li>
        <li><strong>Horário de trabalho:</strong> <?php echo htmlspecialchars($dados['rh_horario_trabalho'] ?? '-'); ?></li>
        <li><strong>Função:</strong> <?php echo htmlspecialchars($dados['rh_funcao'] ?? '-'); ?></li>
    </ul>

<?php endif; ?>

<?php if (
    !empty($dados['precisa_equipamentos']) ||
    !empty($dados['precisa_email_corporativo']) ||
    !empty($dados['precisa_cadastro_sistema'])
): ?>
    <h3>Necessidades solicitadas</h3>

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

<p><strong>Observação final:</strong><br><?php echo nl2br(htmlspecialchars($dados['observacao_final'])); ?></p>
<?php
return ob_get_clean();