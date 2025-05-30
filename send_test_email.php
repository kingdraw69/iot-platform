<?php

require __DIR__ . '/vendor/autoload.php';

use Illuminate\Support\Facades\Mail;

try {
    echo "Iniciando envío de correo...\n";

    Mail::send('emails.alert', [
        'device' => 'Sensor X',
        'location' => 'Sala 1',
        'sensor' => 'Temperatura',
        'message' => 'Alerta de peligro',
        'value' => '100'
    ], function ($message) {
        $message->to('juniorrincon1992@hotmail.com')
                ->subject('Prueba de correo');
    });

    echo "Correo enviado exitosamente.\n";
} catch (Exception $e) {
    echo "Error al enviar el correo: " . $e->getMessage() . "\n";
}

?>
