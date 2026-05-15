<?php
function logPdf($msg)
{
    if (defined('ENV') && ENV === 'production') {
        return;
    }
    
    $arquivo = __DIR__ . '/../logs/pdf_debug.log';
    $linha = '[' . date('Y-m-d H:i:s') . '] ' . $msg . PHP_EOL;
    file_put_contents($arquivo, $linha, FILE_APPEND);
}

function traduzTipoPdf($tipo)
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

function buscarSolicitacaoCompletaPdf(PDO $pdo, $idSolicitacao)
{
    $sql = "SELECT
                s.*,
                u.nome AS solicitante_nome,
                u.email AS solicitante_email
            FROM solicitacoes s
            INNER JOIN usuarios u ON u.id = s.id_solicitante
            WHERE s.id = :id
            LIMIT 1";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id' => (int)$idSolicitacao]);

    $dados = $stmt->fetch();

    if (!$dados) {
        return null;
    }

    $dados['tipo_label'] = traduzTipoPdf($dados['tipo']);
    $dados['data_solicitacao_formatada'] = !empty($dados['data_solicitacao']) ? date('d/m/Y H:i', strtotime($dados['data_solicitacao'])) : '-';
    $dados['data_aprovacao_formatada'] = !empty($dados['data_aprovacao']) ? date('d/m/Y H:i', strtotime($dados['data_aprovacao'])) : '-';
    $dados['data_resolucao_formatada'] = !empty($dados['data_resolucao']) ? date('d/m/Y H:i', strtotime($dados['data_resolucao'])) : '-';

    return $dados;
}

function prepararAssinaturasPdf(PDO $pdo, $idSolicitacao)
{
    $sql = "SELECT *
            FROM solicitacoes_assinaturas
            WHERE id_solicitacao = :id_solicitacao
            ORDER BY data_assinatura ASC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id_solicitacao' => (int)$idSolicitacao]);
    $assinaturas = $stmt->fetchAll();

    foreach ($assinaturas as &$assinatura) {
        $assinatura['assinatura_imagem_base64'] = '';

        logPdf('--- ASSINATURA ---');
        logPdf('DB caminho: ' . $assinatura['assinatura_imagem']);

        if (!empty($assinatura['assinatura_imagem'])) {

            $caminho = __DIR__ . '/../' . ltrim($assinatura['assinatura_imagem'], '/');

            logPdf('Caminho bruto: ' . $caminho);

            $real = realpath($caminho);

            if ($real && is_file($real)) {
                $assinatura['assinatura_imagem_absoluta'] = str_replace('\\', '/', $real);

                $conteudoImagem = file_get_contents($real);
                $assinatura['assinatura_imagem_base64'] = $conteudoImagem
                    ? 'data:image/png;base64,' . base64_encode($conteudoImagem)
                    : '';
                logPdf('OK -> imagem encontrada');
            } else {
                logPdf('ERRO -> imagem NÃO encontrada');
            }
        } else {
            logPdf('ERRO -> campo assinatura_imagem vazio');
        }
    }

    return $assinaturas;
}

function registrarDocumentoGerado(PDO $pdo, $idSolicitacao, $nomeArquivo, $caminhoArquivo, $tamanho, $geradoPor, $codigoVerificacao, $hashDocumento)
{
    $sql = "INSERT INTO solicitacoes_documentos (
                id_solicitacao,
                tipo_documento,
                codigo_verificacao,
                hash_documento,
                nome_arquivo,
                caminho_arquivo,
                mime_type,
                tamanho_arquivo,
                gerado_por,
                data_geracao
            ) VALUES (
                :id_solicitacao,
                'pdf_final',
                :codigo_verificacao,
                :hash_documento,
                :nome_arquivo,
                :caminho_arquivo,
                'application/pdf',
                :tamanho_arquivo,
                :gerado_por,
                NOW()
            )";

    $stmt = $pdo->prepare($sql);

    return $stmt->execute([
        ':id_solicitacao' => (int)$idSolicitacao,
        ':codigo_verificacao' => $codigoVerificacao,
        ':hash_documento' => $hashDocumento,
        ':nome_arquivo' => $nomeArquivo,
        ':caminho_arquivo' => $caminhoArquivo,
        ':tamanho_arquivo' => (int)$tamanho,
        ':gerado_por' => (int)$geradoPor
    ]);
}

