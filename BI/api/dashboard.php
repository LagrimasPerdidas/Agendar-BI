<?php
session_start();
require_once '../db.php'; // Sobe um nível para achar o db.php na raiz
// Se o seu auth.php estiver na raiz, use: require_once '../auth.php';

header('Content-Type: application/json; charset=utf-8');

// Verificação de segurança (caso não queira depender de classes externas)
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Acesso negado']);
    exit;
}

// O $db deve vir do seu db.php
// Se o seu db.php usa uma classe, você pode manter o $db = DB::connect();
// Mas aqui vou usar a variável global $db que definiremos no db.php abaixo.

try {
    // 1. Total Geral
    $total = $db->query("SELECT COUNT(*) FROM marcacoes")->fetchColumn();

    // 2. Confirmadas (Alinhado com o status que o admin define)
    $confirmadas = $db->query("SELECT COUNT(*) FROM marcacoes WHERE status = 'confirmada'")->fetchColumn();

    // 3. Pendentes
    $pendentes = $db->query("SELECT COUNT(*) FROM marcacoes WHERE status = 'pendente'")->fetchColumn();

    // 4. Canceladas
    $canceladas = $db->query("SELECT COUNT(*) FROM marcacoes WHERE status = 'cancelada'")->fetchColumn();

    // Retorno no formato que o JavaScript (fetch) espera
    echo json_encode([
        'success' => true,
        'data' => [
            'total' => (int)$total,
            'concluidas' => (int)$confirmadas, // Mapeado para 'concluidas' no JS
            'pendentes' => (int)$pendentes,
            'canceladas' => (int)$canceladas
        ]
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'Erro ao processar estatísticas: ' . $e->getMessage()
    ]);
}