<?php
session_start(); // Essencial para verificar Auth
require_once '../db.php'; // Caminho correto para sair da pasta api/

header('Content-Type: application/json; charset=utf-8');

// Se você NÃO tiver a classe Auth pronta, use esta verificação manual:
function checkAdmin() {
    if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
        echo json_encode(['success' => false, 'message' => 'Acesso negado: Apenas administradores']);
        exit;
    }
}

// O $db vem do seu require_once 'db.php'
$method = $_SERVER['REQUEST_METHOD'];

try {
    // ==========================================================
    // GET - LISTAR UTILIZADORES
    // ==========================================================
    if ($method === 'GET') {
        checkAdmin();

        // Adicionei NIF e TELEFONE que são campos do seu formulário de registro
        $stmt = $db->query("SELECT id, nome, email, role, nif, telefone FROM users ORDER BY id DESC");
        echo json_encode([
            'success' => true,
            'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)
        ]);
        exit;
    }

    // ==========================================================
    // POST - CRIAR UTILIZADOR (Via Admin)
    // ==========================================================
    if ($method === 'POST') {
        $data = json_decode(file_get_contents("php://input"), true) ?? $_POST;

        if (empty($data['nome']) || empty($data['email']) || empty($data['password'])) {
            echo json_encode(['success' => false, 'message' => 'Preencha todos os campos obrigatórios']);
            exit;
        }

        $check = $db->prepare("SELECT id FROM users WHERE email = ?");
        $check->execute([$data['email']]);
        if ($check->fetch()) {
            echo json_encode(['success' => false, 'message' => 'Este e-mail já está registado']);
            exit;
        }

        $passwordHash = password_hash($data['password'], PASSWORD_DEFAULT);
        $stmt = $db->prepare("INSERT INTO users (nome, email, password, role, nif) VALUES (?, ?, ?, 'user', ?)");
        
        $stmt->execute([
            $data['nome'],
            $data['email'],
            $passwordHash,
            $data['nif'] ?? null
        ]);

        echo json_encode(['success' => true, 'message' => 'Usuário criado com sucesso']);
        exit;
    }

    // ==========================================================
    // PUT - EDITAR UTILIZADOR
    // ==========================================================
    if ($method === 'PUT') {
        checkAdmin();
        $data = json_decode(file_get_contents("php://input"), true);

        if (empty($data['id'])) {
            echo json_encode(['success' => false, 'message' => 'ID é obrigatório']);
            exit;
        }

        // Atualiza os dados principais
        $stmt = $db->prepare("UPDATE users SET nome = ?, email = ?, role = ? WHERE id = ?");
        $stmt->execute([
            $data['nome'],
            $data['email'],
            $data['role'] ?? 'user',
            $data['id']
        ]);

        echo json_encode(['success' => true, 'message' => 'Utilizador atualizado com sucesso']);
        exit;
    }

    // ==========================================================
    // DELETE - REMOVER/DESATIVAR
    // ==========================================================
    if ($method === 'DELETE') {
        checkAdmin();
        $data = json_decode(file_get_contents("php://input"), true);

        if ($data['id'] == $_SESSION['user_id']) {
            echo json_encode(['success' => false, 'message' => 'Não pode desativar a sua própria conta']);
            exit;
        }

        // No SQLite, podemos deletar ou apenas mudar um status
        $stmt = $db->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$data['id']]);

        echo json_encode(['success' => true, 'message' => 'Utilizador removido']);
        exit;
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Erro: ' . $e->getMessage()
    ]);
}