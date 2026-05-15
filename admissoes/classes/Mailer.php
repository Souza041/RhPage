<?php

require_once __DIR__ . '/../includes/PHPMailer/src/Exception.php';
require_once __DIR__ . '/../includes/PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/../includes/PHPMailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class Mailer
{
    private $pdo;
    private $config;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
        $this->config = require __DIR__ . '/../config/mail.php';
    }

    private function criarMailer()
    {
        $mail = new PHPMailer(true);

        $mail->isSMTP();
        $mail->Host = $this->config['host'];
        $mail->SMTPAuth = true;
        $mail->Username = $this->config['usuario'];
        $mail->Password = $this->config['senha'];
        $mail->Port = (int) $this->config['porta'];
        $mail->CharSet = $this->config['charset'];
        $mail->isHTML(true);

        if (!empty($this->config['secure'])) {
            $mail->SMTPSecure = $this->config['secure'];
        }

        $mail->setFrom($this->config['from_email'], $this->config['from_nome']);

        return $mail;
    }

    public function buscarDestinatariosFixos()
    {
        $sql = "SELECT nome, email, tipo
                FROM email_destinatarios
                WHERE ativo = 1
                ORDER BY id ASC";
        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll();
    }

    public function enviar($assunto, $html, array $destinatarios, $idSolicitacao = null, $tipoEvento = null)
    {
        $resultados = [];

        $modoTeste = !empty($this->config['modo_teste']);
        $emailTeste = $this->config['email_teste'] ?? null;

        foreach ($destinatarios as $destinatario) {
            $nome = isset($destinatario['nome']) ? $destinatario['nome'] : '';
            $email = isset($destinatario['email']) ? $destinatario['email'] : '';

            if ($email === '') {
                continue;
            }

            // 🔥 SE ESTIVER EM MODO TESTE
            if ($modoTeste && $emailTeste) {
                $emailOriginal = $email;
                $nomeOriginal = $nome;

                $email = $emailTeste;
                $nome = 'TESTE';

                // opcional: adicionar info no assunto
                $assuntoFinal = '[TESTE]' . $assunto . ' (destino real: ' . $emailOriginal . ')';
            }   else {
                $assuntoFinal = $assunto;
            }

            try {
                $mail = $this->criarMailer();
                $mail->addAddress($email, $nome);
                $mail->Subject = $assunto;
                $mail->Body = $html;
                $mail->AltBody = strip_tags(str_replace(['<br>', '<br/>', '<br />'], "\n", $html));
                $mail->send();

                $this->registrarNotificacao(
                    $idSolicitacao,
                    $tipoEvento,
                    $nome,
                    $email,
                    $assunto,
                    $html,
                    'enviado',
                    null
                );

                $resultados[] = [
                    'email' => $email,
                    'status' => 'enviado'
                ];
            } catch (Exception $e) {
                $this->registrarNotificacao(
                    $idSolicitacao,
                    $tipoEvento,
                    $nome,
                    $email,
                    $assunto,
                    $html,
                    'erro',
                    $e->getMessage()
                );

                $resultados[] = [
                    'email' => $email,
                    'status' => 'erro',
                    'erro' => $e->getMessage()
                ];
            }
        }

        return $resultados;
    }

    private function registrarNotificacao($idSolicitacao, $tipoEvento, $nome, $email, $assunto, $corpo, $status, $erro = null)
    {
        if (!$idSolicitacao || !$tipoEvento) {
            return;
        }

        $sql = "INSERT INTO notificacoes_email (
                    id_solicitacao,
                    tipo_evento,
                    destinatario_nome,
                    destinatario_email,
                    assunto,
                    corpo,
                    status_envio,
                    erro_envio,
                    data_tentativa,
                    data_cadastro
                ) VALUES (
                    :id_solicitacao,
                    :tipo_evento,
                    :destinatario_nome,
                    :destinatario_email,
                    :assunto,
                    :corpo,
                    :status_envio,
                    :erro_envio,
                    NOW(),
                    NOW()
                )";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':id_solicitacao' => $idSolicitacao,
            ':tipo_evento' => $tipoEvento,
            ':destinatario_nome' => $nome,
            ':destinatario_email' => $email,
            ':assunto' => $assunto,
            ':corpo' => $corpo,
            ':status_envio' => $status,
            ':erro_envio' => $erro
        ]);
    }
}