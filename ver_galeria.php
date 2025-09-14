<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Incluir solo configuración
require_once 'config/database.php';

$message = '';
$gallery_data = null;
$photos = [];

// Verificar que existe el token
if (!isset($_GET['token']) || empty($_GET['token'])) {
    die('
    <!DOCTYPE html>
    <html><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Error - Galería</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    </head><body style="background-color: #F7EEDE;">
    <div class="container mt-5">
        <div class="alert alert-danger text-center">
            <h4>❌ Token de galería no válido</h4>
            <p>El enlace que usaste no es válido o ha expirado.</p>
        </div>
    </div></body></html>
    ');
}

$token = sanitize_input($_GET['token']);

// Obtener datos de la galería
try {
    $db = Database::getInstance();
    $sql = "SELECT * FROM galerias WHERE token = ? AND activa = 1";
    $stmt = $db->query($sql, [$token]);
    $gallery_data = $stmt->fetch();
} catch (Exception $e) {
    die('Error de base de datos: ' . htmlspecialchars($e->getMessage()));
}

if (!$gallery_data) {
    die('
    <!DOCTYPE html>
    <html><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Error - Galería</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    </head><body style="background-color: #F7EEDE;">
    <div class="container mt-5">
        <div class="alert alert-danger text-center">
            <h4>❌ Galería no encontrada</h4>
            <p>Esta galería no existe o está inactiva.</p>
        </div>
    </div></body></html>
    ');
}

// Procesar REPORTE DE FOTO
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'report') {
    $foto_id = (int)$_POST['foto_id'];
    $motivo = sanitize_input($_POST['motivo'] ?? 'Contenido inapropiado');
    $user_mac = get_user_mac();
    
    try {
        // Verificar si ya reportó esta foto
        $sql = "SELECT COUNT(*) FROM foto_reportes WHERE foto_id = ? AND mac_usuario = ?";
        $stmt = $db->query($sql, [$foto_id, $user_mac]);
        $ya_reporto = $stmt->fetchColumn() > 0;
        
        if (!$ya_reporto) {
            // Registrar reporte
            $sql = "INSERT INTO foto_reportes (foto_id, motivo, mac_usuario, ip_usuario, fecha_reporte) VALUES (?, ?, ?, ?, NOW())";
            $db->query($sql, [$foto_id, $motivo, $user_mac, get_user_ip()]);
            
            // Marcar foto como reportada si no lo está ya
            $sql = "UPDATE fotos SET reportada = 1, motivo_reporte = ?, fecha_reporte = NOW() 
                   WHERE id = ? AND reportada = 0";
            $db->query($sql, [$motivo, $foto_id]);
            
            echo json_encode(['success' => true, 'message' => 'Reporte registrado. Gracias por ayudar a mantener el contenido apropiado.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Ya reportaste esta foto anteriormente']);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error al procesar reporte']);
    }
    exit;
}

// Procesar LIKE
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'like') {
    $foto_id = (int)$_POST['foto_id'];
    $user_mac = get_user_mac();
    
    try {
        // Verificar si ya dio like
        $sql = "SELECT COUNT(*) FROM foto_likes WHERE foto_id = ? AND mac_usuario = ?";
        $stmt = $db->query($sql, [$foto_id, $user_mac]);
        $ya_dio_like = $stmt->fetchColumn() > 0;
        
        if (!$ya_dio_like) {
            // Registrar like
            $sql = "INSERT INTO foto_likes (foto_id, mac_usuario, ip_usuario) VALUES (?, ?, ?)";
            $db->query($sql, [$foto_id, $user_mac, get_user_ip()]);
            
            echo json_encode(['success' => true, 'message' => 'Like agregado']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Ya diste like a esta foto']);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error al procesar like']);
    }
    exit;
}

// Procesar subida de fotos múltiples (AJAX)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['fotos'])) {
    $nombre_invitado = sanitize_input($_POST['nombre_invitado'] ?? '');
    $user_mac = get_user_mac();
    $user_ip = get_user_ip();
    
    // Validar nombre obligatorio
    if (empty($nombre_invitado) || strlen($nombre_invitado) < 2) {
        echo json_encode([
            'success' => false, 
            'message' => 'El nombre es obligatorio y debe tener al menos 2 caracteres.'
        ]);
        exit;
    }
    
    if (!preg_match('/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]{2,50}$/', $nombre_invitado)) {
        echo json_encode([
            'success' => false, 
            'message' => 'El nombre solo puede contener letras y espacios (2-50 caracteres).'
        ]);
        exit;
    }
    
    try {
        // SISTEMA ILIMITADO - Sin límite de fotos por galería ni por usuario
        $sql = "SELECT COUNT(*) FROM fotos WHERE galeria_id = ?";
        $stmt = $db->query($sql, [$gallery_data['id']]);
        $total_fotos_galeria = $stmt->fetchColumn();
        
        // Sin límite de fotos por galería - sistema completamente ilimitado
        
        // Sin límite de fotos por usuario - sistema completamente ilimitado
        $sql = "SELECT COUNT(*) FROM fotos WHERE galeria_id = ? AND mac_usuario = ?";
        $stmt = $db->query($sql, [$gallery_data['id'], $user_mac]);
        $fotos_subidas = $stmt->fetchColumn();
        
        // SISTEMA ILIMITADO - Permitir cualquier cantidad de fotos
        $archivos = $_FILES['fotos'];
        $total_archivos = count($archivos['name']);
        
        // Sin límite de archivos - sistema completamente ilimitado
        
        $fotos_subidas_exitosamente = 0;
        $errores = [];
        
        // Definir directorio de subida
        if (!defined('UPLOAD_DIR')) {
            define('UPLOAD_DIR', 'uploads/');
        }
        
        // Crear directorio si no existe
        if (!is_dir(UPLOAD_DIR)) {
            if (!mkdir(UPLOAD_DIR, 0755, true)) {
                echo json_encode([
                    'success' => false, 
                    'message' => 'Error: No se pudo crear el directorio de subida.'
                ]);
                exit;
            }
        }
        
        // Procesar cada archivo
        for ($i = 0; $i < $total_archivos; $i++) {
            if ($archivos['error'][$i] === UPLOAD_ERR_OK) {
                try {
                    // Validar archivo usando finfo
                    $finfo = finfo_open(FILEINFO_MIME_TYPE);
                    if (!$finfo) {
                        // Fallback: validar por extensión
                        $extension = strtolower(pathinfo($archivos['name'][$i], PATHINFO_EXTENSION));
                        $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                        if (!in_array($extension, $allowed_extensions)) {
                            $errores[] = 'Archivo ' . ($i + 1) . ': Tipo no permitido';
                            continue;
                        }
                    } else {
                        $file_type = finfo_file($finfo, $archivos['tmp_name'][$i]);
                        finfo_close($finfo);
                        
                        $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                        if (!in_array($file_type, $allowed_types)) {
                            $errores[] = 'Archivo ' . ($i + 1) . ': Tipo no permitido (' . $file_type . ')';
                            continue;
                        }
                    }
                    
                    // Verificar tamaño (50MB para sistema ilimitado)
                    if ($archivos['size'][$i] > 50 * 1024 * 1024) {
                        $errores[] = 'Archivo ' . ($i + 1) . ': Demasiado grande (máx 50MB)';
                        continue;
                    }
                    
                    // Verificar que el archivo temporal existe
                    if (!is_uploaded_file($archivos['tmp_name'][$i])) {
                        $errores[] = 'Archivo ' . ($i + 1) . ': Archivo temporal no válido';
                        continue;
                    }
                    
                    // Generar nombre único y seguro
                    $extension = strtolower(pathinfo($archivos['name'][$i], PATHINFO_EXTENSION));
                    if (empty($extension)) {
                        $extension = 'jpg'; // Extensión por defecto
                    }
                    
                    $filename = 'foto_' . $gallery_data['id'] . '_' . time() . '_' . mt_rand(1000, 9999) . '_' . $i . '.' . $extension;
                    $upload_path = UPLOAD_DIR . $filename;
                    
                    // Mover archivo
                    if (move_uploaded_file($archivos['tmp_name'][$i], $upload_path)) {
                        // Verificar que el archivo se movió correctamente
                        if (file_exists($upload_path) && filesize($upload_path) > 0) {
                            // Registrar en base de datos
                            $sql = "INSERT INTO fotos (galeria_id, nombre_invitado, ruta_foto, nombre_archivo, mac_usuario, ip_usuario, fecha_subida) VALUES (?, ?, ?, ?, ?, ?, NOW())";
                            $stmt = $db->query($sql, [
                                $gallery_data['id'],
                                $nombre_invitado,
                                $upload_path,
                                $archivos['name'][$i],
                                $user_mac,
                                $user_ip
                            ]);
                            
                            if ($stmt) {
                                $fotos_subidas_exitosamente++;
                            } else {
                                // Si falla la BD, eliminar archivo
                                unlink($upload_path);
                                $errores[] = 'Archivo ' . ($i + 1) . ': Error registrando en base de datos';
                            }
                        } else {
                            $errores[] = 'Archivo ' . ($i + 1) . ': El archivo no se guardó correctamente';
                        }
                    } else {
                        $errores[] = 'Archivo ' . ($i + 1) . ': Error al mover archivo';
                    }
                    
                } catch (Exception $e) {
                    $errores[] = 'Archivo ' . ($i + 1) . ': ' . $e->getMessage();
                }
            } else {
                // Errores de subida específicos
                switch ($archivos['error'][$i]) {
                    case UPLOAD_ERR_INI_SIZE:
                    case UPLOAD_ERR_FORM_SIZE:
                        $errores[] = 'Archivo ' . ($i + 1) . ': Archivo demasiado grande';
                        break;
                    case UPLOAD_ERR_PARTIAL:
                        $errores[] = 'Archivo ' . ($i + 1) . ': Subida incompleta';
                        break;
                    case UPLOAD_ERR_NO_FILE:
                        $errores[] = 'Archivo ' . ($i + 1) . ': No se seleccionó archivo';
                        break;
                    case UPLOAD_ERR_NO_TMP_DIR:
                        $errores[] = 'Archivo ' . ($i + 1) . ': Directorio temporal no disponible';
                        break;
                    case UPLOAD_ERR_CANT_WRITE:
                        $errores[] = 'Archivo ' . ($i + 1) . ': Error de escritura en disco';
                        break;
                    default:
                        $errores[] = 'Archivo ' . ($i + 1) . ': Error desconocido en la subida';
                        break;
                }
            }
        }
        
        // Respuesta JSON
        $response = [];
        
        if ($fotos_subidas_exitosamente > 0) {
            $response['success'] = true;
            $response['message'] = '¡' . $fotos_subidas_exitosamente . ' foto(s) subida(s) exitosamente! Sistema ilimitado - puedes subir todas las fotos que quieras.';
            $response['uploaded_count'] = $fotos_subidas_exitosamente;
            $response['remaining_photos'] = 'ilimitadas';
        }
        
        if (!empty($errores)) {
            $response['warnings'] = $errores;
        }
        
        if ($fotos_subidas_exitosamente === 0) {
            $response['success'] = false;
            $response['message'] = 'No se pudo subir ninguna foto. Verifica que los archivos sean imágenes válidas y que no excedan 50MB cada una.';
        }
        
        echo json_encode($response);
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false, 
            'message' => 'Error al procesar fotos: ' . htmlspecialchars($e->getMessage())
        ]);
    }
    exit;
}

