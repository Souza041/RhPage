<?php

function getClientIp()
{
    $keys = [
        'HTTP_CF_CONNECTING_IP',
        'HTTP_X_FORWARDED_FOR',
        'REMOTE_ADDR'
    ];

    foreach ($keys as $key) {
        if (!empty($_SERVER[$key])) {
            $value = trim(explode(',', $_SERVER[$key])[0]);
            if ($value !== '') {
                return $value;
            }
        }
    }

    return null;
}

function buscarDadosAssinaturaUsuario(PDO $pdo, $idUsuario)
{
    $sql = "SELECT
                id,
                nome,
                email,
                perfil,
                assinatura_imagem,
                assinatura_nome_exibicao,
                assinatura_cargo_exibicao
            FROM usuarios
            WHERE id = :id
            LIMIT 1";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id' => (int)$idUsuario]);

    return $stmt->fetch();
}

function registrarAssinaturaSolicitacao(PDO $pdo, $idSolicitacao, $idUsuario, $etapa, $acao)
{
    $usuario = buscarDadosAssinaturaUsuario($pdo, $idUsuario);

    if (!$usuario) {
        return false;
    }

    $nomeExibicao = !empty($usuario['assinatura_nome_exibicao'])
        ? $usuario['assinatura_nome_exibicao']
        : $usuario['nome'];

    $cargoExibicao = !empty($usuario['assinatura_cargo_exibicao'])
        ? $usuario['assinatura_cargo_exibicao']
        : ucfirst($usuario['perfil']);

    $sql = "INSERT INTO solicitacoes_assinaturas (
                id_solicitacao,
                id_usuario,
                etapa,
                acao,
                assinatura_imagem,
                nome_exibicao,
                cargo_exibicao,
                email_usuario,
                data_assinatura,
                ip_origem
            ) VALUES (
                :id_solicitacao,
                :id_usuario,
                :etapa,
                :acao,
                :assinatura_imagem,
                :nome_exibicao,
                :cargo_exibicao,
                :email_usuario,
                NOW(),
                :ip_origem
            )";

    $stmt = $pdo->prepare($sql);

    return $stmt->execute([
        ':id_solicitacao' => (int)$idSolicitacao,
        ':id_usuario' => (int)$idUsuario,
        ':etapa' => $etapa,
        ':acao' => $acao,
        ':assinatura_imagem' => !empty($usuario['assinatura_imagem']) ? $usuario['assinatura_imagem'] : null,
        ':nome_exibicao' => $nomeExibicao,
        ':cargo_exibicao' => $cargoExibicao,
        ':email_usuario' => $usuario['email'],
        ':ip_origem' => getClientIp()
    ]);
}

function buscarAssinaturasSolicitacao(PDO $pdo, $idSolicitacao)
{
    $sql = "SELECT
                sa.*,
                u.nome AS usuario_nome
            FROM solicitacoes_assinaturas sa
            INNER JOIN usuarios u ON u.id = sa.id_usuario
            WHERE sa.id_solicitacao = :id_solicitacao
            ORDER BY sa.data_assinatura ASC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id_solicitacao' => (int)$idSolicitacao]);

    return $stmt->fetchAll();
}