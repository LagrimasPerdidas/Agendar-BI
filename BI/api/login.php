<?php
// Inicia a sessão para que o login persista no navegador
session_start();

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit;

// Caminho para o auth.php (ajuste se ele estiver na raiz ou na pasta api)
require_once 'auth.php'; 

$rawInput = file_get_contents("php://input");
$data = json_decode($rawInput, true) ?? $_POST;

$email = isset($data['email']) ? trim($data['email']) : null;
$password = isset($data['password']) ? $data['password'] : null;

if (empty($email) || empty($password)) {
    echo json_encode([
        "success" => false,
        "message" => "E-mail e palavra-passe são obrigatórios"
    ]);
    exit;
}

try {
    // Chamada para a sua classe Auth
    $result = Auth::login($email, $password);
    
    // Se o login for sucesso, o resultado será enviado ao frontend (dashboard)
    echo json_encode($result);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Erro interno: " . $e->getMessage()
    ]);
}