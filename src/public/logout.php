<?php
session_start();

require_once __DIR__ . '/../vendor/autoload.php';

$cas_host = getenv('CAS_HOSTNAME') ?: 'guri.ceip.edu.uy';
$cas_port = (int)(getenv('CAS_PORT') ?: 443);
$cas_uri  = getenv('CAS_URI') ?: '/cas';
$app_url  = 'https://infraestructura.dgeip.edu.uy';

// Inicializar cliente CAS usando la firma correcta (5 parámetros)
phpCAS::client("2.0", $cas_host, $cas_port, $cas_uri, $app_url);

// Destruir la sesión local de PHP
$_SESSION = [];
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}
session_destroy();

// Redirigir al logout del servidor CAS y regresar a la aplicación
phpCAS::logoutWithRedirectService($app_url);
