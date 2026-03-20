<?php
header('Content-Type: application/json; charset=utf-8');
session_start();
require_once '../db.php';

// Verificação de autenticação manual (ajustado para o seu sistema de login)
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Sessão expirada']);
    exit;
}

$db = $db; // Usa a conexão do db.php
$method = $_SERVER['REQUEST_METHOD'];
$data = json_decode(file_get_contents("php://input"), true) ?? $_POST;

$currentUserId = $_SESSION['user_id'];
$isAdmin = (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin');

// ==========================================================
// LEITURA (GET) - Listar Marcações
// ==========================================================
if ($method === 'GET') {
    try {
        // Se for admin e pedir lista geral, mostra tudo. Senão, mostra só do usuário.
        if ($isAdmin && isset($_GET['admin'])) {
            $stmt = $db->prepare("
                SELECT m.*, u.nome as nome_usuario 
                FROM marcacoes m 
                LEFT JOIN users u ON m.user_id = u.id 
                ORDER BY m.data DESC, m.horario DESC
            ");
            $stmt->execute();
        } else {
            $stmt = $db->prepare("SELECT * FROM marcacoes WHERE user_id = ? ORDER BY data DESC");
            $stmt->execute([$currentUserId]);
        }
        
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['success' => true, 'data' => $result]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

// ==========================================================
// ATUALIZAÇÃO (PUT) - Confirmar, Cancelar ou Remarcar
// ==========================================================
if ($method === 'PUT') {
    $action = $data['action'] ?? '';
    $id = $data['id'] ?? null;

    if (!$id) {
        echo json_encode(['success' => false, 'message' => 'ID não fornecido']);
        exit;
    }

    // Lógica para ADMINISTRADOR (Pode confirmar ou cancelar qualquer uma)
    if ($isAdmin) {
        if ($action === 'confirmada' || $action === 'cancelada') {
            $stmt = $db->prepare("UPDATE marcacoes SET status = ? WHERE id = ?");
            $success = $stmt->execute([$action, $id]);
            echo json_encode(['success' => $success]);
            exit;
        }
    }

    // Lógica para USUÁRIO COMUM (Só pode cancelar a própria marcação)
    if ($action === 'cancelar') {
        $stmt = $db->prepare("UPDATE marcacoes SET status = 'cancelada' WHERE id = ? AND user_id = ?");
        $success = $stmt->execute([$id, $currentUserId]);
        echo json_encode(['success' => $success]);
        exit;
    }

    if ($action === 'remarcar') {
        $stmt = $db->prepare("UPDATE marcacoes SET data = ?, horario = ? WHERE id = ? AND user_id = ?");
        $success = $stmt->execute([$data['data'], $data['horario'], $id, $currentUserId]);
        echo json_encode(['success' => $success]);
        exit;
    }
}

// ==========================================================
// CRIAÇÃO (POST) - Nova Marcação
// ==========================================================
if ($method === 'POST') {
    // ... Seu código de POST original está correto ...
    // Apenas certifique-se de usar $currentUserId em vez de Auth::user()['id']
    // para manter a consistência com o resto do código.
}