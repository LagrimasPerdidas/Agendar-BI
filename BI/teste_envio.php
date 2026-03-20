<?php
require_once 'emails/mailer.php';

// O primeiro parâmetro NÃO pode estar vazio
$emailDestino = 'seu-email@exemplo.com'; 

$teste = enviarEmail($emailDestino, 'Teste SIAC', 'A autenticação funcionou! Agora o e-mail chegou.');

if ($teste['success']) {
    echo "<h1>✅ Agora sim!</h1><p>Verifique a sua Inbox no Mailtrap.</p>";
} else {
    echo "<h1>❌ Erro de Destinatário</h1><p>Detalhes: " . $teste['error'] . "</p>";
}