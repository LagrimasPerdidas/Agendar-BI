<?php
require_once 'auth.php';

/**
 * Verifica se o usuário está logado via Sessão.
 * Se não estiver, interrompe a execução e retorna 401.
 */
function verificarAuth() {
    // 1. Garante que o PHP saiba que vamos devolver JSON
    if (!headers_sent()) {
        header('Content-Type: application/json; charset=utf-8');
    }

    // 2. Tenta recuperar o usuário logado da classe Auth
    $usuario = Auth::user();

    if (!$usuario) {
        http_response_code(401);
        echo json_encode([
            'success' => false, 
            'message' => 'Sessão expirada ou acesso negado. Por favor, faça login.'
        ]);
        exit;
    }

    // Opcional: Você pode retornar os dados do usuário para usar no script que chamou
    return $usuario;
}

/**
 * Verifica se o usuário logado tem permissão de Administrador.
 */
function verificarAdmin() {
    $user = verificarAuth();
    
    if ($user['role'] !== 'admin') {
        http_response_code(403);
        echo json_encode([
            'success' => false, 
            'message' => 'Acesso restrito apenas para administradores.'
        ]);
        exit;
    }
    
    return true;
}