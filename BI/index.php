<?php
session_start();
// Ajustado para bater com o padrão que definimos no api/auth.php e login.php
$user_logged = isset($_SESSION['user_id']);
$is_admin = (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin');
?>
<!DOCTYPE html>
<html lang="pt-AO">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SIAC - Agendamento de BI</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>

<header class="header">
    <div class="header-main">
        <div class="container header-content">
            <a href="index.php" class="logo">
                <div class="logo-icon"><i class="fas fa-id-card"></i></div>
                <div class="logo-text">
                    <span class="logo-title">SIAC</span>
                    <span class="logo-subtitle">DNAICC Angola</span>
                </div>
            </a>

            <nav class="nav">
                <a href="index.php" class="nav-link active">Início</a>
                <a href="marcacao.php" class="nav-link">Nova Marcação</a>
                
                <?php if ($user_logged): ?>
                    <a href="minhas-marcacoes.php" class="nav-link">Minhas Marcações</a>
                    
                    <?php if ($is_admin): ?>
                        <a href="admin_dashboard.php" class="nav-link" style="color: #3498db;"><i class="fas fa-user-shield"></i> Painel Admin</a>
                    <?php endif; ?>

                    <a href="logout.php" class="nav-link" style="color: #e74c3c;">Sair</a>
                <?php else: ?>
                    <a href="login.php" class="nav-link">Entrar</a>
                    <a href="registro.php" class="nav-link">Criar Conta</a>
                <?php endif; ?>
            </nav>
        </div>
    </div>
</header>

<main>
    <section class="hero">
        <div class="container hero-content">
            <h1>Agende o seu <span>Bilhete de Identidade</span></h1>
            <p>Serviço oficial da República de Angola para emissão e renovação de BI em postos SIAC.</p>
            <div class="hero-actions">
                <a href="marcacao.php" class="btn btn-primary">Fazer Marcação Agora</a>
                <?php if (!$user_logged): ?>
                    <a href="registro.php" class="btn btn-secondary">Ainda não tenho conta</a>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <section class="section container">
        <div class="section-title" style="text-align: center; margin-bottom: 2rem;">
            <h2>Emolumentos (Preços)</h2>
        </div>
        <div class="grid grid-3">
            <div class="service-card">
                <i class="fas fa-file-signature"></i>
                <h3>1ª Via</h3>
                <p class="price">3.500 Kz</p>
                <small>Isento para menores de 5 anos</small>
            </div>
            <div class="service-card">
                <i class="fas fa-sync-alt"></i>
                <h3>Renovação</h3>
                <p class="price">2.500 Kz</p>
                <small>Dentro do prazo de validade</small>
            </div>
            <div class="service-card">
                <i class="fas fa-copy"></i>
                <h3>2ª Via</h3>
                <p class="price">5.000 Kz</p>
                <small>Por perda ou extravio</small>
            </div>
        </div>
    </section>
</main>

<footer class="footer">
    <div class="container">
        <p>© 2026 SIAC/DNAICC - Angola. Todos os direitos reservados.</p>
    </div>
</footer>

</body>
</html>