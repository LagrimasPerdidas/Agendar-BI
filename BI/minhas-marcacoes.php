<?php
session_start();
require_once 'db.php'; // Garante que a estrutura do banco esteja pronta

// Proteção de rota: Se não houver ID na sessão, volta para o login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_nome = $_SESSION['user_nome'];
?>
<!DOCTYPE html>
<html lang="pt-AO">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Minhas Marcações - SIAC | Angola</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .status-tag { padding: 4px 8px; border-radius: 4px; font-size: 0.75rem; font-weight: bold; text-transform: uppercase; }
        .status-pendente { background: #fef3c7; color: #92400e; }
        .status-confirmada { background: #d1fae5; color: #065f46; }
        .status-concluida { background: #dcfce7; color: #166534; }
        .status-cancelada { background: #fee2e2; color: #991b1b; }
        .btn-sm { padding: 0.4rem 0.8rem; font-size: 0.8rem; cursor: pointer; }
        .hidden { display: none !important; }
        
        /* Modal Style */
        .modal-overlay { position: fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); display:none; align-items:center; justify-content:center; z-index: 1000; }
        .modal { background: white; padding: 2rem; border-radius: 8px; max-width: 500px; width: 90%; }
    </style>
</head>
<body>

<header class="header">
    <div class="header-main">
        <div class="container header-content">
            <a href="index.php" class="logo">
                <div class="logo-icon">🇦🇴</div>
                <div class="logo-text">
                    <span class="logo-title">SIAC</span>
                    <span class="logo-subtitle">Portal do Cidadão</span>
                </div>
            </a>
            <nav class="nav">
                <a href="index.php" class="nav-link">Início</a>
                <a href="marcacao.php" class="nav-link">Nova Marcação</a>
                <a href="minhas-marcacoes.php" class="nav-link active">Minhas Marcações</a>
                <a href="logout.php" class="nav-link" style="color: #e74c3c;">Sair</a>
            </nav>
        </div>
    </div>
</header>

<main class="container" style="max-width: 1000px; padding: 2rem 1.5rem;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; flex-wrap: wrap; gap: 1rem;">
        <div>
            <h2 style="margin: 0;">Olá, <?= htmlspecialchars($user_nome) ?></h2>
            <p style="color: var(--gray-500); margin: 0.5rem 0 0;">Estes são os seus agendamentos no sistema.</p>
        </div>
        <a href="marcacao.php" class="btn btn-primary"><i class="fas fa-plus"></i> Nova Marcação</a>
    </div>

    <div class="card" style="margin-bottom: 2rem;">
        <div class="card-body" style="display: flex; gap: 1rem; align-items: flex-end;">
            <div style="flex: 1;">
                <label class="form-label">Filtrar por Status</label>
                <select id="filtroStatus" class="form-input" onchange="filtrarMarcacoes()">
                    <option value="">Todos os estados</option>
                    <option value="pendente">Pendente</option>
                    <option value="confirmada">Confirmada</option>
                    <option value="concluida">Concluída</option>
                    <option value="cancelada">Cancelada</option>
                </select>
            </div>
            <button class="btn btn-outline" onclick="carregarMarcacoes()"><i class="fas fa-sync"></i> Atualizar</button>
        </div>
    </div>

    <div id="listaMarcacoes">
        <div style="text-align: center; padding: 2rem;">
            <i class="fas fa-spinner fa-spin fa-2x"></i>
            <p>Carregando seus dados...</p>
        </div>
    </div>

    <div id="semMarcacoes" class="hidden" style="text-align: center; padding: 4rem; background: white; border-radius: 8px;">
        <i class="fas fa-calendar-times fa-4x" style="color: #ccc; margin-bottom: 1rem;"></i>
        <h3>Nenhuma marcação encontrada</h3>
        <p>Você ainda não realizou nenhum agendamento para o BI.</p>
        <a href="marcacao.php" class="btn btn-primary" style="margin-top: 1rem;">Agendar BI agora</a>
    </div>
</main>

<div class="modal-overlay" id="modalCancelar">
    <div class="modal">
        <h3><i class="fas fa-exclamation-triangle" style="color: #e74c3c;"></i> Cancelar Agendamento?</h3>
        <p style="margin: 1rem 0;">Esta ação não pode ser desfeita e a vaga será liberada para outro cidadão.</p>
        <input type="hidden" id="idParaCancelar">
        <div style="display: flex; gap: 10px; justify-content: flex-end;">
            <button class="btn btn-outline" onclick="fecharModais()">Manter Agendamento</button>
            <button class="btn btn-danger" onclick="confirmarCancelamento()">Confirmar Cancelamento</button>
        </div>
    </div>
</div>

<script>
    let todasMarcacoes = [];

    async function carregarMarcacoes() {
        try {
            const res = await fetch('api/marcacoes.php');
            const json = await res.json();
            if(json.success) {
                todasMarcacoes = json.data;
                renderizar(todasMarcacoes);
            }
        } catch (err) {
            document.getElementById('listaMarcacoes').innerHTML = "<p>Erro ao conectar com o servidor.</p>";
        }
    }

    function renderizar(lista) {
        const container = document.getElementById('listaMarcacoes');
        const vazio = document.getElementById('semMarcacoes');
        
        if (!lista || lista.length === 0) {
            container.innerHTML = '';
            vazio.classList.remove('hidden');
            return;
        }

        vazio.classList.add('hidden');
        container.innerHTML = lista.map(m => `
            <div class="card" style="margin-bottom:1rem; border-left: 5px solid ${m.status === 'cancelada' ? '#ef4444' : '#004494'}">
                <div class="card-body" style="display:flex; justify-content:space-between; align-items:center; flex-wrap: wrap; gap: 1rem;">
                    <div>
                        <small style="color:var(--gray-500)">Ticket: <strong>${m.codigo}</strong></small>
                        <h4 style="margin:5px 0">${m.servico}</h4>
                        <p><i class="far fa-calendar"></i> ${m.data} às ${m.horario}</p>
                        <p style="font-size: 0.85rem; color: #666;"><i class="fas fa-map-marker-alt"></i> ${m.posto}</p>
                        <span class="status-tag status-${m.status}">${m.status}</span>
                    </div>
                    <div style="display:flex; gap:10px;">
                        <button class="btn btn-outline btn-sm" onclick="alert('Funcionalidade de impressão em breve!')"><i class="fas fa-print"></i></button>
                        ${m.status !== 'cancelada' && m.status !== 'concluida' ? `
                            <button class="btn btn-danger btn-sm" onclick="abrirCancelamento(${m.id})">Cancelar</button>
                        ` : ''}
                    </div>
                </div>
            </div>
        `).join('');
    }

    function filtrarMarcacoes() {
        const status = document.getElementById('filtroStatus').value;
        const filtradas = status ? todasMarcacoes.filter(m => m.status === status) : todasMarcacoes;
        renderizar(filtradas);
    }

    function abrirCancelamento(id) {
        document.getElementById('idParaCancelar').value = id;
        document.getElementById('modalCancelar').style.display = 'flex';
    }

    function fecharModais() {
        document.getElementById('modalCancelar').style.display = 'none';
    }

    async function confirmarCancelamento() {
        const id = document.getElementById('idParaCancelar').value;
        try {
            const res = await fetch('api/marcacoes.php', {
                method: 'DELETE', // Ou PUT com body action:cancelar
                body: JSON.stringify({ id: id })
            });
            const result = await res.json();
            if(result.success) {
                fecharModais();
                carregarMarcacoes();
            }
        } catch (e) { alert("Erro ao cancelar."); }
    }

    window.onload = carregarMarcacoes;
</script>

</body>
</html>