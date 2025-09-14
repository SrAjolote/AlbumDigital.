<?php
/**
 * Configuración de la base de datos
 * Sistema de Galerías con QR
 */

// Configuración de la base de datos
define('DB_HOST', 'localhost');
define('DB_NAME', 'u845753065_galerianv');
define('DB_USER', 'u845753065_galerianv');
define('DB_PASS', '2415691611+David');
define('DB_CHARSET', 'utf8mb4');

// Configuración general del sistema
define('UPLOAD_DIR', 'uploads/');
define('QR_DIR', 'qr_codes/');
define('MAX_FILE_SIZE', 10 * 1024 * 1024); // 10MB
define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png', 'gif', 'webp']);
// Configuración de BASE_URL - usar siempre el dominio de producción
if (!defined('BASE_URL')) {
    define('BASE_URL', 'https://albumdigital.online');
}

/**
 * Clase para manejo de la base de datos
 */
class Database {
    private static $instance = null;
    private $connection;

    private function __construct() {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $this->connection = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
            ]);
        } catch (PDOException $e) {
            die("Error de conexión: " . $e->getMessage());
        }
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getConnection() {
        return $this->connection;
    }

    public function query($sql, $params = []) {
        try {
            $stmt = $this->connection->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            error_log("Database Query Error: " . $e->getMessage());
            throw $e;
        }
    }

    public function lastInsertId() {
        return $this->connection->lastInsertId();
    }
}

/**
 * Función para limpiar y validar entrada de datos
 */
function sanitize_input($data) {
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
}

/**
 * Función para generar token único
 */
function generate_token($length = 32) {
    return bin2hex(random_bytes($length / 2));
}

/**
 * Función para obtener la IP del usuario
 */
function get_user_ip() {
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        return $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        return $_SERVER['HTTP_X_FORWARDED_FOR'];
    } else {
        return $_SERVER['REMOTE_ADDR'];
    }
}

/**
 * Función para crear directorios necesarios
 */
function create_directories() {
    $dirs = [UPLOAD_DIR, QR_DIR];
    foreach ($dirs as $dir) {
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
    }
}

// Crear directorios al cargar el archivo
create_directories();
?>