<?php

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/classes/Mailer.php';

try {
    $mailer = new Mailer($pdo);

    $destinatarios = [
        [
            'nome' => 'Teste',
            'email' => 'seuemail@dominio.com'
        ]
    ];

    $resultado = $mailer->enviar(
        'Teste SMTP',
        '<h3>Teste de envio OK</h3><p>PHPMailer funcionando.</p>',
        $destinatarios
    );

    echo '<pre>';
    print_r($resultado);
    echo '</pre>';
} catch (Exception $e) {
    echo 'Erro: ' . $e->getMessage();
}