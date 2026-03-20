<?php
include 'db.php';

$error = "";
$success = "";
$token = $_GET['token'] ?? '';

// 1. Validar se o token foi fornecido
if (empty($token)) {
    die("Acesso negado. Token não fornecido.");
}

// 2. Verificar se o token existe e não expirou
// Fazemos um JOIN para buscar o ID do usuário associado a esse token
$stmt = $db->prepare("
    SELECT pr.user_id, pr.token 
    FROM password_resets pr 
    WHERE pr.token = ? AND pr.expires_at > datetime('now', 'localtime')
    LIMIT 1
");
$stmt->execute([$token]);
$reset = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$reset) {
    die("O link de recuperação é inválido ou já expirou. Por favor, peça um novo no formulário de recuperação.");
}

// 3. Processar formulário de nova senha
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $senha = $_POST["senha"];
    $confirmar = $_POST["confirmarSenha"];

    if (strlen($senha) < 6) {
        $error = "A nova senha deve ter pelo menos 6 caracteres.";
    } elseif ($senha !== $confirmar) {
        $error = "As senhas introduzidas não são iguais.";
    } else {
        // Criptografar nova senha
        $hash = password_hash($senha, PASSWORD_DEFAULT);
        $userId = $reset['user_id'];

        // Iniciar Transação para garantir que ambas as operações ocorram
        $db->beginTransaction();
        try {
            // Atualizar senha do usuário pelo ID
            $stmt = $db->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->execute([$hash, $userId]);

            // Eliminar o token para evitar reuso pelo ID do usuário
            $stmt = $db->prepare("DELETE FROM password_resets WHERE user_id = ?");
            $stmt->execute([$userId]);

            $db->commit();
            $success = "Senha redefinida com sucesso! Já pode aceder à sua conta.";
        } catch (Exception $e) {
            $db->rollBack();
            $error = "Ocorreu um erro ao salvar a nova senha: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-AO">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nova Senha - SIAC</title>
    <link rel="stylesheet" href="style.css">
</head>
<body style="background: var(--gray-100); display: flex; align-items: center; min-height: 100vh; padding: 1rem;">

<div class="container" style="max-width: 450px; width: 100%; margin: auto;">
    
    <div style="text-align: center; margin-bottom: 2rem;">
        <span style="font-size: 3rem;">🇦🇴</span>
        <h3 style="margin-top: 1rem;">Definir Nova Senha</h3>
        <p style="color: var(--gray-500);">Escolha uma senha forte para a sua conta SIAC.</p>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-error" style="background:#fee2e2; color:#991b1b; padding:1rem; border-radius:8px; margin-bottom:1rem; border: 1px solid #f87171;">
            ⚠️ <?= $error ?>
        </div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="alert alert-success" style="background:#d1fae5; color:#065f46; padding:2rem; border-radius:8px; margin-bottom:1rem; text-align:center; border: 1px solid #10b981;">
            <p style="font-size: 1.1rem; font-weight: 500;">✅ <?= $success ?></p>
            <a href="login.php" class="btn btn-primary" style="display:inline-block; margin-top:1.5rem; text-decoration:none; background:#2563eb; color:white; padding:0.8rem 2rem; border-radius:6px; font-weight:bold;">Ir para o Login</a>
        </div>
    <?php else: ?>

    <div class="card" style="background: white; padding: 2rem; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
        <div class="card-body">
            <form method="POST">
                <div class="form-group" style="margin-bottom: 1.2rem;">
                    <label class="form-label" style="display:block; margin-bottom:0.5rem; font-weight:500;">Nova Senha</label>
                    <input type="password" name="senha" class="form-input" style="width:100%; padding:0.8rem; border:1px solid #ddd; border-radius:6px;" placeholder="Mínimo 6 caracteres" required>
                </div>

                <div class="form-group" style="margin-bottom: 1.5rem;">
                    <label class="form-label" style="display:block; margin-bottom:0.5rem; font-weight:500;">Confirmar Nova Senha</label>
                    <input type="password" name="confirmarSenha" class="form-input" style="width:100%; padding:0.8rem; border:1px solid #ddd; border-radius:6px;" placeholder="Repita a senha" required>
                </div>

                <button type="submit" class="btn btn-primary" style="width: 100%; background:#2563eb; color:white; padding:0.8rem; border:none; border-radius:6px; cursor:pointer; font-weight:bold; font-size:1rem;">
                    Confirmar Alteração
                </button>
            </form>
        </div>
    </div>
    <?php endif; ?>
</div>

</body>
</html>