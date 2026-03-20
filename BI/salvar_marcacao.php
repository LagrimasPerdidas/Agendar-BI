<?php
session_start();
require 'db.php'; // Certifique-se que o db.php tem a conexão $db

header('Content-Type: application/json');

// 1. Verificar se o usuário está logado
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Sessão expirada. Faça login novamente.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // 2. Coletar dados do formulário
        $usuario_id = $_SESSION['user_id'];
        $servico_id = $_POST['servico_id'] ?? null;
        $posto_id   = $_POST['posto_id'] ?? null;
        $data       = $_POST['data'] ?? null;
        $horario    = $_POST['horario'] ?? null;
        $nome       = $_POST['nome'] ?? '';
        $bi         = $_POST['bi'] ?? '';
        $email      = $_POST['email'] ?? '';
        $telefone   = $_POST['telefone'] ?? '';
        $obs        = $_POST['observacoes'] ?? '';

        // 3. Validações básicas
        if (!$servico_id || !$posto_id || !$data || !$horario || empty($nome)) {
            throw new Exception("Por favor, preencha todos os campos obrigatórios.");
        }

        // 4. Gerar um Código de Confirmação Único (Ex: SIAC-12345)
        $codigo = "SIAC-" . strtoupper(substr(md5(uniqid()), 0, 6));

        // 5. Inserir no Banco de Dados
        $sql = "INSERT INTO marcacoes (
                    usuario_id, servico_id, posto_id, data_agendamento, 
                    horario, nome_completo, bi_numero, email_contacto, 
                    telefone_contacto, observacoes, codigo_confirmacao, status
                ) VALUES (
                    :uid, :sid, :pid, :data, 
                    :hora, :nome, :bi, :email, 
                    :tel, :obs, :codigo, 'pendente'
                )";

        $stmt = $db->prepare($sql);
        $stmt->execute([
            ':uid'    => $usuario_id,
            ':sid'    => $servico_id,
            ':pid'    => $posto_id,
            ':data'   => $data,
            ':hora'   => $horario,
            ':nome'   => $nome,
            ':bi'     => $bi,
            ':email'  => $email,
            ':tel'    => $telefone,
            ':obs'    => $obs,
            ':codigo' => $codigo
        ]);

        // 6. Retornar Sucesso
        echo json_encode([
            'success' => true, 
            'codigo' => $codigo,
            'message' => 'Marcação realizada com sucesso!'
        ]);

    } catch (Exception $e) {
        echo json_encode([
            'success' => false, 
            'message' => $e->getMessage()
        ]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Método inválido.']);
}