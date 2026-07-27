<?php
require_once __DIR__ . '/../vendor/autoload.php';

// Logger simple para depuración
class SimpleFileLogger extends \Psr\Log\AbstractLogger {
    private $file;
    public function __construct($file) { $this->file = $file; }
    public function log($level, $message, array $context = []): void {
        file_put_contents($this->file, sprintf("[%s] %s: %s\n", date('Y-m-d H:i:s'), strtoupper($level), $message), FILE_APPEND);
    }
}

$logger = new SimpleFileLogger('/tmp/phpcas.log');
phpCAS::setLogger($logger);

$cas_host = getenv('CAS_HOSTNAME') ?: 'guri.ceip.edu.uy';
$cas_port = (int)(getenv('CAS_PORT') ?: 443);
$cas_uri  = getenv('CAS_URI') ?: '/cas';

// 1. Inicializar cliente CAS 2.0 (SOLO 4 PARÁMETROS BASE)
phpCAS::client("2.0", $cas_host, $cas_port, $cas_uri, 'https://infraestructura.dgeip.edu.uy');

// 2. Definir la URL del servicio (aquí es donde se configura la URL de tu app)
phpCAS::setFixedServiceURL('https://infraestructura.dgeip.edu.uy');

// 3. Omitir validación SSL del servidor CAS
phpCAS::setNoCasServerValidation();

// 4. Forzar la autenticación SSO
phpCAS::forceAuthentication();

$cas_user = phpCAS::getUser();

if (!$cas_user) {
    die("Error: No se pudo obtener el usuario desde el servidor CAS SSO.");
}

// 5. Conexión y registro en PostgreSQL 17
require_once __DIR__ . '/db.php';

$stmt = $pdo->prepare("SELECT u.*, r.nombre as rol_nombre FROM usuarios u LEFT JOIN roles r ON u.rol_id = r.id WHERE u.username = :username");
$stmt->execute(['username' => $cas_user]);
$user = $stmt->fetch();

if (!$user) {
    $stmt = $pdo->prepare("INSERT INTO usuarios (username, rol_id) VALUES (:username, 1) RETURNING *");
    $stmt->execute(['username' => $cas_user]);
    $user = $stmt->fetch();
    $user['rol_nombre'] = 'admin';
}

$_SESSION['user'] = [
    'username' => $user['username'],
    'rol'      => $user['rol_nombre'] ?? 'admin'
];
