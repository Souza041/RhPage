<?php

class Usuario
{
    private $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function listarTodos()
    {
        $sql = "SELECT id, nome, email, perfil, setor, unidade, ativo, data_cadastro
                FROM usuarios
                ORDER BY nome ASC";
        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll();
    }

    public function buscarPorId($id)
    {
        $sql = "SELECT id, nome, email, perfil, setor, unidade, ativo
                FROM usuarios
                WHERE id = :id
                LIMIT 1";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':id' => (int)$id]);
        return $stmt->fetch();
    }

    public function buscarPorEmail($email)
    {
        $sql = "SELECT id, email
                FROM usuarios
                WHERE email = :email
                LIMIT 1";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':email' => $email]);
        return $stmt->fetch();
    }

    public function criar($dados)
    {
        $sql = "INSERT INTO usuarios (
                    nome,
                    email,
                    senha,
                    perfil,
                    setor,
                    unidade,
                    ativo,
                    data_cadastro
                ) VALUES (
                    :nome,
                    :email,
                    :senha,
                    :perfil,
                    :setor,
                    :unidade,
                    :ativo,
                    NOW()
                )";

        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            ':nome' => $dados['nome'],
            ':email' => $dados['email'],
            ':senha' => $dados['senha'],
            ':perfil' => $dados['perfil'],
            ':setor' => $dados['setor'] !== '' ? $dados['setor'] : null,
            ':unidade' => $dados['unidade'] !== '' ? $dados['unidade'] : null,
            ':ativo' => isset($dados['ativo']) ? (int)$dados['ativo'] : 1
        ]);
    }

    public function atualizar($id, $dados)
    {
        $sql = "UPDATE usuarios
                SET nome = :nome,
                    email = :email,
                    perfil = :perfil,
                    setor = :setor,
                    unidade = :unidade,
                    ativo = :ativo
                WHERE id = :id";

        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            ':id' => (int)$id,
            ':nome' => $dados['nome'],
            ':email' => $dados['email'],
            ':perfil' => $dados['perfil'],
            ':setor' => $dados['setor'] !== '' ? $dados['setor'] : null,
            ':unidade' => $dados['unidade'] !== '' ? $dados['unidade'] : null,
            ':ativo' => isset($dados['ativo']) ? (int)$dados['ativo'] : 1
        ]);
    }

    public function atualizarSenha($id, $novaSenhaHash)
    {
        $sql = "UPDATE usuarios
                SET senha = :senha
                WHERE id = :id";

        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            ':id' => (int)$id,
            ':senha' => $novaSenhaHash
        ]);
    }

    public function alternarStatus($id)
    {
        $sql = "UPDATE usuarios
                SET ativo = CASE WHEN ativo = 1 THEN 0 ELSE 1 END
                WHERE id = :id";

        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            ':id' => (int)$id
        ]);
    }
}