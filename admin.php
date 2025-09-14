<?php
/**
 * Panel de administración - CON VERIFICACIÓN SIMPLE POR CONTRASEÑA
 * Sistema de Galerías con QR - CON LÍMITES Y EDICIÓN
 * MODIFICADO: Sin duplicaciones + Mejor diseño de botones
 * Colores: #826948 #F89E9D #F7EEDE
 */

// Activar reporte de errores para debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

define('ADMIN_PASSWORD', 'charlyadmin 2415691611+Charly');

$authenticated = isset($_SESSION['authenticated']) && $_SESSION['authenticated'] === true;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['password'])) {
    if ($_POST['password'] === ADMIN_PASSWORD) {
        $_SESSION['authenticated'] = true;
        $authenticated = true;
    } else {
        $auth_error = "Contraseña incorrecta";
    }
}

if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit();
}

if (!$authenticated) {
    include 'login_form.php';
    exit();
}

// Verificar si los archivos existen antes de incluirlos
if (!file_exists('config/database.php')) {
    die('Error: Archivo config/database.php no encontrado');
}

require_once 'config/database.php';

// ==================== PREVENCIÓN DE DUPLICADOS ====================
// Generar token único para el formulario
if (!isset($_SESSION['form_token'])) {
    $_SESSION['form_token'] = bin2hex(random_bytes(32));
}

function generate_form_token() {
    return bin2hex(random_bytes(32));
}

function verify_form_token($submitted_token) {
    if (!isset($_SESSION['form_token']) || $submitted_token !== $_SESSION['form_token']) {
        return false;
    }
    // Limpiar token después de usar para prevenir resubmisión
    unset($_SESSION['form_token']);
    return true;
}

// ==================== FUNCIONES DE UTILIDAD ====================
function sanitize_input($data) {
    if (!is_string($data)) return '';
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

function generate_token($length = 32) {
    return bin2hex(random_bytes($length));
}

function redirect_after_post($message = '', $type = 'success') {
    $_SESSION['flash_message'] = $message;
    $_SESSION['flash_type'] = $type;
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit();
}

$message = '';
$gallery = null;
$editing_gallery = null;
$db = null;

// Mostrar mensajes flash
if (isset($_SESSION['flash_message'])) {
    $flash_type = $_SESSION['flash_type'] ?? 'success';
    $flash_class = $flash_type === 'error' ? 'alert-danger' : 'alert-success';
    $flash_icon = $flash_type === 'error' ? 'fa-exclamation-circle' : 'fa-check';
    
    $message = '<div class="alert ' . $flash_class . '">
        <i class="fas ' . $flash_icon . '"></i> ' . $_SESSION['flash_message'] . '
    </div>';
    
    unset($_SESSION['flash_message'], $_SESSION['flash_type']);
}

// Intentar conexión a base de datos con manejo de errores
try {
    if (class_exists('Database')) {
        $db = Database::getInstance();
    } else {
        throw new Exception('Clase Database no encontrada');
    }
} catch (Exception $e) {
    $message = '<div class="alert alert-danger">Error de conexión: ' . htmlspecialchars($e->getMessage()) . '</div>';
}

// Configuración de BASE_URL - usar siempre el dominio de producción
if (!defined('BASE_URL')) {
    define('BASE_URL', 'https://albumdigital.online');
}

if (!defined('QR_DIR')) {
    define('QR_DIR', 'qr_codes/');
}

// ==================== EDITAR GALERÍA - CON PREVENCIÓN DE DUPLICADOS ====================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_gallery']) && $db) {
    // Verificar token de formulario
    if (!verify_form_token($_POST['form_token'] ?? '')) {
        redirect_after_post('Error: Envío de formulario inválido o duplicado', 'error');
    }
    
    $galeria_id = (int)$_POST['galeria_id'];
    $nombre_evento = sanitize_input($_POST['nombre_evento'] ?? '');
    $cliente_nombre = sanitize_input($_POST['cliente_nombre'] ?? '');
    $fecha_evento = sanitize_input($_POST['fecha_evento'] ?? '');
    $limite_fotos = 999999999; // Sistema ilimitado
    
    // Validación mejorada
    $errores = [];
    if (empty($nombre_evento)) $errores[] = "El nombre del evento es obligatorio";
    if (empty($cliente_nombre)) $errores[] = "El nombre del cliente es obligatorio";
    if (empty($fecha_evento)) $errores[] = "La fecha del evento es obligatoria";
    // Sin validación de límite - sistema ilimitado
    
    if (empty($errores)) {
        try {
            // Verificar que la galería existe antes de actualizar
            $check_sql = "SELECT id FROM galerias WHERE id = ?";
            $check_stmt = $db->query($check_sql, [$galeria_id]);
            if (!$check_stmt->fetch()) {
                redirect_after_post('Galería no encontrada', 'error');
            }
            
            $sql = "UPDATE galerias SET nombre_evento = ?, cliente_nombre = ?, fecha_evento = ?, limite_fotos = ? WHERE id = ?";
            $db->query($sql, [$nombre_evento, $cliente_nombre, $fecha_evento, $limite_fotos, $galeria_id]);
            
            redirect_after_post('Galería actualizada exitosamente', 'success');
            
        } catch (Exception $e) {
            error_log("Error editando galería: " . $e->getMessage());
            redirect_after_post('Error al actualizar: ' . $e->getMessage(), 'error');
        }
    } else {
        $message = '<div class="alert alert-danger">
            <i class="fas fa-exclamation-circle"></i> Errores de validación:<br>
            • ' . implode("<br>• ", $errores) . '
        </div>';
    }
}

// ==================== CREAR GALERÍA - CON PREVENCIÓN DE DUPLICADOS ====================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_gallery']) && $db) {
    // Verificar token de formulario
    if (!verify_form_token($_POST['form_token'] ?? '')) {
        redirect_after_post('Error: Envío de formulario inválido o duplicado', 'error');
    }
    
    $nombre_evento = sanitize_input($_POST['nombre_evento'] ?? '');
    $cliente_nombre = sanitize_input($_POST['cliente_nombre'] ?? '');
    $fecha_evento = sanitize_input($_POST['fecha_evento'] ?? '');
    $limite_fotos = 999999999; // Sistema ilimitado
    
    // Validación mejorada
    $errores = [];
    if (empty($nombre_evento)) $errores[] = "El nombre del evento es obligatorio";
    if (empty($cliente_nombre)) $errores[] = "El nombre del cliente es obligatorio";
    if (empty($fecha_evento)) $errores[] = "La fecha del evento es obligatoria";
    // Sin validación de límite - sistema ilimitado
    
    if (empty($errores)) {
        try {
            // Verificar duplicados potenciales
            $check_sql = "SELECT id FROM galerias WHERE nombre_evento = ? AND cliente_nombre = ? AND fecha_evento = ?";
            $check_stmt = $db->query($check_sql, [$nombre_evento, $cliente_nombre, $fecha_evento]);
            if ($check_stmt->fetch()) {
                redirect_after_post('Ya existe una galería con los mismos datos', 'error');
            }
            
            $token = generate_token();
            
            $sql = "INSERT INTO galerias (nombre_evento, cliente_nombre, fecha_evento, token, limite_fotos) VALUES (?, ?, ?, ?, ?)";
            $stmt = $db->query($sql, [$nombre_evento, $cliente_nombre, $fecha_evento, $token, $limite_fotos]);
            
            $galeria_id = $db->lastInsertId();
            
            // Generar QR Code con URL limpia
            $gallery_url = rtrim(BASE_URL, '/') . "/galeria.php?token=" . $token;
            $qr_url = "https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=" . urlencode($gallery_url);
            
            if (!is_dir(QR_DIR)) {
                mkdir(QR_DIR, 0755, true);
            }
            
            $qr_filename = "qr_galeria_" . $galeria_id . ".png";
            $qr_path = QR_DIR . $qr_filename;
            
            // Usar cURL para descargar el QR
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $qr_url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            $qr_content = curl_exec($ch);
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($qr_content !== false && $http_code === 200) {
                file_put_contents($qr_path, $qr_content);
                
                $update_sql = "UPDATE galerias SET qr_path = ? WHERE id = ?";
                $db->query($update_sql, [$qr_path, $galeria_id]);
            }
            
            redirect_after_post('¡Galería creada exitosamente! Evento: ' . $nombre_evento . ', Cliente: ' . $cliente_nombre, 'success');
            
        } catch (Exception $e) {
            error_log("Error creando galería: " . $e->getMessage());
            redirect_after_post('Error al crear galería: ' . $e->getMessage(), 'error');
        }
    } else {
        $message = '<div class="alert alert-danger">
            <i class="fas fa-exclamation-circle"></i> Errores de validación:<br>
            • ' . implode("<br>• ", $errores) . '
        </div>';
    }
}

