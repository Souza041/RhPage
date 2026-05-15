
<?php
ob_start();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <style>
        body {
            font-family: DejaVu Sans, Arial, sans-serif;
            font-size: 12px;
            color: #222;
        }

        h1, h2, h3 {
            margin: 0 0 10px 0;
        }

        .topo {
            margin-bottom: 20px;
            border-bottom: 2px solid #333;
            padding-bottom: 10px;
        }

        .secao {
            margin-bottom: 18px;
        }

        .linha {
            margin-bottom: 6px;
        }

        .box {
            border: 1px solid #ccc;
            padding: 10px;
            border-radius: 4px;
            background: #f8f8f8;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            border: 1px solid #ccc;
            padding: 8px;
            text-align: left;
            vertical-align: top;
        }

        .assinatura-box {
            margin-top: 10px;
            margin-bottom: 15px;
            border: 1px solid #ddd;
            padding: 10px;
        }

        .assinatura-img {
            max-height: 70px;
            margin-top: 8px;
        }

        .muted {
            color: #666;
        }
    </style>
</head>
<body>
    <div class="topo">
        <h1>Solicitação RH</h1>
        <div class="muted">Documento gerado automaticamente pelo sistema</div>
    </div>

    <div class="secao">
        <h3>Dados da solicitação</h3>
        <table>
            <tr>
                <th style="width: 25%;">Número</th>
                <td>#<?php echo (int)$solicitacao['id']; ?></td>
            </tr>
            <tr>
                <th>Tipo</th>
                <td><?php echo htmlspecialchars($solicitacao['tipo_label']); ?></td>
            </tr>
            <tr>
                <th>Status</th>
                <td><?php echo htmlspecialchars($solicitacao['status']); ?></td>
            </tr>
            <tr>
                <th>Solicitante</th>
                <td><?php echo htmlspecialchars($solicitacao['solicitante_nome']); ?></td>
            </tr>
            <tr>
                <th>E-mail</th>
                <td><?php echo htmlspecialchars($solicitacao['solicitante_email']); ?></td>
            </tr>
            <tr>
                <th>Nome candidato / funcionário</th>
                <td>
                    <?php
                        echo htmlspecialchars(
                            !empty($solicitacao['rh_nome_candidato'])
                                ? $solicitacao['rh_nome_candidato']
                                : $solicitacao['nome_candidato']
                        );
                    ?>
                </td>
            </tr>
            <tr>
                <th>CPF</th>
                <td>
                    <?php
                        echo htmlspecialchars(
                            !empty($solicitacao['rh_cpf'])
                                ? $solicitacao['rh_cpf']
                                : $solicitacao['cpf_candidato']
                        );
                    ?>
                </td>
            </tr>
            <tr>
                <th>Cargo atual</th>
                <td><?php echo htmlspecialchars($solicitacao['cargo_atual']); ?></td>
            </tr>
            <tr>
                <th>Novo cargo</th>
                <td><?php echo htmlspecialchars($solicitacao['cargo_novo']); ?></td>
            </tr>
            <tr>
                <th>Salário atual</th>
                <td>
                    <?php echo $solicitacao['salario_atual'] !== null ? 'R$ ' . number_format($solicitacao['salario_atual'], 2, ',', '.') : '-'; ?>
                </td>
            </tr>
            <tr>
                <th>Novo salário</th>
                <td>
                    <?php echo $solicitacao['salario_novo'] !== null ? 'R$ ' . number_format($solicitacao['salario_novo'], 2, ',', '.') : '-'; ?>
                </td>
            </tr>
            <tr>
                <th>Setor solicitante</th>
                <td><?php echo htmlspecialchars($solicitacao['setor_solicitante']); ?></td>
            </tr>
            <tr>
                <th>Unidade / Filial</th>
                <td><?php echo htmlspecialchars($solicitacao['unidade_solicitante']); ?></td>
            </tr>
            <tr>
                <th>Data solicitação</th>
                <td><?php echo htmlspecialchars($solicitacao['data_solicitacao_formatada']); ?></td>
            </tr>
            <tr>
                <th>Data aprovação</th>
                <td><?php echo htmlspecialchars($solicitacao['data_aprovacao_formatada']); ?></td>
            </tr>
            <tr>
                <th>Data resolução</th>
                <td><?php echo htmlspecialchars($solicitacao['data_resolucao_formatada']); ?></td>
            </tr>
        </table>
    </div>

    <?php if (
        !empty($solicitacao['rh_nome_candidato']) ||
        !empty($solicitacao['rh_cpf']) ||
        !empty($solicitacao['rh_funcao'])
    ): ?>
        <div class="secao">
            <h3>Dados para cadastro TI</h3>

            <table>
                <tr>
                    <th style="width: 25%;">Nome</th>
                    <td><?php echo htmlspecialchars($solicitacao['rh_nome_candidato'] ?? '-'); ?></td>
                </tr>
                <tr>
                    <th>CPF</th>
                    <td><?php echo htmlspecialchars($solicitacao['rh_cpf'] ?? '-'); ?></td>
                </tr>
                <tr>
                    <th>Data de nascimento</th>
                    <td>
                        <?php echo !empty($solicitacao['rh_data_nascimento']) ? date('d/m/Y', strtotime($solicitacao['rh_data_nascimento'])) : '-'; ?>
                    </td>
                </tr>
                <tr>
                    <th>Setor</th>
                    <td><?php echo htmlspecialchars($solicitacao['rh_setor'] ?? '-'); ?></td>
                </tr>
                <tr>
                    <th>Filial</th>
                    <td><?php echo htmlspecialchars($solicitacao['rh_filial'] ?? '-'); ?></td>
                </tr>
                <tr>
                    <th>Admissão</th>
                    <td>
                        <?php echo !empty($solicitacao['rh_admissao']) ? date('d/m/Y', strtotime($solicitacao['rh_admissao'])) : '-'; ?>
                    </td>
                </tr>
                <tr>
                    <th>Horário de trabalho</th>
                    <td><?php echo htmlspecialchars($solicitacao['rh_horario_trabalho'] ?? '-'); ?></td>
                </tr>
                <tr>
                    <th>Função</th>
                    <td><?php echo htmlspecialchars($solicitacao['rh_funcao'] ?? '-'); ?></td>
                </tr>
            </table>
        </div>
    <?php endif; ?>

    <?php if (
        !empty($solicitacao['precisa_equipamentos']) ||
        !empty($solicitacao['precisa_email_corporativo']) ||
        !empty($solicitacao['precisa_cadastro_sistema'])
    ): ?>
        <div class="secao">
            <h3>Necessidades solicitadas</h3>

            <div class="box">
                <?php if (!empty($solicitacao['precisa_equipamentos'])): ?>
                    <div class="linha">• Equipamentos</div>
                <?php endif; ?>

                <?php if (!empty($solicitacao['precisa_email_corporativo'])): ?>
                    <div class="linha">• E-mail corporativo</div>
                <?php endif; ?>

                <?php if (!empty($solicitacao['precisa_cadastro_sistema'])): ?>
                    <div class="linha">• Cadastro no sistema</div>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>

    <div class="secao">
        <h3>Motivo / Observação inicial</h3>
        <div class="box">
            <?php echo nl2br(htmlspecialchars($solicitacao['motivo'])); ?>
        </div>
    </div>

    <?php if (!empty($solicitacao['observacao_final'])): ?>
        <div class="secao">
            <h3>Observação final</h3>
            <div class="box">
                <?php echo nl2br(htmlspecialchars($solicitacao['observacao_final'])); ?>
            </div>
        </div>
    <?php endif; ?>

    <div class="secao">
        <h3>Assinaturas</h3>

        <?php foreach ($assinaturas as $assinatura): ?>
            <div class="assinatura-box">
                <div class="linha"><strong>Nome:</strong> <?php echo htmlspecialchars($assinatura['nome_exibicao']); ?></div>
                <div class="linha"><strong>Cargo:</strong> <?php echo htmlspecialchars($assinatura['cargo_exibicao']); ?></div>
                <div class="linha"><strong>E-mail:</strong> <?php echo htmlspecialchars($assinatura['email_usuario']); ?></div>
                <div class="linha"><strong>Data:</strong> <?php echo date('d/m/Y H:i', strtotime($assinatura['data_assinatura'])); ?></div>

                <?php if (!empty($assinatura['assinatura_imagem_base64'])): ?>
                    <div class="linha">
                        <img
                            class="assinatura-img"
                            src="<?php echo $assinatura['assinatura_imagem_base64']; ?>"
                            alt="Assinatura"
                        >
                    </div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
    <div class="secao">
        <h3>Validação do documento</h3>
        <div class="box">
            <div class="linha">
                <strong>Código de verificação:</strong>
                <?php echo htmlspecialchars($solicitacao['codigo_verificacao']); ?>
            </div>

            <div class="linha">
                <strong>Validação:</strong>
                <?php echo htmlspecialchars($solicitacao['url_validacao']); ?>
            </div>
        </div>
    </div>
</body>
</html>
<?php
return ob_get_clean();