// Obtener fotos de la galería con conteo de likes y estado de reportes
try {
    $sql = "SELECT f.*, 
            COUNT(fl.id) as total_likes,
            MAX(CASE WHEN fl.mac_usuario = ? THEN 1 ELSE 0 END) as usuario_dio_like,
            MAX(CASE WHEN fr.mac_usuario = ? THEN 1 ELSE 0 END) as usuario_reporto,
            COALESCE(f.reportada, 0) as es_reportada
            FROM fotos f 
            LEFT JOIN foto_likes fl ON f.id = fl.foto_id 
            LEFT JOIN foto_reportes fr ON f.id = fr.foto_id 
            WHERE f.galeria_id = ? 
            GROUP BY f.id 
            ORDER BY f.fecha_subida DESC";
    $stmt = $db->query($sql, [get_user_mac(), get_user_mac(), $gallery_data['id']]);
    $photos = $stmt->fetchAll();
} catch (Exception $e) {
    $message .= '<div class="alert alert-warning">
        Error cargando fotos: ' . htmlspecialchars($e->getMessage()) . '
    </div>';
}

// SISTEMA ILIMITADO - Sin límite de fotos por usuario
$fotos_restantes = 'ilimitadas';
try {
    $sql = "SELECT COUNT(*) FROM fotos WHERE galeria_id = ? AND mac_usuario = ?";
    $stmt = $db->query($sql, [$gallery_data['id'], get_user_mac()]);
    $fotos_subidas_usuario = $stmt->fetchColumn();
    // Sistema completamente ilimitado
} catch (Exception $e) {
    // Si hay error, mantener sistema ilimitado
}

