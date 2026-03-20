<?php
require_once 'db.php';

class Auth {
    public static function login($email, $password) {
        try {
            $db = DB::connect();

            // Buscamos o usuário pelo e-mail
            $stmt = $db->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            // 1. Verifica se usuário existe
            if (!$user) {
                return ["success" => false, "message" => "E-mail ou senha inválidos"];
            }

            // 2. Verifica a senha (usando o hash salvo no banco)
            if (!password_verify($password, $user['password'])) {
                return ["success" => false, "message" => "E-mail ou senha inválidos"];
            }

            // 3. Login Sucesso: Iniciar Sessão
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }

            // Salvamos apenas o essencial na sessão por segurança
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_nome'] = $user['nome'];
            $_SESSION['user_role'] = $user['role'];

            return [
                "success" => true,
                "message" => "Login realizado com sucesso",
                "user" => [
                    "id" => $user['id'],
                    "nome" => $user['nome'],
                    "role" => $user['role']
                ]
            ];

        } catch (PDOException $e) {
            // Log de erro para o desenvolvedor, mensagem genérica para o usuário
            return ["success" => false, "message" => "Erro interno no servidor"];
        }
    }

    public static function logout() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        session_destroy();
        return ["success" => true, "message" => "Logout realizado"];
    }
} 

public static function user() {
    if (session_status() === PHP_SESSION_NONE) { session_start(); }
    return isset($_SESSION['user_id']) ? [
        'id' => $_SESSION['user_id'],
        'nome' => $_SESSION['user_nome'],
        'role' => $_SESSION['user_role']
    ] : null;
}

public static function requireLogin() {
    if (self::user() === null) {
        header('Content-Type: application/json');
        http_response_code(401);
        echo json_encode(["success" => false, "message" => "Acesso negado. Faça login."]);
        exit;
    }
}

public static function requireAdmin() {
    $user = self::user(); // Pega o usuário da sessão
    if (!$user || $user['role'] !== 'admin') {
        header('Content-Type: application/json');
        http_response_code(403);
        echo json_encode(["success" => false, "message" => "Acesso restrito a administradores."]);
        exit;
    }
}// Fim da classe}