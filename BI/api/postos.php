<?php
session_start();
require_once '../db.php'; // Ajustado o caminho para a raiz

header('Content-Type: application/json; charset=utf-8');

// O $db vem do db.php via classe DB::connect() ou variável global
$db = DB::connect();
$method = $_SERVER['REQUEST_METHOD'];

// Helper rápido para validar admin se a classe Auth não estiver disponível
function isAdmin() {
    return (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin');
}

try {
    // ==========================================================
    // GET - LISTAR (Público ou Usuário logado)
    // ==========================================================
    if ($method === 'GET') {
        // Buscamos apenas os ativos. Se não houver a coluna 'ativo', a query falhará (veja o SQL abaixo)
        $stmt = $db->query("SELECT id, nome, endereco, telefone FROM postos WHERE ativo = 1 ORDER BY nome ASC");
        $postos = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode([
            'success' => true,
            'data' => $postos
        ]);
        exit;
    }

    // ==========================================================
    // POST - CRIAR (EXCLUSIVO ADMIN)
    // ==========================================================
    if ($method === 'POST') {
        if (!isAdmin()) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Acesso negado']);
            exit;
        }

        $data = json_decode(file_get_contents("php://input"), true) ?? $_POST;

        if (empty($data['nome']) || empty($data['endereco'])) {
            echo json_encode(['success' => false, 'message' => 'Nome e Endereço são obrigatórios']);
            exit;
        }

        $stmt = $db->prepare("INSERT INTO postos (nome, endereco, telefone, ativo) VALUES (?, ?, ?, 1)");
        $stmt->execute([
            $data['nome'],
            $data['endereco'],
            $data['telefone'] ?? null
        ]);

        echo json_encode(['success' => true, 'message' => 'Posto cadastrado com sucesso']);
        exit;
    }

    // ==========================================================
    // DELETE - DESATIVAR (EXCLUSIVO ADMIN)
    // ==========================================================
    if ($method === 'DELETE') {
        if (!isAdmin()) {
            http_response_code(403);
            exit;
        }

        $data = json_decode(file_get_contents("php://input"), true);
        $id = $data['id'] ?? null;

        if (!$id) {
            echo json_encode(['success' => false, 'message' => 'ID do posto é obrigatório']);
            exit;
        }

        $stmt = $db->prepare("UPDATE postos SET ativo = 0 WHERE id = ?");
        $stmt->execute([$id]);

        echo json_encode(['success' => true, 'message' => 'Posto desativado']);
        exit;
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Erro: ' . $e->getMessage()
    ]);
}