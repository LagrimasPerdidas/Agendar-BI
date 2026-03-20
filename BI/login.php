<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'db.php'; 

// Garante que temos a conexão
$database = DB::connect(); 

$error = ""; // Inicializamos a variável para evitar o erro da linha 98
$email_digitado = ""; 

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email_digitado = trim($_POST["email"]);
    $password = $_POST["password"];

    try {
        // 1. Procurar o usuário
        $stmt = $database->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
        $stmt->execute([$email_digitado]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            // 2. Verificar a senha (Poeta@926)
            if (password_verify($password, $user["password"])) {
                
                // 3. Sucesso! Guardar na sessão
                $_SESSION["user_id"]   = $user["id"];
                $_SESSION["user_nome"] = $user["nome"];
                $_SESSION["user_role"] = $user["role"];

                // 4. Redirecionar
                if ($user["role"] === 'admin') {
                    header("Location: admin-dashboard.php");
                } else {
                    header("Location: minhas-marcacoes.php");
                }
                exit;

            } else {
                $error = "Palavra-passe incorreta para o utilizador $email_digitado.";
            }
        } else {
            $error = "Nenhum utilizador encontrado com o e-mail: $email_digitado";
        }

    } catch (PDOException $e) {
        $error = "Erro na Base de Dados: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="pt-AO">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Login - Sistema de Agendamento do Bilhete de Identidade.">
    <title>Login - SIAC Bilhete de Identidade | Angola</title>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">

    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>🇦🇴</text></svg>">
</head>
<body>

<header class="header">
    <div class="header-top">
        <div class="container header-top-content">
            <div class="header-top-left">
                <span class="header-top-item">
                    <i class="fas fa-phone-alt"></i> Linha de Apoio: 111
                </span>
                <span class="header-top-item">
                    <i class="fas fa-envelope"></i> suporte@siac.gov.ao
                </span>
            </div>
            <div class="header-top-right">
                <span class="header-top-item">
                    <i class="fas fa-globe"></i> República de Angola
                </span>
            </div>
        </div>
    </div>

    <div class="header-main">
        <div class="container header-content">
            <a href="index.php" class="logo">
                <div class="logo-icon">
                    <i class="fas fa-id-card"></i>
                </div>
                <div class="logo-text">
                    <span class="logo-title">SIAC</span>
                    <span class="logo-subtitle">Bilhete de Identidade</span>
                </div>
            </a>

            <nav class="nav">
                <a href="index.php" class="nav-link">Início</a>
                <a href="marcacao.php" class="nav-link">Nova Marcação</a>
                <a href="minhas-marcacoes.php" class="nav-link">Minhas Marcações</a>
                <a href="faq.php" class="nav-link">Ajuda</a>
                <a href="login.php" class="nav-link active">Entrar</a>
            </nav>
        </div>
    </div>
</header>

<main>
    <div class="container container-narrow" style="padding: 3rem 1.5rem;">
        <div class="card">
            <div class="card-header" style="text-align: center;">
                <h3>Iniciar Sessão</h3>
            </div>

            <div class="card-body">

                <?php if ($error): ?>
                    <div class="alert alert-error">
                        <?php echo $error; ?>
                    </div>
                <?php endif; ?>

                <form method="POST">

                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" name="email" class="form-input" required>
                    </div>

                    <div class="form-group">
                        <label>Senha</label>
                        <input type="password" name="password" class="form-input" required>
                    </div>

                    <button type="submit" class="btn btn-primary btn-block">
                        <i class="fas fa-sign-in-alt"></i> Entrar
                    </button>

                </form>

                <div style="text-align: center; margin-top: 1rem;">
                    <a href="recuperar-senha.php">Esqueceu a senha?</a>
                </div>

                <hr>

                <div style="text-align: center;">
                    <a href="registro.php" class="btn btn-secondary btn-block">
                        Criar Conta
                    </a>
                </div>

            </div>
        </div>
    </div>
</main>

<footer class="footer">
    <div class="container">
        <p>© 2026 República de Angola - SIAC/DNAICC</p>
    </div>
</footer>

</body>
</html>