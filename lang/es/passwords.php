<?php

/**
 * Mensajes del flujo de recuperacion de contraseña.
 *
 * El resto del sistema esta en español pero estas cadenas las emite Laravel, y
 * sin este archivo salian en ingles ("We have emailed your password reset
 * link.") justo en la pantalla de recuperar acceso.
 *
 * Lo que no este traducido cae en el idioma de respaldo (en), asi que no hace
 * falta duplicar todo el paquete de idiomas.
 */
return [
    'reset' => 'Tu contraseña ha sido restablecida.',
    'sent' => 'Te enviamos un enlace para restablecer tu contraseña.',
    'throttled' => 'Espera un momento antes de volver a intentarlo.',
    'token' => 'El enlace de recuperación no es válido o ya expiró.',
    'user' => 'No encontramos ninguna cuenta con ese correo electrónico.',
];
