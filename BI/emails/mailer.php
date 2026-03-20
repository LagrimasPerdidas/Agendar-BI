<?php
// Carregamento manual da biblioteca
require_once __DIR__ . '/../vendor/phpmailer/Exception.php';
require_once __DIR__ . '/../vendor/phpmailer/PHPMailer.php';
require_once __DIR__ . '/../vendor/phpmailer/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

/**
 * Envia e-mails autenticados via SMTP
 */
function enviarEmail($para, $assunto, $mensagem) {
    // 1. PRIMEIRO criamos a instância
    $mail = new PHPMailer(true);

    try {
        // --- CONFIGURAÇÃO DO SERVIDOR SMTP ---
        $mail->isSMTP();
        $mail->Host       = 'sandbox.smtp.mailtrap.io';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'd8af33e2491300'; 
        $mail->Password   = '998a55e7701d83'; 
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; 
        $mail->Port       = 587; // Se falhar, tente 2525
        $mail->CharSet    = 'UTF-8';

        // --- REMETENTE E DESTINATÁRIO (A ordem correta é aqui) ---
        $mail->setFrom('no-reply@siac.ao', 'SIAC DNAICC');
        $mail->addAddress($para); 

        // --- CONTEÚDO DO E-MAIL ---
        $mail->isHTML(true);
        $mail->Subject = $assunto;
        
        $mail->Body = "
            <div style='font-family: Arial, sans-serif; max-width: 600px; border: 1px solid #ddd; padding: 20px; border-radius: 10px;'>
                <h2 style='color: #004a99; margin-top: 0;'>SIAC DNAICC</h2>
                <div style='background: #f9f9f9; padding: 15px; border-left: 4px solid #004a99; margin: 20px 0;'>
                    <div style='color: #333; font-size: 16px; line-height: 1.6;'>
                        $mensagem
                    </div>
                </div>
                <hr style='border: 0; border-top: 1px solid #eee;'>
                <p style='font-size: 12px; color: #888;'>
                    Este é um e-mail automático enviado pelo sistema de agendamento online. <br>
                    <strong>Por favor, não responda a esta mensagem.</strong> <br>
                    © 2026 SIAC - Serviços Integrados de Atendimento ao Cidadão.
                </p>
            </div>
        ";

        $mail->AltBody = strip_tags($mensagem);

        // Enviar
        $mail->send();
        return ["success" => true];

    } catch (Exception $e) {
        error_log("Erro no envio de e-mail SIAC: {$mail->ErrorInfo}");
        return [
            "success" => false, 
            "error" => "Erro técnico: {$mail->ErrorInfo}"
        ];
    }
}