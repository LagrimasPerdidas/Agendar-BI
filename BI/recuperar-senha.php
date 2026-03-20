<?php
include 'db.php';

$success = "";
$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST["email"]);

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Por favor, insira um e-mail válido.";
    } else {
        // 1. Verificar se o usuário existe para pegar o ID dele
        $stmt = $db->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            // Dica de segurança: em produção, evite dizer que o e-mail não existe.
            // Aqui mantemos para facilitar o seu desenvolvimento.
            $error = "Este e-mail não está registado no sistema.";
        } else {
            $userId = $user['id'];

            // 2. Gerar token seguro
            $token = bin2hex(random_bytes(32));
            
            // 3. Definir expiração para 1 hora
            $expira = date("Y-m-d H:i:s", strtotime("+1 hour"));

            try {
                // 4. Limpar tokens antigos usando o user_id (CORREÇÃO DO ERRO FATAL)
                $stmtDel = $db->prepare("DELETE FROM password_resets WHERE user_id = ?");
                $stmtDel->execute([$userId]);

                // 5. Inserir novo token associado ao user_id
                $stmtIns = $db->prepare("INSERT INTO password_resets (user_id, token, expires_at) VALUES (?, ?, ?)");
                $stmtIns->execute([$userId, $token, $expira]);

                // 6. Link de recuperação
                $link = "http://localhost/BI/resetar-senha.php?token=$token";
                $success = "Instruções geradas com sucesso!<br>
                            Como o envio de e-mail ainda não está configurado, utilize o link abaixo:<br><br>
                            <a href='$link' style='color:#2563eb; font-weight:bold; word-break: break-all;'>$link</a>";
            } catch (PDOException $e) {
                $error = "Erro no banco de dados: " . $e->getMessage();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-AO">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperar Senha - SIAC</title>
    <link rel="stylesheet" href="style.css">
</head>
<body style="background: var(--gray-100); display: flex; align-items: center; min-height: 100vh; padding: 1rem;">

    <div class="container" style="max-width: 450px; width: 100%;">
        <div style="text-align: center; margin-bottom: 2rem;">
            <span style="font-size: 3rem;">🇦🇴</span>
            <h2 style="margin-top: 1rem;">SIAC</h2>
            <p style="color: var(--gray-500);">Recuperação de Acesso</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-error" style="background:#fee2e2; color:#991b1b; padding:1rem; border-radius:8px; margin-bottom:1rem; border: 1px solid #f87171;">
                ⚠️ <?= $error ?>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success" style="background:#d1fae5; color:#065f46; padding:1.5rem; border-radius:8px; margin-bottom:1rem; border: 1px solid #10b981;">
                ✅ <?= $success ?>
            </div>
        <?php endif; ?>

        <div class="card" style="background: white; padding: 2rem; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);">
            <div class="card-body">
                <form method="POST">
                    <div class="form-group" style="margin-bottom: 1.5rem;">
                        <label class="form-label" style="display: block; margin-bottom: 0.5rem; font-weight: 500;">E-mail de Registo</label>
                        <input type="email" name="email" class="form-input" style="width: 100%; padding: 0.75rem; border: 1px solid #ddd; border-radius: 6px;" placeholder="exemplo@mail.com" required>
                    </div>
                    <button type="submit" class="btn btn-primary" style="width: 100%; background: #2563eb; color: white; padding: 0.75rem; border: none; border-radius: 6px; cursor: pointer; font-weight: 600;">
                        Enviar Link de Recuperação
                    </button>
                </form>
                <div style="text-align: center; margin-top: 1.5rem;">
                    <a href="login.php" style="color: var(--gray-500); text-decoration: none; font-size: 0.875rem;">
                        ← Voltar ao Login
                    </a>
                </div>
            </div>
        </div>
    </div>

</body>
</html>