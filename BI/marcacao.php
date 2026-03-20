<?php
session_start();
require_once 'db.php'; // Usa o db.php da raiz que criamos

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$database = DB::connect();

try {
    $servicosDB = $database->query("SELECT * FROM servicos WHERE ativo = 1")->fetchAll();
    $postosDB = $database->query("SELECT * FROM postos WHERE ativo = 1")->fetchAll();
} catch (Exception $e) {
    die("Erro ao carregar dados: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="pt-AO">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nova Marcação - SIAC</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .hidden { display: none; }
        .selected { border: 2px solid var(--primary) !important; background: #f0f7ff; }
        .service-card, .info-card { cursor: pointer; transition: 0.3s; border: 1px solid #ddd; padding: 15px; border-radius: 8px; margin-bottom: 10px; }
        .slot-badge { padding: 4px 8px; border-radius: 4px; font-size: 0.8rem; font-weight: bold; }
        .slot-available { background: #dcfce7; color: #166534; }
        .slot-full { background: #fee2e2; color: #991b1b; }
    </style>
</head>
<body>

<div class="container" style="max-width: 800px; margin-top: 2rem;">
    <div class="card">
        <div class="card-header">
            <h3><i class="fas fa-calendar-alt"></i> Agendamento de BI</h3>
        </div>
        <div class="card-body">
            <form id="marcacaoForm">
                
                <div id="step1" class="form-step">
                    <h4>1. Selecione o Serviço</h4>
                    <div class="grid grid-3" style="margin: 1.5rem 0;">
                        <?php foreach($servicosDB as $s): ?>
                        <div class="service-card" onclick="selectServico(<?= $s['id'] ?>, '<?= $s['nome'] ?>', <?= $s['preco'] ?>, this)">
                            <i class="fas fa-file-alt fa-2x"></i>
                            <h5 style="margin-top:10px"><?= $s['nome'] ?></h5>
                            <p style="font-size:0.9rem"><?= number_format($s['preco'], 0, ',', '.') ?> Kz</p>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <input type="hidden" name="servico_id" id="servicoId" required>
                    <div style="text-align: right;">
                        <button type="button" class="btn btn-primary" id="btnStep1" onclick="nextStep(2)" disabled>Próximo <i class="fas fa-arrow-right"></i></button>
                    </div>
                </div>

                <div id="step2" class="form-step hidden">
                    <h4>2. Escolha o Posto SIAC</h4>
                    <div id="listaPostos" style="margin: 1.5rem 0;">
                        <?php foreach($postosDB as $p): ?>
                        <div class="info-card" onclick="selectPosto(<?= $p['id'] ?>, '<?= $p['nome'] ?>', this)">
                            <h5><?= $p['nome'] ?></h5>
                            <p><i class="fas fa-map-marker-alt"></i> <?= $p['endereco'] ?></p>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <input type="hidden" name="posto_id" id="postoId" required>
                    <div style="display:flex; justify-content: space-between;">
                        <button type="button" class="btn btn-outline" onclick="prevStep(1)">Voltar</button>
                        <button type="button" class="btn btn-primary" id="btnStep2" onclick="nextStep(3)" disabled>Próximo</button>
                    </div>
                </div>

                <div id="step3" class="form-step hidden">
                    <h4>3. Data e Disponibilidade</h4>
                    <div style="margin: 1.5rem 0;">
                        <label>Selecione a Data:</label>
                        <input type="date" name="data" id="inputData" class="form-input" min="<?= date('Y-m-d') ?>" onchange="verificarVagas()">
                        
                        <div id="statusVagas" style="margin-top: 1rem;"></div>
                        
                        <div id="campoHorario" class="hidden" style="margin-top: 1rem;">
                            <label>Escolha o Horário:</label>
                            <select name="horario" id="horario" class="form-input">
                                <option value="08:00">08:00</option>
                                <option value="10:00">10:00</option>
                                <option value="13:00">13:00</option>
                                <option value="15:00">15:00</option>
                            </select>
                        </div>
                    </div>
                    <div style="display:flex; justify-content: space-between;">
                        <button type="button" class="btn btn-outline" onclick="prevStep(2)">Voltar</button>
                        <button type="button" class="btn btn-primary" id="btnStep3" onclick="nextStep(4)" disabled>Próximo</button>
                    </div>
                </div>

                <div id="step4" class="form-step hidden">
                    <h4>4. Confirmar Dados</h4>
                    <div class="info-card" style="background: #f9f9f9;">
                        <p><strong>Cidadão:</strong> <?= $_SESSION['user_nome'] ?></p>
                        <p><strong>Serviço:</strong> <span id="resumoServico"></span></p>
                        <p><strong>Local:</strong> <span id="resumoPosto"></span></p>
                        <p><strong>Data:</strong> <span id="resumoData"></span> às <span id="resumoHorario"></span></p>
                        <hr>
                        <p><strong>Total a Pagar:</strong> <span id="resumoPreco"></span> Kz</p>
                    </div>
                    <div style="display:flex; justify-content: space-between; margin-top: 1.5rem;">
                        <button type="button" class="btn btn-outline" onclick="prevStep(3)">Corrigir</button>
                        <button type="submit" class="btn btn-primary" style="background: #27ae60;">Confirmar Agendamento</button>
                    </div>
                </div>

            </form>
        </div>
    </div>
</div>

<script>
const state = { servicoId: null, servicoNome: '', preco: 0, postoId: null, postoNome: '' };

function selectServico(id, nome, preco, el) {
    state.servicoId = id; state.servicoNome = nome; state.preco = preco;
    document.getElementById('servicoId').value = id;
    document.querySelectorAll('.service-card').forEach(c => c.classList.remove('selected'));
    el.classList.add('selected');
    document.getElementById('btnStep1').disabled = false;
}

function selectPosto(id, nome, el) {
    state.postoId = id; state.postoNome = nome;
    document.getElementById('postoId').value = id;
    document.querySelectorAll('.info-card').forEach(c => c.classList.remove('selected'));
    el.classList.add('selected');
    document.getElementById('btnStep2').disabled = false;
    // Se a data já estiver preenchida, revalida vagas para o novo posto
    if(document.getElementById('inputData').value) verificarVagas();
}

async function verificarVagas() {
    const data = document.getElementById('inputData').value;
    if(!data || !state.postoId) return;

    const divStatus = document.getElementById('statusVagas');
    divStatus.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Verificando postos...';

    try {
        const response = await fetch(`api/vagas.php?posto_id=${state.postoId}&data=${data}`);
        const res = await response.json();

        if(res.vagas_disponiveis > 0) {
            divStatus.innerHTML = `<span class="slot-badge slot-available">✓ ${res.vagas_disponiveis} vagas disponíveis</span>`;
            document.getElementById('campoHorario').classList.remove('hidden');
            document.getElementById('btnStep3').disabled = false;
        } else {
            divStatus.innerHTML = `<span class="slot-badge slot-full">✗ Esgotado para este dia</span>`;
            document.getElementById('campoHorario').classList.add('hidden');
            document.getElementById('btnStep3').disabled = true;
        }
    } catch(e) {
        divStatus.innerHTML = "Erro ao consultar vagas.";
    }
}

function nextStep(n) {
    document.querySelectorAll('.form-step').forEach(s => s.classList.add('hidden'));
    document.getElementById('step' + n).classList.remove('hidden');
    if(n === 4) {
        document.getElementById('resumoServico').innerText = state.servicoNome;
        document.getElementById('resumoPosto').innerText = state.postoNome;
        document.getElementById('resumoData').innerText = document.getElementById('inputData').value;
        document.getElementById('resumoHorario').innerText = document.getElementById('horario').value;
        document.getElementById('resumoPreco').innerText = state.preco.toLocaleString();
    }
}

function prevStep(n) {
    document.querySelectorAll('.form-step').forEach(s => s.classList.add('hidden'));
    document.getElementById('step' + n).classList.remove('hidden');
}

document.getElementById('marcacaoForm').onsubmit = async (e) => {
    e.preventDefault();
    const formData = new FormData(e.target);
    
    // Mostra um alerta de processamento
    const btn = e.target.querySelector('button[type="submit"]');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Agendando...';

    try {
        const response = await fetch('api/marcacoes.php', { method: 'POST', body: formData });
        const res = await response.json();
        if(res.success) {
            alert("Agendamento realizado com sucesso! Código: " + res.codigo);
            window.location.href = 'minhas-marcacoes.php';
        } else {
            alert("Erro: " + res.message);
            btn.disabled = false;
            btn.innerText = 'Confirmar Agendamento';
        }
    } catch(err) {
        alert("Falha na comunicação com o servidor.");
        btn.disabled = false;
    }
};
</script>
</body>
</html>