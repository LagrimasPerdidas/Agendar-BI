<?php
session_start();
include 'db.php';

$success = "";
$error = "";

// Inicializamos as variáveis para manter os dados no formulário em caso de erro
$nome = $dataNascimento = $genero = $nif = $email = $telefone = $provincia = $municipio = $endereco = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Coleta e Limpeza de dados
    $nome = trim($_POST["nomeCompleto"]);
    $dataNascimento = $_POST["dataNascimento"];
    $genero = $_POST["genero"];
    $nif = strtoupper(trim($_POST["nif"]));
    $email = strtolower(trim($_POST["email"]));
    $telefone = trim($_POST["telefone"]);
    $provincia = $_POST["provincia"];
    $municipio = trim($_POST["municipio"]);
    $endereco = trim($_POST["endereco"]);
    $password = $_POST["senha"];
    $confirmar = $_POST["confirmarSenha"];
    $notificacoes = isset($_POST["notificacoes"]) ? 1 : 0;

    // 1. Validação de Senha
    if ($password !== $confirmar) {
        $error = "As senhas não coincidem.";
    } elseif (strlen($password) < 6) {
        $error = "A senha deve ter pelo menos 6 caracteres.";
    } else {
        // 2. Verificar duplicados individualmente para dar feedback específico
        
        // Verifica E-mail
        $stmtEmail = $db->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
        $stmtEmail->execute([$email]);
        
        // Verifica NIF
        $stmtNif = $db->prepare("SELECT id FROM users WHERE nif = ? LIMIT 1");
        $stmtNif->execute([$nif]);

        if ($stmtEmail->fetch()) {
            $error = "O campo E-mail já existe no sistema.";
            $email = ""; // Reseta apenas este campo
        } elseif ($stmtNif->fetch()) {
            $error = "O campo NIF já existe no sistema.";
            $nif = ""; // Reseta apenas este campo
        } else {
            // 3. Criar Hash e Inserir
            $hash = password_hash($password, PASSWORD_DEFAULT);

            try {
                $sql = "INSERT INTO users 
                        (nome, data_nascimento, genero, nif, email, telefone, provincia, municipio, endereco, password, notificacoes)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                
                $stmt = $db->prepare($sql);
                $exec = $stmt->execute([
                    $nome, $dataNascimento, $genero, $nif, $email, 
                    $telefone, $provincia, $municipio, $endereco, $hash, $notificacoes
                ]);

                if ($exec) {
                    $success = "Conta criada com sucesso! Já pode aceder ao sistema.";
                }
            } catch (PDOException $e) {
                $error = "Erro no sistema: " . $e->getMessage();
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
    <title>Registo - SIAC | Angola</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .registo-container { max-width: 600px; margin: 2rem auto; padding: 0 1.5rem; }
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; }
        .full-width { grid-column: span 2; }
        @media (max-width: 600px) { .form-grid { grid-template-columns: 1fr; } .full-width { grid-column: span 1; } }
    </style>
</head>
<body style="background: var(--gray-100);">

    <div class="registo-container">
        <div style="text-align: center; margin-bottom: 2rem;">
            <span style="font-size: 2.5rem;">🇦🇴</span>
            <h2 style="margin-top: 0.5rem;">Criar Conta SIAC</h2>
            <p style="color: var(--gray-500);">Portal do Bilhete de Identidade</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-error" style="background:#fee2e2; color:#991b1b; padding:1rem; border-radius:8px; margin-bottom:1rem; border: 1px solid #f87171;">
                ⚠️ <?= $error ?>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success" style="background:#d1fae5; color:#065f46; padding:1.5rem; border-radius:8px; margin-bottom:1rem; border: 1px solid #10b981; text-align: center;">
                ✅ <?= $success ?><br><br>
                <a href="login.php" class="btn btn-primary" style="text-decoration: none;">Ir para o Login</a>
            </div>
        <?php else: ?>

        <div class="card">
            <div class="card-body">
                <form method="POST" class="form-grid">
                    
                    <div class="form-group full-width">
                        <label class="form-label">Nome Completo</label>
                        <input type="text" name="nomeCompleto" class="form-input" value="<?= htmlspecialchars($nome) ?>" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Data de Nascimento</label>
                        <input type="date" name="dataNascimento" class="form-input" value="<?= htmlspecialchars($dataNascimento) ?>" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Género</label>
                        <select name="genero" class="form-select" required>
                            <option value="masculino" <?= $genero == 'masculino' ? 'selected' : '' ?>>Masculino</option>
                            <option value="feminino" <?= $genero == 'feminino' ? 'selected' : '' ?>>Feminino</option>
                        </select>
                    </div>

                    <div class="form-group full-width">
                        <label class="form-label">NIF (Número de Identificação Fiscal)</label>
                        <input type="text" name="nif" class="form-input" placeholder="Ex: 006543210LA041" value="<?= htmlspecialchars($nif) ?>" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">E-mail</label>
                        <input type="email" name="email" class="form-input" value="<?= htmlspecialchars($email) ?>" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Telefone</label>
                        <input type="tel" name="telefone" class="form-input" value="<?= htmlspecialchars($telefone) ?>" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Província</label>
                        <input type="text" name="provincia" class="form-input" value="<?= htmlspecialchars($provincia) ?>">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Município</label>
                        <input type="text" name="municipio" class="form-input" value="<?= htmlspecialchars($municipio) ?>">
                    </div>

                    <div class="form-group full-width">
                        <label class="form-label">Endereço de Residência</label>
                        <textarea name="endereco" class="form-input" rows="2"><?= htmlspecialchars($endereco) ?></textarea>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Senha</label>
                        <input type="password" name="senha" class="form-input" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Confirmar Senha</label>
                        <input type="password" name="confirmarSenha" class="form-input" required>
                    </div>

                    <div class="form-group full-width">
                        <label style="display: flex; align-items: center; gap: 10px; cursor: pointer;">
                            <input type="checkbox" name="notificacoes" <?= $notificacoes ? 'checked' : '' ?>>
                            <span style="font-size: 0.875rem; color: var(--gray-600);">Desejo receber notificações sobre as minhas marcações via e-mail.</span>
                        </label>
                    </div>

                    <div class="full-width" style="margin-top: 1rem;">
                        <button type="submit" class="btn btn-primary" style="width: 100%;">Criar Minha Conta</button>
                    </div>
                </form>
            </div>
        </div>
        <p style="text-align: center; margin-top: 1.5rem; font-size: 0.875rem; color: var(--gray-600);">
            Já tem uma conta? <a href="login.php" style="color: var(--primary); font-weight: bold; text-decoration: none;">Entrar</a>
        </p>
        <?php endif; ?>
    </div>

</body>
</html>