<?php
session_start();
require_once '../db.php'; // Ajustado para sair da pasta /api

header('Content-Type: application/json; charset=utf-8');

$db = DB::connect();
$method = $_SERVER['REQUEST_METHOD'];

// Helper para validar Admin
function checkAdmin() {
    if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Acesso negado']);
        exit;
    }
}

try {
    // ==========================================================
    // GET - CONSULTAR DISPONIBILIDADE
    // ==========================================================
    if ($method === 'GET') {
        $postoId = $_GET['posto_id'] ?? null;
        $dataConsulta = $_GET['data'] ?? null;

        if (!$postoId || !$dataConsulta) {
            echo json_encode(['success' => false, 'message' => 'Posto e Data são obrigatórios']);
            exit;
        }

        // 1. Busca capacidade total
        $stmt = $db->prepare("SELECT quantidade FROM vagas WHERE posto_id = ? AND data = ?");
        $stmt->execute([$postoId, $dataConsulta]);
        $vaga = $stmt->fetch();
        $capacidadeTotal = $vaga ? (int)$vaga['quantidade'] : 0;

        // 2. Conta ocupação (status 'pendente' ou 'confirmada')
        $stmt2 = $db->prepare("
            SELECT COUNT(*) as ocupadas 
            FROM marcacoes 
            WHERE posto_id = ? AND data = ? AND status IN ('pendente', 'confirmada')
        ");
        $stmt2->execute([$postoId, $dataConsulta]);
        $ocupadas = (int)$stmt2->fetch()['ocupadas'];

        $disponiveis = max(0, $capacidadeTotal - $ocupadas);

        echo json_encode([
            'success' => true,
            'vagas_totais' => $capacidadeTotal,
            'vagas_disponiveis' => $disponiveis,
            'mensagem' => $disponiveis > 0 ? "Disponível" : "Esgotado"
        ]);
        exit;
    }

    // ==========================================================
    // POST - DEFINIR VAGAS (EXCLUSIVO ADMIN)
    // ==========================================================
    if ($method === 'POST') {
        checkAdmin();

        $data = json_decode(file_get_contents("php://input"), true) ?? $_POST;

        if (empty($data['posto_id']) || empty($data['data']) || !isset($data['quantidade'])) {
            echo json_encode(['success' => false, 'message' => 'Dados incompletos']);
            exit;
        }

        $db->beginTransaction();
        try {
            // Limpa definição anterior para evitar duplicados
            $del = $db->prepare("DELETE FROM vagas WHERE posto_id = ? AND data = ?");
            $del->execute([$data['posto_id'], $data['data']]);
            
            // Insere a nova quantidade
            $ins = $db->prepare("INSERT INTO vagas (posto_id, data, quantidade) VALUES (?, ?, ?)");
            $ins->execute([$data['posto_id'], $data['data'], (int)$data['quantidade']]);
            
            $db->commit();
            echo json_encode(['success' => true, 'message' => 'Vagas atualizadas']);
        } catch (Exception $e) {
            $db->rollBack();
            throw $e;
        }
        exit;
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}