// ==================== FUNCIONES DE UTILIDAD ====================

function sanitize_input($data) {
    if (!is_string($data)) return '';
    return htmlspecialchars(trim(stripslashes($data)), ENT_QUOTES, 'UTF-8');
}

function get_user_ip() {
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        return $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        return $_SERVER['HTTP_X_FORWARDED_FOR'];
    } else {
        return $_SERVER['REMOTE_ADDR'];
    }
}

function get_user_mac() {
    // Crear un identificador único basado en características del dispositivo
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    $user_ip = get_user_ip();
    
    // Crear un hash único basado en User Agent + IP + Headers únicos
    $unique_string = $user_agent . '|' . $user_ip . '|' . 
                    ($_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '') . '|' . 
                    ($_SERVER['HTTP_ACCEPT_ENCODING'] ?? '') . '|' . 
                    ($_SERVER['HTTP_DNT'] ?? '');
    
    // Generar un "pseudo-MAC" único para este dispositivo
    $pseudo_mac = substr(hash('sha256', $unique_string), 0, 12);
    
    // Formato de MAC address (XX:XX:XX:XX:XX:XX)
    $formatted_mac = implode(':', str_split($pseudo_mac, 2));
    
    return $formatted_mac;
}

// Función para generar iniciales bonitas del nombre
function getUserInitials($name) {
    if (empty($name)) return '?';
    
    $words = explode(' ', trim($name));
    $initials = '';
    
    // Tomar primera letra de las primeras dos palabras
    for ($i = 0; $i < min(2, count($words)); $i++) {
        if (!empty($words[$i])) {
            $initials .= strtoupper(substr($words[$i], 0, 1));
        }
    }
    
    // Si solo hay una inicial, repetirla o usar la segunda letra
    if (strlen($initials) === 1) {
        if (strlen($words[0]) > 1) {
            $initials .= strtoupper(substr($words[0], 1, 1));
        } else {
            $initials .= $initials; // Repetir la única letra
        }
    }
    
    return $initials ?: '??';
}

// Función para asignar estilo de avatar basado en el nombre
function getUserAvatarStyle($name) {
    if (empty($name)) return 'avatar-style-1';
    
    // Crear un hash del nombre para asignar consistentemente un estilo
    $hash = crc32($name);
    $style_number = (abs($hash) % 8) + 1; // 8 estilos disponibles
    
    return 'avatar-style-' . $style_number;
}

?>
