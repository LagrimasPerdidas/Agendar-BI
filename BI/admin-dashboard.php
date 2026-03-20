<?php
session_start();
require_once 'db.php';

// Proteção de acesso: Apenas administradores entram aqui
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

$nomeAdmin = $_SESSION['user_nome'] ?? "Administrador";
?>
<!DOCTYPE html>
<html lang="pt-AO">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel Administrativo - SIAC DNAICC</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root { 
            --primary: #004a99; 
            --secondary: #f4f7f6; 
            --text: #333;
            --success: #27ae60;
            --danger: #e74c3c;
            --warning: #f39c12;
        }
        
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; margin: 0; background: var(--secondary); color: var(--text); }
        
        .admin-layout { display: flex; min-height: 100vh; }
        
        /* Sidebar */
        .admin-sidebar { width: 260px; background: var(--primary); color: white; display: flex; flex-direction: column; position: fixed; height: 100vh; z-index: 100; }
        .admin-sidebar-header { padding: 30px 20px; text-align: center; border-bottom: 1px solid rgba(255,255,255,0.1); }
        .admin-sidebar-nav { flex: 1; padding: 20px 0; }
        .admin-sidebar-nav a { display: block; padding: 15px 25px; color: rgba(255,255,255,0.8); text-decoration: none; transition: 0.3s; cursor: pointer; border-left: 4px solid transparent; }
        .admin-sidebar-nav a:hover, .admin-sidebar-nav a.active { background: rgba(255,255,255,0.1); color: white; border-left-color: #fff; }
        .admin-sidebar-nav hr { border: 0; border-top: 1px solid rgba(255,255,255,0.1); margin: 20px 0; }
        
        /* Main Content */
        .admin-main { flex: 1; margin-left: 260px; padding: 40px; }
        .admin-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; border-bottom: 2px solid #ddd; padding-bottom: 15px; }
        
        /* Stats Cards */
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 40px; }
        .card { background: white; padding: 25px; border-radius: 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); text-align: center; }
        .card h4 { margin: 0; color: #666; font-size: 13px; text-transform: uppercase; letter-spacing: 1px; }
        .card span { display: block; font-size: 32px; font-weight: bold; color: var(--primary); margin-top: 10px; }

        /* Tables */
        .table-container { background: white; border-radius: 10px; padding: 20px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th { background: #f8f9fa; padding: 15px; text-align: left; border-bottom: 2px solid #eee; color: #555; font-size: 0.9rem; }
        td { padding: 15px; border-bottom: 1px solid #eee; font-size: 0.9rem; }
        
        /* Status Badges */
        .status-badge { padding: 4px 8px; border-radius: 4px; font-size: 11px; font-weight: bold; text-transform: uppercase; }
        .status-pendente { background: #fff3cd; color: #856404; }
        .status-confirmada { background: #d1fae5; color: #065f46; }
        .status-cancelada { background: #fee2e2; color: #991b1b; }
        
        /* Buttons sm */
        .btn-action { padding: 5px 10px; border: none; border-radius: 4px; cursor: pointer; font-size: 12px; margin-right: 5px; transition: 0.2s; }
        .btn-confirm { background: var(--success); color: white; }
        .btn-cancel { background: var(--danger); color: white; }
        .btn-confirm:hover { background: #219150; }
        
        .hidden { display: none; }
        .badge-admin { background: #e74c3c; color: white; padding: 3px 10px; border-radius: 20px; font-size: 11px; margin-left: 10px; vertical-align: middle; }
    </style>
</head>
<body>

<div class="admin-layout">

    <aside class="admin-sidebar">
        <div class="admin-sidebar-header">
            <h2 style="margin:0;">SIAC PIV</h2>
            <small style="opacity: 0.7;">Painel de Gestão DNAICC</small>
        </div>
        <nav class="admin-sidebar-nav">
            <a onclick="showSection('dashboard')" id="nav-dashboard" class="active"><i class="fas fa-chart-line"></i> Dashboard</a>
            <a onclick="showSection('marcacoes')" id="nav-marcacoes"><i class="fas fa-calendar-check"></i> Marcações</a>
            <a onclick="showSection('postos')" id="nav-postos"><i class="fas fa-building"></i> Postos SIAC</a>
            <a onclick="showSection('usuarios')" id="nav-usuarios"><i class="fas fa-users"></i> Utilizadores</a>
            <hr>
            <a href="logout.php" style="color: #ff7675;"><i class="fas fa-sign-out-alt"></i> Terminar Sessão</a>
        </nav>
    </aside>

    <main class="admin-main">
        <header class="admin-header">
            <div>
                <h2 id="tituloSeccao" style="margin:0; color: var(--primary);">DASHBOARD</h2>
            </div>
            <div class="user-info">
                <span>Olá, <strong><?= htmlspecialchars($nomeAdmin) ?></strong></span>
                <span class="badge-admin">ADMINISTRADOR</span>
            </div>
        </header>

        <section id="dashboard">
            <div class="stats-grid">
                <div class="card">
                    <h4>Total de Marcações</h4>
                    <span id="statTotal">0</span>
                </div>
                <div class="card">
                    <h4>Pendentes</h4>
                    <span id="statPendentes" style="color: var(--warning);">0</span>
                </div>
                <div class="card">
                    <h4>Confirmadas</h4>
                    <span id="statConcluidas" style="color: var(--success);">0</span>
                </div>
            </div>
            <h3>Atividade Recente</h3>
            <div class="table-container">
                <p style="color: #666;">Selecione uma categoria no menu lateral para realizar operações de gestão.</p>
            </div>
        </section>

        <section id="marcacoes" class="hidden">
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Código</th>
                            <th>Cidadão</th>
                            <th>Serviço</th>
                            <th>Data/Hora</th>
                            <th>Status</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody id="listaMarcacoes">
                        </tbody>
                </table>
            </div>
        </section>

        <section id="postos" class="hidden">
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Nome do Posto</th>
                            <th>Localização</th>
                            <th>Contacto</th>
                        </tr>
                    </thead>
                    <tbody id="listaPostos"></tbody>
                </table>
            </div>
        </section>

        <section id="usuarios" class="hidden">
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Nome Completo</th>
                            <th>E-mail</th>
                            <th>NIF</th>
                            <th>Perfil</th>
                        </tr>
                    </thead>
                    <tbody id="listaUsuarios"></tbody>
                </table>
            </div>
        </section>

    </main>
</div>

<script>
// Navegação entre secções
function showSection(sectionId) {
    document.querySelectorAll("section").forEach(s => s.classList.add("hidden"));
    document.querySelectorAll(".admin-sidebar-nav a").forEach(a => a.classList.remove("active"));
    
    document.getElementById(sectionId).classList.remove("hidden");
    document.getElementById("nav-" + sectionId).classList.add("active");
    document.getElementById("tituloSeccao").innerText = sectionId.toUpperCase();
    
    // Recarregar dados
    if(sectionId === 'dashboard') carregarDashboard();
    if(sectionId === 'marcacoes') carregarMarcacoes();
    if(sectionId === 'postos') carregarPostos();
    if(sectionId === 'usuarios') carregarUsuarios();
}

// 1. Estatísticas do Dashboard
async function carregarDashboard() {
    const res = await fetch("api/dashboard.php");
    const json = await res.json();
    if(json.success) {
        document.getElementById("statTotal").innerText = json.data.total;
        document.getElementById("statPendentes").innerText = json.data.pendentes;
        document.getElementById("statConcluidas").innerText = json.data.concluidas;
    }
}

// 2. Lista de Marcações com Botões de Ação
async function carregarMarcacoes() {
    const res = await fetch("api/marcacoes.php?admin=true"); // Passamos flag para ver todas
    const json = await res.json();
    if(json.success) {
        const html = json.data.map(m => `
            <tr>
                <td><strong>${m.codigo}</strong></td>
                <td>${m.nome_usuario || 'N/D'}</td>
                <td>${m.servico}</td>
                <td>${m.data} - ${m.horario}</td>
                <td><span class="status-badge status-${m.status}">${m.status}</span></td>
                <td>
                    ${m.status === 'pendente' ? `
                        <button class="btn-action btn-confirm" onclick="alterarStatus('${m.id}', 'confirmada')">Confirmar</button>
                        <button class="btn-action btn-cancel" onclick="alterarStatus('${m.id}', 'cancelada')">Cancelar</button>
                    ` : '<small style="color:#999">Sem ações</small>'}
                </td>
            </tr>
        `).join("");
        document.getElementById("listaMarcacoes").innerHTML = html || "<tr><td colspan='6'>Nenhuma marcação encontrada.</td></tr>";
    }
}

// Função para Confirmar/Cancelar via API
async function alterarStatus(id, novoStatus) {
    if(!confirm(`Deseja alterar o status para ${novoStatus}?`)) return;

    const res = await fetch("api/marcacoes.php", {
        method: 'PUT',
        body: JSON.stringify({ id: id, action: novoStatus })
    });
    const result = await res.json();
    if(result.success) {
        carregarMarcacoes();
        carregarDashboard();
    }
}

// 3. Postos SIAC
async function carregarPostos() {
    const res = await fetch("api/postos.php");
    const json = await res.json();
    if(json.success) {
        const html = json.data.map(p => `
            <tr>
                <td><strong>${p.nome}</strong></td>
                <td>${p.endereco}</td>
                <td>${p.telefone || 'N/A'}</td>
            </tr>
        `).join("");
        document.getElementById("listaPostos").innerHTML = html;
    }
}

// 4. Utilizadores
async function carregarUsuarios() {
    const res = await fetch("api/usuarios.php");
    const json = await res.json();
    if(json.success) {
        const html = json.data.map(u => `
            <tr>
                <td>${u.nome}</td>
                <td>${u.email}</td>
                <td>${u.nif || 'N/A'}</td>
                <td><span class="badge-admin" style="background:${u.role === 'admin' ? '#e74c3c' : '#3498db'}">${u.role.toUpperCase()}</span></td>
            </tr>
        `).join("");
        document.getElementById("listaUsuarios").innerHTML = html;
    }
}

window.onload = carregarDashboard;
</script>

</body>
</html>