// ==================== OBTENER GALERÍA PARA EDITAR ====================
if (isset($_GET['edit']) && is_numeric($_GET['edit']) && $db) {
    try {
        $galeria_id = (int)$_GET['edit'];
        $sql = "SELECT * FROM galerias WHERE id = ?";
        $stmt = $db->query($sql, [$galeria_id]);
        $editing_gallery = $stmt->fetch();
        
        if (!$editing_gallery) {
            redirect_after_post('Galería no encontrada', 'error');
        }
    } catch (Exception $e) {
        redirect_after_post('Error obteniendo galería: ' . $e->getMessage(), 'error');
    }
}

// ==================== ELIMINAR GALERÍA ====================
if (isset($_GET['delete']) && is_numeric($_GET['delete']) && $db) {
    try {
        $galeria_id = (int)$_GET['delete'];
        
        // Eliminar fotos físicas
        $sql = "SELECT ruta_foto FROM fotos WHERE galeria_id = ?";
        $stmt = $db->query($sql, [$galeria_id]);
        $photos = $stmt->fetchAll();
        
        foreach ($photos as $photo) {
            if (file_exists($photo['ruta_foto'])) {
                unlink($photo['ruta_foto']);
            }
        }
        
        // Eliminar QR físico
        $sql = "SELECT qr_path FROM galerias WHERE id = ?";
        $stmt = $db->query($sql, [$galeria_id]);
        $gallery = $stmt->fetch();
        
        if ($gallery && $gallery['qr_path'] && file_exists($gallery['qr_path'])) {
            unlink($gallery['qr_path']);
        }
        
        // Eliminar registros de base de datos
        $db->query("DELETE FROM foto_likes WHERE foto_id IN (SELECT id FROM fotos WHERE galeria_id = ?)", [$galeria_id]);
        $db->query("DELETE FROM foto_reportes WHERE foto_id IN (SELECT id FROM fotos WHERE galeria_id = ?)", [$galeria_id]);
        $db->query("DELETE FROM fotos WHERE galeria_id = ?", [$galeria_id]);
        $db->query("DELETE FROM galerias WHERE id = ?", [$galeria_id]);
        
        redirect_after_post('Galería eliminada exitosamente', 'success');
        
    } catch (Exception $e) {
        redirect_after_post('Error eliminando galería: ' . $e->getMessage(), 'error');
    }
}

// ==================== OBTENER TODAS LAS GALERÍAS ====================
$galleries = [];
if ($db) {
    try {
        $sql = "SELECT * FROM galerias ORDER BY fecha_creacion DESC";
        $stmt = $db->query($sql);
        $galleries = $stmt->fetchAll();
        
        // Agregar conteos manualmente
        foreach ($galleries as &$gallery) {
            // Contar fotos
            try {
                $foto_sql = "SELECT COUNT(*) as total FROM fotos WHERE galeria_id = ?";
                $foto_stmt = $db->query($foto_sql, [$gallery['id']]);
                $foto_result = $foto_stmt->fetch();
                $gallery['total_fotos'] = $foto_result['total'];
            } catch (Exception $e) {
                $gallery['total_fotos'] = 0;
            }
            
            // Contar likes
            try {
                $like_sql = "SELECT COUNT(*) as total FROM foto_likes fl 
                            JOIN fotos f ON fl.foto_id = f.id 
                            WHERE f.galeria_id = ?";
                $like_stmt = $db->query($like_sql, [$gallery['id']]);
                $like_result = $like_stmt->fetch();
                $gallery['total_likes'] = $like_result['total'];
            } catch (Exception $e) {
                $gallery['total_likes'] = 0;
            }
        }
        
    } catch (Exception $e) {
        $message .= '<div class="alert alert-warning">
            <i class="fas fa-exclamation-triangle"></i> Error obteniendo galerías: ' . htmlspecialchars($e->getMessage()) . '
        </div>';
    }
}

// ==================== ESTADÍSTICAS DE MODERACIÓN ====================
$fotos_reportadas = 0;
$total_fotos_sistema = 0;

if ($db) {
    try {
        // Contar fotos reportadas
        $reportadas_sql = "SELECT COUNT(*) as total FROM fotos WHERE reportada = 1";
        $reportadas_stmt = $db->query($reportadas_sql);
        $reportadas_result = $reportadas_stmt->fetch();
        $fotos_reportadas = $reportadas_result['total'];
        
        // Contar total de fotos en el sistema
        $total_sql = "SELECT COUNT(*) as total FROM fotos";
        $total_stmt = $db->query($total_sql);
        $total_result = $total_stmt->fetch();
        $total_fotos_sistema = $total_result['total'];
        
    } catch (Exception $e) {
        // Error silencioso, continuar sin datos
        error_log("Error obteniendo estadísticas: " . $e->getMessage());
    }
}

// Regenerar token para el próximo formulario
$_SESSION['form_token'] = generate_form_token();

error_log("=== DEBUG FINAL ===");
error_log("Total galerías: " . count($galleries));
error_log("Fotos reportadas: " . $fotos_reportadas);
error_log("Total fotos sistema: " . $total_fotos_sistema);
error_log("Editando galería: " . ($editing_gallery ? 'ID ' . $editing_gallery['id'] : 'No'));
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0, user-scalable=yes">
    <title>Panel de Administración - Galerías QR</title>
    
    <!-- Preload critical resources -->
    <link rel="preload" href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" as="style">
    <link rel="preload" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" as="style">
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    
    <style>
