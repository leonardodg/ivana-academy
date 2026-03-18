<?php
// Load Composer's autoloader
require 'PHPMailer/Exception.php';
require 'PHPMailer/PHPMailer.php';
require 'PHPMailer/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

$mail = new PHPMailer(true);

try {
    // --- CONFIGURAÇÕES DA SUA IMAGEM DO MOODLE ---
    $mail->SMTPDebug = SMTP::DEBUG_SERVER;         // Habilita log detalhado para ver o erro real
    $mail->isSMTP();                               // Define que vai usar SMTP
    $mail->Host       = 'smtp.gmail.com';          // SMTP hosts
    $mail->SMTPAuth   = true;                      // SMTP Auth Type: LOGIN
    $mail->Username   = 'academy.ivana@gmail.com'; // SMTP username
    $mail->Password   = 'password'; // Senha de App do Google (MUITO IMPORTANTE)
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; // SMTP security: TLS
    $mail->Port       = 587;                               // TCP port to connect to; use 587 for TLS, 465 for SMTPS

    // Configurações do E-mail
    $mail->setFrom('academy.ivana@gmail.com', 'Ivana Academy');
    $mail->addAddress('academy.ivana@gmail.com');  // Enviar para você mesma para testar

    $mail->isHTML(true);
    $mail->Subject = 'Teste SMTP Externo - PHPMailer Manual';
    $mail->Body    = 'Se voce recebeu este email, as configuracoes da imagem do Moodle estao corretas!';

    $mail->send();
    echo "<h3>SUCESSO: O e-mail foi enviado com as configuracoes do Moodle!</h3>";
} catch (Exception $e) {
    echo "<h3>FALHA AO ENVIAR:</h3>";
    echo "Erro do PHPMailer: {$mail->ErrorInfo}";
}