function gerarPdfFinalSolicitacao(PDO $pdo, $idSolicitacao, $geradoPor)
{
    // Ajusta aqui conforme tua forma final de carregar o Dompdf
    require_once __DIR__ . '/dompdf/vendor/autoload.php';

    $solicitacao = buscarSolicitacaoCompletaPdf($pdo, $idSolicitacao);
    if (!$solicitacao) {
        throw new Exception('Solicitação não encontrada para geração do PDF.');
    }

    $assinaturas = prepararAssinaturasPdf($pdo, $idSolicitacao);

    $codigoVerificacao = gerarCodigoVerificacaoBonito($idSolicitacao);
    $urlValidacao = montarUrlValidacao($codigoVerificacao);

    $solicitacao['codigo_verificacao'] = $codigoVerificacao;
    $solicitacao['url_validacao'] = $urlValidacao;

    $html = require __DIR__ . '/../templates/pdf_solicitacao.php';

    $dompdf = new Dompdf\Dompdf([
        'isRemoteEnabled' => true
    ]);

    $dompdf->loadHtml($html, 'UTF-8');
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();

    $diretorio = __DIR__ . '/../uploads/documentos_rh/';

    if (!is_dir($diretorio)) {
        if (!mkdir($diretorio, 0775, true) && !is_dir($diretorio)) {
            throw new Exception('Não foi possível criar a pasta dos documentos.');
        }
    }

    $nomeArquivo = 'solicitacao_' . (int)$idSolicitacao . '_' . date('Ymd_His') . '.pdf';

    $caminhoAbsoluto = $diretorio . $nomeArquivo;
    $caminhoRelativo = 'uploads/documentos_rh/' . $nomeArquivo;

    $pdfOutput = $dompdf->output();

    if (empty($pdfOutput)) {
        throw new Exception('Dompdf retornou conteúdo vazio.');
    }
    
    $resultado = file_put_contents($caminhoAbsoluto, $pdfOutput);

    if ($resultado === false) {
        throw new Exception('Erro no file_put_contents em: ' . $caminhoAbsoluto);
    }
    
    if (!file_exists($caminhoAbsoluto)) {
        throw new Exception('Arquivo não encontrado após salvar: ' . $caminhoAbsoluto);
    }
        
    $hashDocumento = gerarHashDocumento($caminhoAbsoluto);

    registrarDocumentoGerado(
        $pdo,
        $idSolicitacao,
        $nomeArquivo,
        $caminhoRelativo,
        filesize($caminhoAbsoluto),
        $geradoPor,
        $codigoVerificacao,
        $hashDocumento
    );

    return [
        'nome_arquivo' => $nomeArquivo,
        'caminho_absoluto' => $caminhoAbsoluto,
        'caminho_relativo' => $caminhoRelativo,
        'codigo_verificacao' => $codigoVerificacao,
        'hash_documento' => $hashDocumento
    ];
}

function gerarCodigoVerificacaoBonito($idSolicitacao)
{
    $prefixo = 'RH';
    $data = date('Ymd');
    $idFormatado = str_pad((int)$idSolicitacao, 6, '0', STR_PAD_LEFT);
    $sufixo = strtoupper(substr(bin2hex(random_bytes(3)), 0, 6));

    return $prefixo . '-' . $data . '-' . $idFormatado . '-' . $sufixo;
}

function gerarHashDocumento($caminhoArquivo)
{
    if (!file_exists($caminhoArquivo)) {
        throw new Exception('Arquivo não encontrado para gerar hash.');
    }

    return hash_file('sha256', $caminhoArquivo);
}

function montarUrlValidacao($codigoVerificacao)
{
    $base = '';

    if (defined('BASE_URL') && BASE_URL !== '') {
        $base = rtrim(BASE_URL, '/');
    }

    // Ajusta aqui se teu sistema estiver em outro caminho
    // Exemplo local:
    // http://localhost/RHpage/admissoes
    if ($base === '' || strpos($base, 'http') !== 0) {
        $base = '/admissoes';
    }

    return $base . '/validar_documento.php?codigo=' . urlencode($codigoVerificacao);
}