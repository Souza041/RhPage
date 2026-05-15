<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/pdf_solicitacao.php';

$idSolicitacao = 1; // troca para uma solicitação real já resolvida ou existente

try {
    $resultado = gerarPdfFinalSolicitacao($pdo, $idSolicitacao, $_SESSION['usuario_id']);

    echo '<pre>';
    print_r($resultado);
    echo '</pre>';
} catch (Exception $e) {
    echo 'Erro ao gerar PDF: ' . $e->getMessage();
}