:root {
            --primary-brown: #1a1a1a;
            --primary-pink: #F89E9D;
            --primary-cream: #2a2a2a;
            --shadow-soft: 0 4px 15px rgba(0, 0, 0, 0.3);
            --shadow-medium: 0 8px 25px rgba(0, 0, 0, 0.4);
            --shadow-strong: 0 12px 30px rgba(0, 0, 0, 0.5);
            --gradient-primary: linear-gradient(135deg, #000000 0%, var(--primary-pink) 100%);
            --gradient-soft: linear-gradient(45deg, rgba(0, 0, 0, 0.2), rgba(248, 158, 157, 0.1));
        }
        
        * {
            box-sizing: border-box;
        }
        
        body {
            background: linear-gradient(135deg, #000000 0%, var(--primary-pink) 100%);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            overflow-x: hidden;
            min-height: 100vh;
            color: white;
        }
        
        .navbar-dark {
            background: var(--gradient-primary) !important;
        }
        
        /* Navigation con efecto vidrio esmerilado transparente y texto blanco */
        .navbar {
            background: rgba(0, 0, 0, 0.25) !important;
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
        }

        /* Navbar al hacer scroll */
        .navbar.scrolled {
            background: rgba(0, 0, 0, 0.35) !important;
            backdrop-filter: blur(15px);
            -webkit-backdrop-filter: blur(15px);
            box-shadow: 0 6px 25px rgba(0, 0, 0, 0.4);
        }

        /* Texto blanco para navbar */
        .navbar-brand {
            color: white !important;
            font-weight: 700;
            font-size: 1.5rem;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.5);
        }

        .nav-link {
            color: white !important;
            font-weight: 500;
            margin: 0 10px;
            transition: all 0.3s ease;
            text-shadow: 0 1px 3px rgba(0, 0, 0, 0.5);
        }

        .nav-link:hover {
            color: var(--primary-cream) !important;
            transform: translateY(-2px);
            text-shadow: 0 2px 6px rgba(0, 0, 0, 0.6);
        }

        .navbar-brand:hover {
            color: var(--primary-cream) !important;
            text-shadow: 0 2px 6px rgba(0, 0, 0, 0.6);
        }

        /* Botón hamburguesa blanco en móviles */
        .navbar-toggler {
            border-color: rgba(255, 255, 255, 0.3);
        }

        .navbar-toggler-icon {
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 30 30'%3e%3cpath stroke='rgba%28255, 255, 255, 0.8%29' stroke-linecap='round' stroke-miterlimit='10' stroke-width='2' d='M4 7h22M4 15h22M4 23h22'/%3e%3c/svg%3e");
        }
        
        /* ==================== BOTONES MEJORADOS ==================== */
        .btn {
            border-radius: 15px;
            padding: 12px 24px;
            font-weight: 600;
            transition: all 0.3s cubic-bezier(0.25, 0.46, 0.45, 0.94);
            border: none;
            position: relative;
            overflow: hidden;
            text-transform: none;
            letter-spacing: 0.3px;
        }
        
        .btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
            transition: left 0.5s;
        }
        
        .btn:hover::before {
            left: 100%;
        }
        
        .btn:hover {
            transform: translateY(-3px);
            box-shadow: var(--shadow-medium);
        }
        
        .btn:active {
            transform: translateY(-1px);
        }
        
        .btn-primary {
            background: var(--gradient-primary);
            color: white;
            box-shadow: var(--shadow-soft);
        }
        
        .btn-primary:hover {
            background: linear-gradient(135deg, #333333 0%, #f48b8a 100%);
            color: white;
        }
        
        .btn-success {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
            box-shadow: var(--shadow-soft);
        }
        
        .btn-success:hover {
            background: linear-gradient(135deg, #218838 0%, #1ea085 100%);
            color: white;
        }
        
        .btn-warning {
            background: linear-gradient(135deg, #ffc107 0%, #fd7e14 100%);
            color: #212529;
            box-shadow: var(--shadow-soft);
        }
        
        .btn-warning:hover {
            background: linear-gradient(135deg, #e0a800 0%, #e8590c 100%);
            color: #212529;
        }
        
        .btn-danger {
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
            color: white;
            box-shadow: var(--shadow-soft);
        }
        
        .btn-danger:hover {
            background: linear-gradient(135deg, #c82333 0%, #bd2130 100%);
            color: white;
        }
        
        .btn-info {
            background: linear-gradient(135deg, #17a2b8 0%, #138496 100%);
            color: white;
            box-shadow: var(--shadow-soft);
        }
        
        .btn-info:hover {
            background: linear-gradient(135deg, #138496 0%, #117a8b 100%);
            color: white;
        }
        
        .btn-secondary {
            background: linear-gradient(135deg, #6c757d 0%, #5a6268 100%);
            color: white;
            box-shadow: var(--shadow-soft);
        }
        
        .btn-secondary:hover {
            background: linear-gradient(135deg, #5a6268 0%, #495057 100%);
            color: white;
        }
        
        .btn-outline-primary {
            border: 2px solid #ffffff;
            color: #ffffff;
            background: transparent;
        }
        
        .btn-outline-primary:hover {
            background: var(--gradient-primary);
            border-color: var(--primary-pink);
            color: white;
        }
        
        .btn-sm {
            padding: 8px 16px;
            font-size: 0.85rem;
            border-radius: 12px;
        }
        
        .btn-lg {
            padding: 16px 32px;
            font-size: 1.1rem;
            border-radius: 20px;
        }
        
        /* Botones con iconos mejorados */
        .btn i {
            margin-right: 8px;
            transition: transform 0.3s ease;
        }
        
        .btn:hover i {
            transform: scale(1.1);
        }
        
        /* Grupos de botones mejorados */
        .btn-group .btn {
            border-radius: 0;
            margin: 0;
            border-right: 1px solid rgba(255,255,255,0.2);
        }
        
        .btn-group .btn:first-child {
            border-radius: 15px 0 0 15px;
        }
        
        .btn-group .btn:last-child {
            border-radius: 0 15px 15px 0;
            border-right: none;
        }
        
        .btn-group .btn:only-child {
            border-radius: 15px;
        }
        
        /* ==================== CARDS MEJORADAS ==================== */
        .gallery-card {
            transition: all 0.4s cubic-bezier(0.25, 0.46, 0.45, 0.94);
            border: none;
            border-radius: 20px;
            box-shadow: var(--shadow-soft);
            background: rgba(0, 0, 0, 0.8);
            cursor: pointer;
            position: relative;
            min-height: 200px;
            overflow: hidden;
        }
        
        .gallery-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: var(--gradient-primary);
            transform: scaleX(0);
            transition: transform 0.3s ease;
        }
        
        .gallery-card:hover {
            transform: translateY(-8px);
            box-shadow: var(--shadow-strong);
        }
        
        .gallery-card:hover::before {
            transform: scaleX(1);
        }
        
        .stat-card {
            background: var(--gradient-primary);
            color: white;
            border: none;
            border-radius: 20px;
            box-shadow: var(--shadow-soft);
            transition: all 0.4s cubic-bezier(0.25, 0.46, 0.45, 0.94);
            min-height: 140px;
            position: relative;
            overflow: hidden;
        }
        
        .stat-card::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 100%;
            height: 100%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
            transition: all 0.3s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-5px) scale(1.02);
            box-shadow: var(--shadow-medium);
        }
        
        .stat-card:hover::before {
            top: -25%;
            right: -25%;
        }
        
        .card {
            border: none;
            border-radius: 20px;
            box-shadow: var(--shadow-soft);
            margin-bottom: 1.5rem;
            transition: all 0.3s ease;
            background: rgba(0, 0, 0, 0.8);
            color: white;
        }
        
        .card:hover {
            box-shadow: var(--shadow-medium);
        }
        
        .card-header {
            background: var(--gradient-soft);
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 20px 20px 0 0 !important;
            color: white;
            font-weight: 600;
            padding: 20px;
        }
        
        .card-body {
            padding: 25px;
        }
        
        /* Hero Section */
        .hero-section {
            padding: 120px 0 80px;
            color: white;
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        
        .hero-content h1 {
            font-size: 3.5rem;
            font-weight: 800;
            margin-bottom: 20px;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.5);
            color: white;
        }
        
        .hero-content .lead {
            font-size: 1.3rem;
            margin-bottom: 40px;
            opacity: 0.95;
            color: white;
        }
        
        /* Buttons */
        .btn-primary-custom {
            background: linear-gradient(45deg, #000000, var(--primary-pink));
            border: none;
            border-radius: 50px;
            padding: 15px 40px;
            color: white;
            font-weight: 600;
            font-size: 1.1rem;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
        }
        
        .btn-primary-custom:hover {
            transform: translateY(-3px);
            color: white;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.4);
        }
        
        .btn-outline-custom {
            border: 2px solid white;
            color: white;
            border-radius: 50px;
            padding: 15px 40px;
            font-weight: 600;
            font-size: 1.1rem;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
        }
        
        .btn-outline-custom:hover {
            background: white;
            color: #000000;
            transform: translateY(-3px);
            text-decoration: none;
        }
        
        /* Feature Cards */
        .feature-card {
            background: rgba(0, 0, 0, 0.8);
            border-radius: 20px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
            padding: 40px 30px;
            margin-bottom: 30px;
            height: 100%;
            transition: all 0.3s ease;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .feature-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 50px rgba(0, 0, 0, 0.4);
        }
        
        .feature-icon {
            font-size: 3.5rem;
            color: white;
            margin-bottom: 25px;
            display: block;
        }
        
        .feature-card h4 {
            color: white;
            font-weight: 600;
            margin-bottom: 20px;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.5);
        }
        
        .feature-card p {
            color: white;
            text-shadow: 0 1px 2px rgba(0, 0, 0, 0.3);
        }
        
        /* Steps Section */
        .steps-section {
            background: rgba(0, 0, 0, 0.6);
            border-radius: 30px;
            padding: 60px 40px;
            margin: 80px 0;
            backdrop-filter: blur(15px);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .step-card {
            background: rgba(0, 0, 0, 0.8);
            border-radius: 20px;
            padding: 30px;
            text-align: center;
            height: 100%;
            transition: all 0.3s ease;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .step-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.4);
        }
        
        .step-number {
            background: linear-gradient(45deg, #000000, var(--primary-pink));
            color: white;
            width: 80px;
            height: 80px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 2rem;
            margin: 0 auto 25px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.4);
        }
        
        .step-icon {
            font-size: 2.5rem;
            color: var(--primary-pink);
            margin-bottom: 20px;
        }
        
        .step-title {
            color: white;
            font-weight: 700;
            font-size: 1.5rem;
            margin-bottom: 15px;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.5);
        }
        
        .step-card p {
            color: white;
            text-shadow: 0 1px 2px rgba(0, 0, 0, 0.3);
        }
        
        /* Pricing Section */
        .pricing-section {
            background: rgba(0, 0, 0, 0.6);
            border-radius: 30px;
            padding: 60px 40px;
            margin: 80px 0;
            backdrop-filter: blur(15px);
        }
        
        .pricing-card {
            background: rgba(0, 0, 0, 0.8);
            border-radius: 25px;
            padding: 40px 30px;
            text-align: center;
            height: 100%;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .pricing-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.5);
        }
        
        .pricing-card.featured {
            border: 3px solid var(--primary-pink);
            transform: scale(1.05);
        }
        
        .pricing-card.featured::before {
            content: "MÁS POPULAR";
            position: absolute;
            top: 20px;
            left: -30px;
            background: var(--primary-pink);
            color: white;
            padding: 5px 40px;
            font-size: 12px;
            font-weight: bold;
            transform: rotate(-45deg);
        }
        
        .pricing-title {
            color: white;
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 10px;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.5);
        }
        
        .pricing-photos {
            color: var(--primary-pink);
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 20px;
        }
        
        .pricing-price {
            font-size: 3rem;
            font-weight: 800;
            color: white;
            margin-bottom: 30px;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.5);
        }
        
        .pricing-features {
            list-style: none;
            padding: 0;
            margin-bottom: 30px;
        }
        
        .pricing-features li {
            margin-bottom: 10px;
            color: white;
            text-shadow: 0 1px 2px rgba(0, 0, 0, 0.3);
        }
        
        .pricing-features li i {
            color: var(--primary-pink);
            margin-right: 10px;
        }
        
        /* FAQ Section */
        .faq-section {
            background: rgba(0, 0, 0, 0.8);
            border-radius: 30px;
            padding: 60px 40px;
            margin: 80px 0;
        }
        
        .faq-item {
            margin-bottom: 25px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.2);
            padding-bottom: 25px;
        }
        
        .faq-question {
            color: white;
            font-weight: 600;
            font-size: 1.2rem;
            margin-bottom: 15px;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.5);
        }
        
        .faq-answer {
            color: white;
            line-height: 1.6;
            text-shadow: 0 1px 2px rgba(0, 0, 0, 0.3);
        }
        
        /* Contact Section */
        .contact-section {
            background: rgba(0, 0, 0, 0.6);
            border-radius: 30px;
            padding: 60px 40px;
            margin: 80px 0;
            text-align: center;
            backdrop-filter: blur(15px);
        }
        
        .contact-section h2 {
            color: white;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.5);
        }
        
        .contact-section p {
            color: white;
            text-shadow: 0 1px 2px rgba(0, 0, 0, 0.3);
        }
        
        .whatsapp-btn {
            background: #25D366;
            color: white;
            border-radius: 50px;
            padding: 20px 40px;
            font-size: 1.2rem;
            font-weight: 600;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s ease;
            margin: 0 10px;
        }
        
        .whatsapp-btn:hover {
            background: #128C7E;
            color: white;
            transform: translateY(-3px);
            text-decoration: none;
            box-shadow: 0 10px 25px rgba(37, 211, 102, 0.3);
        }
        
        /* Admin Button */
        .admin-btn {
            position: fixed;
            bottom: 30px;
            right: 30px;
            background: #000000;
            color: white;
            border-radius: 50%;
            width: 60px;
            height: 60px;
            display: flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.4);
            transition: all 0.3s ease;
            z-index: 1000;
        }
        
        .admin-btn:hover {
            background: var(--primary-pink);
            color: white;
            transform: scale(1.1);
            text-decoration: none;
        }
        
        /* Floating Elements */
        .floating-elements {
            position: absolute;
            width: 100%;
            height: 100%;
            overflow: hidden;
            z-index: -1;
        }
        
        .floating-element {
            position: absolute;
            color: rgba(255, 255, 255, 0.1);
            animation: float 8s ease-in-out infinite;
        }
        
        .floating-element:nth-child(1) { top: 10%; left: 10%; animation-delay: 0s; }
        .floating-element:nth-child(2) { top: 20%; left: 80%; animation-delay: 2s; }
        .floating-element:nth-child(3) { top: 60%; left: 5%; animation-delay: 4s; }
        .floating-element:nth-child(4) { top: 70%; left: 85%; animation-delay: 1s; }
        .floating-element:nth-child(5) { top: 40%; left: 70%; animation-delay: 3s; }
        .floating-element:nth-child(6) { top: 80%; left: 30%; animation-delay: 5s; }
        
        @keyframes float {
            0%, 100% { transform: translateY(0px) rotate(0deg); }
            50% { transform: translateY(-30px) rotate(180deg); }
        }
        
        /* Section Titles */
        .section-title {
            color: white;
            font-size: 2.5rem;
            font-weight: 700;
            text-align: center;
            margin-bottom: 50px;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.5);
        }
        
        .section-title-dark {
            color: white;
            font-size: 2.5rem;
            font-weight: 700;
            text-align: center;
            margin-bottom: 50px;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.5);
        }
        
        /* Texto general blanco */
        h1, h2, h3, h4, h5, h6 {
            color: white !important;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.5);
        }
        
        p, span, div, small, label {
            color: white !important;
            text-shadow: 0 1px 2px rgba(0, 0, 0, 0.3);
        }
        
        /* Elementos específicos del admin */
        .text-primary {
            color: white !important;
        }
        
        .text-muted {
            color: #ccc !important;
        }
        
        .form-control {
            background: #1a1a1a;
            border: 1px solid #444;
            color: white;
        }
        
        .form-control:focus {
            border-color: var(--primary-pink);
            box-shadow: 0 0 0 0.25rem rgba(248, 158, 157, 0.25);
            background: #1a1a1a;
            color: white;
        }
        
        .form-select {
            background: #1a1a1a;
            border: 1px solid #444;
            color: white;
        }
        
        .form-select:focus {
            border-color: var(--primary-pink);
            box-shadow: 0 0 0 0.25rem rgba(248, 158, 157, 0.25);
            background: #1a1a1a;
            color: white;
        }
        
        .form-label {
            color: white !important;
        }
        
        .alert {
            border-radius: 20px;
            border: none;
            padding: 20px;
            margin-bottom: 20px;
        }
        
        .alert-success {
            background: linear-gradient(45deg, rgba(40, 167, 69, 0.2), rgba(32, 201, 151, 0.2));
            color: #20c997;
            border: 1px solid rgba(32, 201, 151, 0.3);
        }
        
        .alert-danger {
            background: linear-gradient(45deg, rgba(220, 53, 69, 0.2), rgba(200, 35, 51, 0.2));
            color: #dc3545;
            border: 1px solid rgba(220, 53, 69, 0.3);
        }
        
        .alert-warning {
            background: linear-gradient(45deg, rgba(248, 158, 157, 0.2), rgba(255, 193, 7, 0.2));
            color: var(--primary-pink);
            border: 1px solid rgba(248, 158, 157, 0.3);
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .hero-content h1 {
                font-size: 2.5rem;
            }
            
            .steps-section, .pricing-section, .faq-section, .contact-section {
                padding: 40px 20px;
                margin: 40px 0;
            }
            
            .pricing-card.featured {
                transform: none;
                margin-top: 20px;
            }
            
            .admin-btn {
                bottom: 20px;
                right: 20px;
                width: 50px;
                height: 50px;
            }
        }
        
        /* Ajustes específicos para 2 pasos centrados */
        .steps-section .row.justify-content-center {
            max-width: 900px;
            margin: 0 auto;
            gap: 30px;
        }

        .steps-section .col-md-5 {
            flex: 0 0 auto;
            width: 45%;
        }

        /* Responsivo para móviles */
        @media (max-width: 768px) {
            .steps-section .col-md-5 {
                width: 100%;
                margin-bottom: 30px;
            }
            
            .steps-section .row.justify-content-center {
                gap: 0;
            }
        }
        
        /* Logo con efecto resplandor */
        .navbar-logo {
            height: 90px;
            width: auto;
            transition: all 0.4s ease;
            background: transparent;
            filter: contrast(1.1) brightness(1.05) drop-shadow(0 0 8px rgba(248, 158, 157, 0.3));
            mix-blend-mode: multiply;
        }

        .navbar-logo:hover {
            transform: scale(1.08);
            filter: contrast(1.2) brightness(1.1) drop-shadow(0 0 15px rgba(248, 158, 157, 0.6));
        }

        /* Ajuste responsivo para el logo */
        @media (max-width: 768px) {
            .navbar-logo {
                height: 65px;
            }
        }
        
        /* Logo en el footer */
        .footer-logo {
            height: 90px;
            width: auto;
            transition: all 0.3s ease;
            filter: brightness(1.2) contrast(1.1);
            margin-bottom: 10px;
        }

        .footer-logo:hover {
            transform: scale(1.05);
            filter: brightness(1.3) contrast(1.2) drop-shadow(0 0 10px rgba(255, 255, 255, 0.3));
        }
        
        /* ==================== OTROS ESTILOS ==================== */
        .badge {
            background: var(--gradient-primary) !important;
            border-radius: 20px;
            padding: 8px 16px;
            font-size: 0.75rem;
            font-weight: 600;
            color: white !important;
        }
        
        .badge-limit {
            background: linear-gradient(45deg, #17a2b8, #000000) !important;
        }
        
        .badge-danger {
            background: linear-gradient(45deg, #dc3545, #c82333) !important;
        }
        
        .badge-warning {
            background: linear-gradient(45deg, #ffc107, #e0a800) !important;
            color: #212529 !important;
        }
        
        .qr-code-small {
            max-width: 70px;
            height: auto;
            border-radius: 15px;
            box-shadow: var(--shadow-soft);
            background: white;
            padding: 5px;
        }
        
        .limit-progress {
            height: 8px;
            border-radius: 10px;
            background: #1a1a1a;
            margin-top: 8px;
            overflow: hidden;
        }
        
        .limit-progress-bar {
            height: 100%;
            border-radius: 10px;
            transition: width 0.6s cubic-bezier(0.25, 0.46, 0.45, 0.94);
        }
        
        .limit-progress-bar.safe {
            background: linear-gradient(45deg, #28a745, #20c997);
        }
        
        .limit-progress-bar.warning {
            background: linear-gradient(45deg, #ffc107, #fd7e14);
        }
        
        .limit-progress-bar.danger {
            background: linear-gradient(45deg, #dc3545, #c82333);
        }
        
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #999;
        }
        
        .empty-state i {
            font-size: 4rem;
            margin-bottom: 20px;
            color: var(--primary-pink);
            animation: float 3s ease-in-out infinite;
        }
        
        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
        }
        
        .modal-xl {
            max-width: 95%;
        }

        .stat-mini {
            text-align: center;
            padding: 15px;
            border-radius: 15px;
            background: var(--gradient-soft);
            margin-bottom: 15px;
            transition: all 0.3s ease;
        }

        .stat-mini:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-soft);
        }

        .stat-number {
            font-size: 1.4rem;
            font-weight: bold;
            color: white;
        }

        .stat-label {
            font-size: 0.8rem;
            color: #ccc;
            text-transform: uppercase;
            font-weight: 500;
        }

        .modal-content {
            border: none;
            border-radius: 25px;
            overflow: hidden;
            box-shadow: var(--shadow-strong);
            background: rgba(0, 0, 0, 0.9);
            color: white;
        }
        
        .modal-header {
            border-bottom: none;
            padding: 20px 25px;
            background: rgba(0, 0, 0, 0.95);
            color: white;
        }
        
        .modal-body {
            padding: 25px;
        }
        
        .modal-footer {
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            padding: 20px 25px;
            background: rgba(0, 0, 0, 0.95);
        }

        .progress {
            background-color: #1a1a1a;
            border-radius: 15px;
            height: 20px;
        }

        .progress-bar {
            border-radius: 15px;
            background: var(--gradient-primary);
        }

        .token-box {
            background: #1a1a1a;
            border: 2px solid rgba(255, 255, 255, 0.2);
            border-radius: 15px;
            padding: 15px;
            margin: 10px 0;
            font-family: 'Courier New', monospace;
            font-size: 13px;
            word-break: break-all;
            position: relative;
            transition: all 0.3s ease;
            color: white;
        }
        
        .token-box:hover {
            border-color: var(--primary-pink);
            background: rgba(248, 158, 157, 0.1);
            transform: translateY(-2px);
        }
        
        .copy-btn {
            position: absolute;
            top: 8px;
            right: 8px;
            padding: 6px 12px;
            font-size: 11px;
            border-radius: 20px;
            background: #000000;
            border: none;
            color: white;
            transition: all 0.3s ease;
            cursor: pointer;
        }
        
        .copy-btn:hover {
            background: var(--primary-pink);
            transform: scale(1.1);
        }
        
        /* ==================== ANIMACIONES MEJORADAS ==================== */
        @keyframes slideInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        @keyframes slideInLeft {
            from {
                opacity: 0;
                transform: translateX(-30px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }
        
        @keyframes pulse {
            0%, 100% {
                transform: scale(1);
            }
            50% {
                transform: scale(1.05);
            }
        }
        
        .animate-slide-up {
            animation: slideInUp 0.6s ease-out;
        }
        
        .animate-slide-left {
            animation: slideInLeft 0.6s ease-out;
        }
        
        .animate-pulse {
            animation: pulse 2s infinite;
        }
        
        /* ==================== MEJORAS DE ACCESIBILIDAD ==================== */
        .btn:focus {
            outline: 3px solid rgba(248, 158, 157, 0.5);
            outline-offset: 2px;
        }
        
        .card:focus-within {
            box-shadow: var(--shadow-medium);
        }
        
        /* ==================== EFECTOS DE LOADING ==================== */
        .loading {
            opacity: 0.7;
            pointer-events: none;
            position: relative;
        }
        
        .loading::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 30px;
            height: 30px;
            margin: -15px 0 0 -15px;
            border: 3px solid var(--primary-pink);
            border-radius: 50%;
            border-top-color: transparent;
            animation: spin 1s linear infinite;
            z-index: 1000;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        /* ==================== EFECTOS HOVER MEJORADOS ==================== */
        .hover-lift {
            transition: all 0.3s cubic-bezier(0.25, 0.46, 0.45, 0.94);
        }
        
        .hover-lift:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-medium);
        }
        
        /* Responsive específico para móviles */
        @media (max-width: 576px) {
            .row > .col-md-3 {
                margin-bottom: 15px;
            }
            
            .modal-footer .btn-group {
                flex-direction: column;
                width: 100%;
            }
            
            .modal-footer .btn-group .btn {
                width: 100%;
                margin-bottom: 10px;
            }
            
            .qr-code-small {
                max-width: 60px;
                background: white;
                padding: 3px;
            }
        }
        
        /* Forzar carga de imágenes */
        img {
            max-width: 100%;
            height: auto;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand" href="#" style="font-weight: 600;">
                <i class="fas fa-camera"></i> Galerías QR
            </a>
            <div class="navbar-nav ms-auto">
                <span class="navbar-text me-3 d-none d-md-inline">
                    <i class="fas fa-user"></i> Admin
                </span>
                
                <a class="nav-link hover-lift" href="moderacion.php">
                    <i class="fas fa-shield-alt"></i><span class="d-none d-md-inline"> Moderación</span>
                </a>
                <a class="nav-link hover-lift" href="index.php">
                    <i class="fas fa-home"></i><span class="d-none d-md-inline"> Inicio</span>
                </a>
                <a class="nav-link hover-lift" href="?logout=1">
                    <i class="fas fa-sign-out-alt"></i><span class="d-none d-md-inline"> Salir</span>
                </a>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <?php if ($message): ?>
            <div class="animate-slide-up">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>
        
        <!-- Estadísticas -->
        <div class="row mb-4">
            <div class="col-6 col-md-3">
                <div class="card stat-card animate-slide-up" style="animation-delay: 0.1s;">
                    <div class="card-body text-center">
                        <i class="fas fa-images fa-2x mb-3"></i>
                        <h3><?php echo count($galleries); ?></h3>
                        <p class="mb-0">Galerías</p>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card stat-card animate-slide-up" style="animation-delay: 0.2s;">
                    <div class="card-body text-center">
                        <i class="fas fa-photo-video fa-2x mb-3"></i>
                        <h3><?php echo array_sum(array_column($galleries, 'total_fotos')); ?></h3>
                        <p class="mb-0">Fotos</p>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card stat-card animate-slide-up" style="animation-delay: 0.3s;">
                    <div class="card-body text-center">
                        <i class="fas fa-heart fa-2x mb-3"></i>
                        <h3><?php echo array_sum(array_column($galleries, 'total_likes')); ?></h3>
                        <p class="mb-0">Likes</p>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card stat-card animate-slide-up" style="animation-delay: 0.4s;">
                    <div class="card-body text-center">
                        <i class="fas fa-chart-line fa-2x mb-3"></i>
                        <h3><?php echo array_sum(array_map(function($g) { return isset($g['limite_fotos']) ? intval($g['limite_fotos']) : 100; }, $galleries)); ?></h3>
                        <p class="mb-0">Capacidad</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Acceso Rápido -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card animate-slide-left">
                    <div class="card-header">
                        <h5><i class="fas fa-bolt"></i> Acceso Rápido</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <a href="moderacion.php" class="btn btn-warning w-100 hover-lift">
                                    <i class="fas fa-shield-alt fa-2x d-block mb-2"></i>
                                    <strong>Sistema de Moderación</strong>
                                    <br>
                                    <small>Ver, filtrar y eliminar fotos</small>
                                    <?php if ($fotos_reportadas > 0): ?>
                                        <br>
                                        <span class="badge bg-danger mt-1">
                                            <?php echo $fotos_reportadas; ?> reportadas
                                        </span>
                                    <?php endif; ?>
                                </a>
                            </div>
                            <div class="col-md-4 mb-3">
                                <a href="moderacion.php?reportadas=1" class="btn btn-danger w-100 hover-lift">
                                    <i class="fas fa-flag fa-2x d-block mb-2"></i>
                                    <strong>Fotos Reportadas</strong>
                                    <br>
                                    <small>Revisar contenido flagged</small>
                                    <?php if ($fotos_reportadas > 0): ?>
                                        <br>
                                        <span class="badge bg-light text-dark mt-1">
                                            <?php echo $fotos_reportadas; ?> pendientes
                                        </span>
                                    <?php endif; ?>
                                </a>
                            </div>
                            <div class="col-md-4 mb-3">
                                <a href="moderacion.php?fecha=<?php echo date('Y-m-d'); ?>" class="btn btn-info w-100 hover-lift">
                                    <i class="fas fa-calendar-day fa-2x d-block mb-2"></i>
                                    <strong>Subidas de Hoy</strong>
                                    <br>
                                    <small>Revisar fotos recientes</small>
                                    <br>
                                    <span class="badge bg-light text-dark mt-1">
                                        <?php echo date('d/m/Y'); ?>
                                    </span>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Crear/Editar galería -->
        <div class="card mb-4 animate-slide-up">
            <div class="card-header">
                <h5>
                    <?php if ($editing_gallery): ?>
                        <i class="fas fa-edit"></i> Editar: <?php echo htmlspecialchars($editing_gallery['nombre_evento']); ?>
                    <?php else: ?>
                        <i class="fas fa-plus"></i> Nueva Galería
                    <?php endif; ?>
                </h5>
            </div>

            <div class="card-body">
                <form method="POST" class="row g-3" id="galleryForm">
                    <!-- Token de seguridad -->
                    <input type="hidden" name="form_token" value="<?php echo $_SESSION['form_token']; ?>">
                    
                    <?php if ($editing_gallery): ?>
                        <input type="hidden" name="galeria_id" value="<?php echo $editing_gallery['id']; ?>">
                    <?php endif; ?>
                    
                    <div class="col-md-3 col-12">
                        <label for="nombre_evento" class="form-label">
                            <i class="fas fa-calendar-alt"></i> Evento *
                        </label>
                        <input type="text" class="form-control" id="nombre_evento" name="nombre_evento" 
                               placeholder="Ej: Boda Juan y María" 
                               value="<?php echo $editing_gallery ? htmlspecialchars($editing_gallery['nombre_evento']) : ''; ?>" 
                               required maxlength="100">
                    </div>
                    
                    <div class="col-md-6 col-12">
                        <label for="cliente_nombre" class="form-label">
                            <i class="fas fa-user"></i> Nombre del Cliente *
                        </label>
                        <input type="text" 
                               class="form-control" 
                               id="cliente_nombre" 
                               name="cliente_nombre" 
                               placeholder="Ej: Juan Pérez, María García, Empresa XYZ" 
                               value="<?php echo $editing_gallery ? htmlspecialchars($editing_gallery['cliente_nombre']) : ''; ?>" 
                               required
                               maxlength="100">
                        <div class="form-text">Nombre de la persona o empresa contratante</div>
                    </div>
                    
                    <div class="col-md-3 col-12">
                        <label for="fecha_evento" class="form-label">
                            <i class="fas fa-calendar"></i> Fecha *
                        </label>
                        <input type="date" class="form-control" id="fecha_evento" name="fecha_evento" 
                               value="<?php echo $editing_gallery ? $editing_gallery['fecha_evento'] : ''; ?>" 
                               required>
                    </div>
                    
                    <div class="col-md-3 col-12">
                        <label for="limite_fotos" class="form-label">
                            <i class="fas fa-layer-group"></i> Plan *
                        </label>
                        <select class="form-select" id="limite_fotos" name="limite_fotos" required>
                            <option value="100000000000" <?php echo ($editing_gallery && (isset($editing_gallery['limite_fotos']) ? $editing_gallery['limite_fotos'] : 100) == 100) ? 'selected' : ''; ?>>Fotos Ilimitadas - Premium</option>
                        </select>
                    </div>
                    
                    <div class="col-12">
                        <?php if ($editing_gallery): ?>
                            <button type="submit" name="edit_gallery" class="btn btn-success btn-lg" id="submitBtn">
                                <i class="fas fa-save"></i> Guardar Cambios
                            </button>
                            <a href="<?php echo $_SERVER['PHP_SELF']; ?>" class="btn btn-secondary btn-lg">
                                <i class="fas fa-times"></i> Cancelar
                            </a>
                        <?php else: ?>
                            <button type="submit" name="create_gallery" class="btn btn-primary btn-lg" id="submitBtn">
                                <i class="fas fa-plus"></i> Crear Galería
                            </button>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>

        <!-- Lista de galerías -->
        <div class="card animate-slide-up">
            <div class="card-header">
                <h5><i class="fas fa-list"></i> Galerías (<?php echo count($galleries); ?>)</h5>
            </div>
            <div class="card-body">
                <?php if (empty($galleries)): ?>
                    <div class="empty-state">
                        <i class="fas fa-folder-open"></i>
                        <h5>No hay galerías</h5>
                        <p>Cree su primera galería usando el formulario de arriba</p>
                    </div>
                <?php else: ?>
                    <div class="row">
                        <?php foreach ($galleries as $g): ?>
                            <?php
                            $total_fotos = intval($g['total_fotos']);
                            $limite_fotos = isset($g['limite_fotos']) ? intval($g['limite_fotos']) : 100;
                            if ($limite_fotos <= 0) $limite_fotos = 100;
                            $porcentaje_uso = $limite_fotos > 0 ? ($total_fotos / $limite_fotos) * 100 : 0;
                            
                            if ($porcentaje_uso >= 100) {
                                $limite_class = 'danger';
                                $limite_badge = 'badge-danger';
                                $limite_text = 'COMPLETO';
                            } elseif ($porcentaje_uso >= 80) {
                                $limite_class = 'warning';
                                $limite_badge = 'badge-warning';
                                $limite_text = 'CERCA LÍMITE';
                            } else {
                                $limite_class = 'safe';
                                $limite_badge = 'badge-limit';
                                $limite_text = 'DISPONIBLE';
                            }
                            ?>
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card gallery-card h-100 hover-lift" onclick="openGalleryModal(<?php echo htmlspecialchars(json_encode($g), ENT_QUOTES, 'UTF-8'); ?>)">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-start mb-3">
                                            <h6 class="card-title text-primary mb-0">
                                                <?php echo htmlspecialchars($g['nombre_evento']); ?>
                                            </h6>
                                            <div class="text-end">
                                                <span class="badge">
                                                    <?php echo $total_fotos; ?>/<?php echo $limite_fotos; ?>
                                                </span>
                                                <br>
                                                <span class="badge <?php echo $limite_badge; ?> mt-1" style="font-size: 0.65rem;">
                                                    <?php echo $limite_text; ?>
                                                </span>
                                            </div>
                                        </div>
                                        
                                        <div class="limit-progress">
                                            <div class="limit-progress-bar <?php echo $limite_class; ?>" 
                                                 style="width: <?php echo min(100, $porcentaje_uso); ?>%"></div>
                                        </div>
                                        <div class="limit-indicator text-muted small mt-2">
                                            <?php echo number_format($porcentaje_uso, 1); ?>% usado 
                                            (<?php echo ($limite_fotos - $total_fotos); ?> libres)
                                        </div>
                                        
                                        <p class="card-text small text-muted mb-2 mt-3">
                                            <i class="fas fa-user"></i> <?php echo htmlspecialchars($g['cliente_nombre']); ?>
                                        </p>
                                        
                                        <p class="card-text small text-muted mb-2">
                                            <i class="fas fa-calendar"></i> 
                                            <?php echo date('d/m/Y', strtotime($g['fecha_evento'])); ?>
                                        </p>
                                        
                                        <p class="card-text small text-muted mb-2">
                                            <i class="fas fa-clock"></i> 
                                            <?php echo date('d/m/Y H:i', strtotime($g['fecha_creacion'])); ?>
                                        </p>
                                        
                                        <?php if (isset($g['total_likes']) && $g['total_likes'] > 0): ?>
                                            <p class="card-text small text-muted mb-3">
                                                <i class="fas fa-heart text-danger"></i> 
                                                <?php echo intval($g['total_likes']); ?> likes
                                            </p>
                                        <?php endif; ?>
                                        
                                        <?php if (!empty($g['qr_path']) && file_exists($g['qr_path'])): ?>
                                            <div class="text-center mb-3">
                                                <img src="<?php echo htmlspecialchars($g['qr_path']); ?>" 
                                                     alt="QR Code" class="qr-code-small" loading="lazy">
                                                <br>
                                                <small class="text-muted">QR Code</small>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <div class="text-center">
                                            <small class="text-muted">
                                                <i class="fas fa-mouse-pointer"></i> Toca para administrar
                                            </small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Modal de Administración de Galería -->
    <div class="modal fade" id="galleryModal" tabindex="-1" aria-labelledby="galleryModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header" style="background: var(--gradient-primary); color: white;">
                    <h5 class="modal-title" id="galleryModalLabel">
                        <i class="fas fa-cog"></i> Administrar Galería
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <!-- Información General -->
                        <div class="col-md-6">
                            <div class="card h-100 hover-lift">
                                <div class="card-header">
                                    <h6><i class="fas fa-info-circle"></i> Información</h6>
                                </div>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <strong><i class="fas fa-calendar-alt"></i> Evento:</strong>
                                        <div id="modal-nombre-evento" class="mt-1 p-2 bg-light rounded"></div>
                                    </div>
                                    <div class="mb-3">
                                        <strong><i class="fas fa-user"></i> Cliente:</strong>
                                        <div id="modal-cliente-nombre" class="mt-1 p-2 bg-light rounded"></div>
                                    </div>
                                    <div class="mb-3">
                                        <strong><i class="fas fa-calendar"></i> Fecha Evento:</strong>
                                        <div id="modal-fecha-evento" class="mt-1 p-2 bg-light rounded"></div>
                                    </div>
                                    <div class="mb-3">
                                        <strong><i class="fas fa-clock"></i> Creada:</strong>
                                        <div id="modal-fecha-creacion" class="mt-1 p-2 bg-light rounded"></div>
                                    </div>
                                    <div class="mb-3">
                                        <strong><i class="fas fa-layer-group"></i> Plan:</strong>
                                        <div id="modal-limite-fotos" class="mt-1"></div>
                                    </div>
                                    <div class="mb-3">
                                        <strong><i class="fas fa-signal"></i> Estado:</strong>
                                        <div id="modal-estado" class="mt-1"></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Estadísticas y QR -->
                        <div class="col-md-6">
                            <div class="card h-100 hover-lift">
                                <div class="card-header">
                                    <h6><i class="fas fa-chart-bar"></i> Stats & QR</h6>
                                </div>
                                <div class="card-body text-center">
                                    <div class="row mb-3">
                                        <div class="col-4">
                                            <div class="stat-mini">
                                                <div class="stat-number" id="modal-total-fotos">0</div>
                                                <div class="stat-label">Fotos</div>
                                            </div>
                                        </div>
                                        <div class="col-4">
                                            <div class="stat-mini">
                                                <div class="stat-number" id="modal-total-likes">0</div>
                                                <div class="stat-label">Likes</div>
                                            </div>
                                        </div>
                                        <div class="col-4">
                                            <div class="stat-mini">
                                                <div class="stat-number" id="modal-porcentaje-uso">0%</div>
                                                <div class="stat-label">Uso</div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Barra de progreso -->
                                    <div class="progress mb-3">
                                        <div id="modal-progress-bar" class="progress-bar" role="progressbar" style="width: 0%"></div>
                                    </div>
                                    
                                    <!-- QR Code -->
                                    <div id="modal-qr-container" class="mb-3">
                                        <img id="modal-qr-image" src="" alt="QR Code" class="img-fluid hover-lift" style="max-width: 160px; border-radius: 15px;" loading="lazy">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Token/Contraseña -->
                    <div class="card mt-3 hover-lift">
                        <div class="card-header">
                            <h6><i class="fas fa-key"></i> Contraseña de Descarga</h6>
                        </div>
                        <div class="card-body">
                            <div class="token-box" id="modal-token-box">
                                <span id="modal-token-text"></span>
                                <button type="button" class="copy-btn" onclick="copyModalToken()">
                                    <i class="fas fa-copy"></i>
                                </button>
                            </div>
                            <small class="text-muted">
                                <i class="fas fa-info-circle"></i> Necesaria para descargar todas las fotos del evento
                            </small>
                        </div>
                    </div>

                    <!-- URL Pública -->
                    <div class="card mt-3 hover-lift">
                        <div class="card-header">
                            <h6><i class="fas fa-link"></i> URL Pública</h6>
                        </div>
                        <div class="card-body">
                            <div class="input-group">
                                <input type="text" class="form-control" id="modal-url-publica" readonly>
                                <button class="btn btn-outline-primary" type="button" onclick="copyURL()">
                                    <i class="fas fa-copy"></i> Copiar
                                </button>
                                <button class="btn btn-outline-success" type="button" onclick="openURL()">
                                    <i class="fas fa-external-link-alt"></i> Abrir
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <div class="btn-group w-100" role="group">
                        <button type="button" class="btn btn-secondary" onclick="editGallery()">
                            <i class="fas fa-edit"></i> Editar
                        </button>
                        <button type="button" class="btn btn-warning" onclick="downloadQR()">
                            <i class="fas fa-qrcode"></i> Descargar QR
                        </button>
                        <button type="button" class="btn btn-info" onclick="downloadAlbum()">
                            <i class="fas fa-download"></i> Descargar Álbum
                        </button>
                        <button type="button" class="btn btn-danger" onclick="deleteGallery()">
                            <i class="fas fa-trash"></i> Eliminar
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Variables globales
        let currentGallery = null;
        let formSubmitted = false;

        // ==================== MODAL DE GALERÍA ====================
        function openGalleryModal(gallery) {
            try {
                currentGallery = gallery;
                
                const totalFotos = parseInt(gallery.total_fotos) || 0;
                const limiteFotos = parseInt(gallery.limite_fotos) || 100;
                const totalLikes = parseInt(gallery.total_likes) || 0;
                const porcentajeUso = limiteFotos > 0 ? ((totalFotos / limiteFotos) * 100) : 0;
                
                // Llenar información general con efectos
                document.getElementById('modal-nombre-evento').textContent = gallery.nombre_evento || 'N/A';
                document.getElementById('modal-cliente-nombre').textContent = gallery.cliente_nombre || 'N/A';
                document.getElementById('modal-fecha-evento').textContent = gallery.fecha_evento ? formatDate(gallery.fecha_evento) : 'N/A';
                document.getElementById('modal-fecha-creacion').textContent = gallery.fecha_creacion ? formatDateTime(gallery.fecha_creacion) : 'N/A';
                
                // Plan información
                const planInfo = getPlanInfo(limiteFotos);
                document.getElementById('modal-limite-fotos').innerHTML = 
                    '<span class="badge badge-limit">' + planInfo.name + '</span>' +
                    '<div class="mt-1 small text-muted">' + limiteFotos + ' fotos máximo</div>';
                
                // Estado del álbum
                let estadoHTML = '';
                if (porcentajeUso >= 100) {
                    estadoHTML = '<span class="badge badge-danger animate-pulse">COMPLETO</span>';
                } else if (porcentajeUso >= 80) {
                    estadoHTML = '<span class="badge badge-warning">CERCA LÍMITE</span>';
                } else {
                    estadoHTML = '<span class="badge badge-limit">DISPONIBLE</span>';
                }
                document.getElementById('modal-estado').innerHTML = estadoHTML;
                
                // Estadísticas con animación
                setTimeout(() => {
                    animateModalCounters(totalFotos, totalLikes, porcentajeUso);
                }, 300);
                
                // Barra de progreso animada
                const progressBar = document.getElementById('modal-progress-bar');
                progressBar.style.width = '0%';
                progressBar.textContent = '';
                
                setTimeout(() => {
                    progressBar.style.transition = 'width 1s cubic-bezier(0.25, 0.46, 0.45, 0.94)';
                    progressBar.style.width = Math.min(100, porcentajeUso) + '%';
                    progressBar.textContent = totalFotos + '/' + limiteFotos;
                    
                    if (porcentajeUso >= 100) {
                        progressBar.className = 'progress-bar bg-danger';
                    } else if (porcentajeUso >= 80) {
                        progressBar.className = 'progress-bar bg-warning';
                    } else {
                        progressBar.className = 'progress-bar';
                    }
                }, 500);
                
                // QR Code con efecto
                const qrContainer = document.getElementById('modal-qr-container');
                const qrImage = document.getElementById('modal-qr-image');
                if (gallery.qr_path && gallery.qr_path.length > 0) {
                    qrImage.src = gallery.qr_path;
                    qrContainer.style.display = 'block';
                    qrImage.style.opacity = '0';
                    qrImage.style.transform = 'scale(0.8)';
                    setTimeout(() => {
                        qrImage.style.transition = 'all 0.5s ease';
                        qrImage.style.opacity = '1';
                        qrImage.style.transform = 'scale(1)';
                    }, 200);
                } else {
                    qrContainer.style.display = 'none';
                }
                
                // Token
                document.getElementById('modal-token-text').textContent = gallery.token || 'N/A';
                
                // URL Pública
                const publicURL = window.location.origin + window.location.pathname.replace(/\/[^\/]*$/, '') + '/galeria.php?token=' + (gallery.token || '');
                document.getElementById('modal-url-publica').value = publicURL;
                
                // Mostrar modal
                const modal = new bootstrap.Modal(document.getElementById('galleryModal'));
                modal.show();
                
            } catch (error) {
                console.error('Error abriendo modal:', error);
                showNotification('Error al abrir detalles de la galería', 'error');
            }
        }

        // ==================== ANIMACIÓN DE CONTADORES DEL MODAL ====================
        function animateModalCounters(fotos, likes, porcentaje) {
            animateCounter('modal-total-fotos', fotos);
            animateCounter('modal-total-likes', likes);
            animateCounter('modal-porcentaje-uso', porcentaje, '%');
        }

        function animateCounter(elementId, target, suffix = '') {
            const element = document.getElementById(elementId);
            let current = 0;
            const increment = target / 30;
            
            const timer = setInterval(() => {
                current += increment;
                if (current >= target) {
                    element.textContent = Math.round(target) + suffix;
                    clearInterval(timer);
                } else {
                    element.textContent = Math.floor(current) + suffix;
                }
            }, 50);
        }

        // ==================== FUNCIONES DE UTILIDAD ====================
        function getPlanInfo(limite) {
            const planes = {
                100: { name: 'Básico', color: '#17a2b8' },
                250: { name: 'Profesional', color: '#28a745' },
                500: { name: 'Premium', color: '#fd7e14' },
                1000: { name: 'Empresarial', color: '#6f42c1' }
            };
            return planes[limite] || { name: 'Personalizado', color: '#6c757d' };
        }

        function formatDate(dateString) {
            try {
                const date = new Date(dateString);
                return date.toLocaleDateString('es-ES', {
                    year: 'numeric',
                    month: 'long',
                    day: 'numeric'
                });
            } catch (error) {
                return dateString;
            }
        }

        function formatDateTime(dateString) {
            try {
                const date = new Date(dateString);
                return date.toLocaleDateString('es-ES', {
                    year: 'numeric',
                    month: 'short',
                    day: 'numeric',
                    hour: '2-digit',
                    minute: '2-digit'
                });
            } catch (error) {
                return dateString;
            }
        }

        // ==================== FUNCIONES DE COPIADO ====================
        function copyModalToken() {
            try {
                const tokenText = document.getElementById('modal-token-text').textContent;
                copyToClipboard(tokenText, '#modal-token-box .copy-btn');
            } catch (error) {
                console.error('Error copiando token:', error);
                showNotification('Error al copiar token', 'error');
            }
        }

        function copyURL() {
            try {
                const urlInput = document.getElementById('modal-url-publica');
                copyToClipboard(urlInput.value, event.target.closest('button'));
            } catch (error) {
                console.error('Error copiando URL:', error);
                showNotification('Error al copiar URL', 'error');
            }
        }

        function copyToClipboard(text, buttonElement) {
            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(text).then(function() {
                    showCopySuccess(buttonElement);
                    showNotification('Copiado al portapapeles', 'success');
                }).catch(function() {
                    fallbackCopyTextToClipboard(text);
                });
            } else {
                fallbackCopyTextToClipboard(text);
            }
        }

        function fallbackCopyTextToClipboard(text) {
            const textArea = document.createElement("textarea");
            textArea.value = text;
            textArea.style.position = "fixed";
            textArea.style.left = "-999999px";
            textArea.style.top = "-999999px";
            document.body.appendChild(textArea);
            textArea.focus();
            textArea.select();
            
            try {
                document.execCommand('copy');
                showNotification('Copiado al portapapeles', 'success');
            } catch (err) {
                showNotification('No se pudo copiar automáticamente', 'warning');
            }
            
            document.body.removeChild(textArea);
        }

        function showCopySuccess(btn) {
            try {
                const originalHTML = btn.innerHTML;
                btn.innerHTML = '<i class="fas fa-check"></i>';
                btn.style.background = 'var(--primary-pink)';
                btn.classList.add('animate-pulse');
                
                setTimeout(function() {
                    btn.innerHTML = originalHTML;
                    btn.style.background = 'var(--primary-brown)';
                    btn.classList.remove('animate-pulse');
                }, 2000);
            } catch (error) {
                console.error('Error mostrando éxito:', error);
            }
        }

        // ==================== ACCIONES DEL MODAL ====================
        function openURL() {
            try {
                const url = document.getElementById('modal-url-publica').value;
                window.open(url, '_blank');
            } catch (error) {
                console.error('Error abriendo URL:', error);
                showNotification('Error al abrir URL', 'error');
            }
        }

        function editGallery() {
            try {
                if (currentGallery && currentGallery.id) {
                    window.location.href = '?edit=' + currentGallery.id;
                }
            } catch (error) {
                console.error('Error editando galería:', error);
                showNotification('Error al editar galería', 'error');
            }
        }

        function downloadQR() {
            try {
                if (currentGallery && currentGallery.qr_path) {
                    const link = document.createElement('a');
                    link.href = currentGallery.qr_path;
                    link.download = 'QR_' + (currentGallery.nombre_evento || 'galeria').replace(/[^a-zA-Z0-9]/g, '_') + '.png';
                    link.click();
                    showNotification('Descargando código QR...', 'success');
                } else {
                    showNotification('No hay código QR disponible para esta galería', 'warning');
                }
            } catch (error) {
                console.error('Error descargando QR:', error);
                showNotification('Error al descargar QR', 'error');
            }
        }

        function downloadAlbum() {
            try {
                if (currentGallery && currentGallery.id) {
                    window.open('descargar_album.php?id=' + currentGallery.id, '_blank');
                    showNotification('Abriendo descarga de álbum...', 'info');
                }
            } catch (error) {
                console.error('Error descargando álbum:', error);
                showNotification('Error al descargar álbum', 'error');
            }
        }

        function deleteGallery() {
            try {
                if (currentGallery) {
                    const confirmMsg = '¿Estás seguro de eliminar esta galería?\n\n' +
                        '📅 Evento: ' + (currentGallery.nombre_evento || 'N/A') + '\n' +
                        '👤 Cliente: ' + (currentGallery.cliente_nombre || 'N/A') + '\n' +
                        '📸 Fotos: ' + (currentGallery.total_fotos || 0) + '\n' +
                        '❤️ Likes: ' + (currentGallery.total_likes || 0) + '\n\n' +
                        '⚠️ ESTA ACCIÓN NO SE PUEDE DESHACER ⚠️\n' +
                        'Se eliminarán todas las fotos y datos asociados.';

                    if (confirm(confirmMsg)) {
                        showNotification('Eliminando galería...', 'info');
                        window.location.href = '?delete=' + currentGallery.id;
                    }
                }
            } catch (error) {
                console.error('Error eliminando galería:', error);
                showNotification('Error al eliminar galería', 'error');
            }
        }

        // ==================== SISTEMA DE NOTIFICACIONES ====================
        function showNotification(message, type = 'info') {
            // Crear notificación toast
            const toastContainer = document.createElement('div');
            toastContainer.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                z-index: 9999;
                max-width: 300px;
            `;
            
            const toastClass = type === 'success' ? 'text-bg-success' : 
                            type === 'error' ? 'text-bg-danger' : 
                            type === 'warning' ? 'text-bg-warning' : 'text-bg-info';
            
            const iconClass = type === 'success' ? 'fa-check-circle' : 
                            type === 'error' ? 'fa-exclamation-circle' : 
                            type === 'warning' ? 'fa-exclamation-triangle' : 'fa-info-circle';
            
            toastContainer.innerHTML = `
                <div class="toast ${toastClass}" role="alert" aria-live="assertive" aria-atomic="true">
                    <div class="toast-header">
                        <i class="fas ${iconClass} me-2"></i>
                        <strong class="me-auto">Sistema</strong>
                        <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
                    </div>
                    <div class="toast-body">
                        ${message}
                    </div>
                </div>
            `;
            
            document.body.appendChild(toastContainer);
            
            const toast = new bootstrap.Toast(toastContainer.querySelector('.toast'), {
                autohide: true,
                delay: type === 'error' ? 5000 : 3000
            });
            
            toast.show();
            
            // Remover contenedor después de cerrar
            toastContainer.querySelector('.toast').addEventListener('hidden.bs.toast', () => {
                document.body.removeChild(toastContainer);
            });
        }

        // ==================== AUTO-CERRAR ALERTAS ====================
        setTimeout(function() {
            try {
                const alerts = document.querySelectorAll('.alert');
                alerts.forEach(function(alert) {
                    alert.style.transition = 'opacity 0.5s ease';
                    alert.style.opacity = '0';
                    setTimeout(() => {
                        if (alert.parentNode) {
                            alert.parentNode.removeChild(alert);
                        }
                    }, 500);
                });
            } catch (error) {
                console.error('Error cerrando alertas:', error);
            }
        }, 8000);

        // ==================== MANEJO DE ERRORES GLOBALES ====================
        window.onerror = function(msg, url, lineNo, columnNo, error) {
            console.error('Error JS:', msg, 'en', url, 'línea', lineNo);
            return false;
        };

        window.addEventListener('unhandledrejection', function(event) {
            console.error('Error de promesa no capturada:', event.reason);
        });

        // ==================== OPTIMIZACIONES DE RENDIMIENTO ====================
        // Lazy loading para imágenes QR
        if ('IntersectionObserver' in window) {
            const imageObserver = new IntersectionObserver((entries, observer) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        const img = entry.target;
                        if (img.dataset.src) {
                            img.src = img.dataset.src;
                            img.classList.remove('lazy');
                            observer.unobserve(img);
                        }
                    }
                });
            });

            document.querySelectorAll('img[data-src]').forEach(img => {
                imageObserver.observe(img);
            });
        }

        console.log('✅ Panel de administración cargado correctamente');
        console.log('🔒 Sistema de prevención de duplicados activado');
        console.log('🎨 Efectos y animaciones mejorados');

    </script>
</body>
</html>