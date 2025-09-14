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
        // SISTEMA ILIMITADO - Sin límite de fotos por galería
        $sql = "SELECT COUNT(*) FROM fotos WHERE galeria_id = ?";
        $stmt = $db->query($sql, [$gallery_data['id']]);
        $total_fotos_galeria = $stmt->fetchColumn();
        
        // Sin límite de fotos por galería - sistema completamente ilimitado
        
        // SISTEMA ILIMITADO - Sin límite de fotos por usuario
        $sql = "SELECT COUNT(*) FROM fotos WHERE galeria_id = ? AND mac_usuario = ?";
        $stmt = $db->query($sql, [$gallery_data['id'], $user_mac]);
        $fotos_subidas = $stmt->fetchColumn();
        
        // Sin límite de fotos por usuario - sistema completamente ilimitado
        
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
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($gallery_data['nombre_evento']); ?> - Galería de Fotos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
<style>:root{--primary-brown:#1a1a1a;--primary-pink:#F89E9D;--primary-cream:#2a2a2a}body{background: linear-gradient(135deg, #000000 0%, var(--primary-pink) 100%);font-family:'Segoe UI',Tahoma,Geneva,Verdana,sans-serif;padding-top:160px;color:white}.fixed-header{position:fixed;top:0;left:0;right:0;z-index:1000;background:linear-gradient(135deg,rgba(0,0,0,0.8) 0%,rgba(26,26,26,0.9) 100%);border-bottom:2px solid rgba(255,255,255,0.1);box-shadow:0 4px 20px rgba(0,0,0,0.4);backdrop-filter:blur(10px)}.header-content{padding:15px 0}.event-info{display:flex;align-items:center;gap:15px;margin-bottom:15px}.event-avatar{width:60px;height:60px;background:linear-gradient(45deg,#000000,var(--primary-pink));border-radius:50%;display:flex;align-items:center;justify-content:center;color:white;font-size:24px;font-weight:bold;box-shadow:0 4px 15px rgba(0,0,0,0.4)}.event-details h5{margin:0;color:white;font-weight:600;font-size:1.3rem;text-shadow:0 2px 4px rgba(0,0,0,0.5)}.event-details small{color:#ccc;font-size:0.9rem;text-shadow:0 1px 2px rgba(0,0,0,0.3)}.header-stats{margin-top:10px}.stats-row{display:flex;gap:20px;align-items:center}.stat-item{text-align:center}.stat-number{display:block;font-size:20px;font-weight:bold;color:white;line-height:1;text-shadow:0 2px 4px rgba(0,0,0,0.5)}.stat-label{font-size:11px;color:#ccc;text-transform:uppercase;letter-spacing:0.5px;text-shadow:0 1px 2px rgba(0,0,0,0.3)}.header-buttons{display:flex;gap:10px;align-items:center}.btn-upload{background:linear-gradient(45deg,#000000,var(--primary-pink));border:none;border-radius:25px;padding:10px 20px;color:white;font-weight:600;transition:all 0.3s ease;text-decoration:none;font-size:14px}.btn-upload:hover{transform:translateY(-2px);color:white;box-shadow:0 8px 20px rgba(0,0,0,0.4)}.btn-download{background:linear-gradient(45deg,#007bff,#6610f2);border:none;border-radius:25px;padding:10px 20px;color:white;font-weight:600;transition:all 0.3s ease;text-decoration:none;font-size:14px}.btn-download:hover{transform:translateY(-2px);color:white;box-shadow:0 8px 20px rgba(0,123,255,0.4)}.view-controls{background:rgba(0,0,0,0.7);border-radius:15px;padding:15px 20px;margin-bottom:20px;box-shadow:0 2px 10px rgba(0,0,0,0.3);border:1px solid rgba(255,255,255,0.1)}.view-toggle{background:none;border:2px solid white;color:white;border-radius:12px;padding:8px 20px;margin:0 5px;transition:all 0.3s ease;font-size:0.9em;font-weight:500}.view-toggle.active{background:linear-gradient(45deg,#000000,var(--primary-pink));color:white;border-color:var(--primary-pink);transform:translateY(-2px);box-shadow:0 4px 12px rgba(0,0,0,0.4)}.view-toggle:hover{background:#000000;color:white;transform:translateY(-2px)}.instagram-post{background:rgba(0,0,0,0.8);margin-bottom:25px;border-radius:15px;overflow:hidden;box-shadow:0 4px 15px rgba(0,0,0,0.3);transition:all 0.4s cubic-bezier(0.25,0.46,0.45,0.94);border:1px solid rgba(255,255,255,0.1)}.instagram-post:hover{transform:translateY(-5px);box-shadow:0 8px 25px rgba(0,0,0,0.4)}.post-header{padding:15px;display:flex;align-items:center;justify-content:space-between;border-bottom:1px solid rgba(255,255,255,0.1)}.post-header-left{display:flex;align-items:center;gap:12px}.user-avatar{width:35px;height:35px;background:linear-gradient(45deg,var(--primary-pink),#000000);border-radius:50%;display:flex;align-items:center;justify-content:center;color:white;font-size:14px;font-weight:bold;position:relative;overflow:hidden;border:2px solid white;box-shadow:0 2px 8px rgba(0,0,0,0.3)}.user-avatar::before{content:'';position:absolute;top:0;left:0;right:0;bottom:0;background:radial-gradient(circle at 30% 30%,rgba(255,255,255,0.3),transparent 60%);border-radius:50%}.user-avatar.avatar-style-1{background:linear-gradient(135deg,#667eea,#764ba2)}.user-avatar.avatar-style-2{background:linear-gradient(135deg,#f093fb,#f5576c)}.user-avatar.avatar-style-3{background:linear-gradient(135deg,#4facfe,#00f2fe)}.user-avatar.avatar-style-4{background:linear-gradient(135deg,#43e97b,#38f9d7)}.user-avatar.avatar-style-5{background:linear-gradient(135deg,#fa709a,#fee140)}.user-avatar.avatar-style-6{background:linear-gradient(135deg,#a8edea,#fed6e3)}.user-avatar.avatar-style-7{background:linear-gradient(135deg,#ff9a9e,#fecfef)}.user-avatar.avatar-style-8{background:linear-gradient(135deg,#ffecd2,#fcb69f)}.user-avatar-large{width:50px;height:50px;font-size:18px}.post-user-info h6{margin:0;font-size:14px;font-weight:600;color:white;text-shadow:0 1px 2px rgba(0,0,0,0.3)}.post-user-info small{color:#ccc;font-size:12px;text-shadow:0 1px 2px rgba(0,0,0,0.3)}.report-btn{background:none;border:none;color:#999;font-size:14px;cursor:pointer;transition:all 0.3s ease;padding:8px;border-radius:8px}.report-btn:hover{color:#dc3545;background:rgba(220,53,69,0.1);transform:scale(1.1)}.report-btn.reported{color:#dc3545;background:rgba(220,53,69,0.1)}.post-image{width:100%;height:400px;object-fit:cover;cursor:pointer;transition:all 0.3s ease;-webkit-user-drag:none;-khtml-user-drag:none;-moz-user-drag:none;-o-user-drag:none;user-drag:none;-webkit-touch-callout:none;-webkit-user-select:none;-khtml-user-select:none;-moz-user-select:none;-ms-user-select:none;user-select:none}.post-image:hover{filter:brightness(1.05)}.post-actions{padding:15px;display:flex;align-items:center;gap:15px}.like-btn{background:none;border:none;font-size:24px;cursor:pointer;color:#999;transition:all 0.2s cubic-bezier(0.25,0.46,0.45,0.94);position:relative;padding:8px;border-radius:50%}.like-btn.liked{color:var(--primary-pink)}.like-btn:hover:not(.liked){transform:scale(1.1);color:var(--primary-pink);text-shadow:0 0 10px rgba(248,158,157,0.5)}.like-btn:active{transform:scale(0.95)}.likes-count{font-weight:600;color:white;font-size:14px;transition:all 0.3s ease;text-shadow:0 1px 2px rgba(0,0,0,0.3)}.layout-1-column .instagram-post{margin:0 auto 30px auto;max-width:600px}.layout-1-column .post-image{height:500px}.layout-2-column .instagram-post{margin-bottom:20px}.layout-2-column .post-image{height:300px}.layout-2-column .post-header{padding:12px 15px}.layout-2-column .post-actions{padding:12px 15px}.layout-2-column .user-avatar{width:32px;height:32px;font-size:13px}.layout-2-column .post-user-info h6{font-size:13px}.layout-2-column .post-user-info small{font-size:11px}.layout-2-column .like-btn{font-size:20px}.layout-2-column .likes-count{font-size:13px}.photo-grid{transition:all 0.5s cubic-bezier(0.25,0.46,0.45,0.94)}.upload-area{border:2px dashed var(--primary-pink);border-radius:15px;padding:40px 20px;text-align:center;background:rgba(248,158,157,0.1);transition:all 0.3s ease;cursor:pointer}.upload-area:hover,.upload-area.dragover{border-color:white;background:rgba(255,255,255,0.1);transform:translateY(-2px)}.upload-area i{color:var(--primary-pink)}.upload-area h5,.upload-area p{color:white;text-shadow:0 2px 4px rgba(0,0,0,0.5)}.image-previews{display:grid;grid-template-columns:repeat(auto-fill,minmax(120px,1fr));gap:10px;margin-top:15px}.preview-item{position:relative;border-radius:10px;overflow:hidden;background:#1a1a1a}.preview-image{width:100%;height:120px;object-fit:cover;border-radius:10px}.remove-preview{position:absolute;top:5px;right:5px;background:rgba(220,53,69,0.9);color:white;border:none;border-radius:50%;width:25px;height:25px;font-size:12px;cursor:pointer;display:flex;align-items:center;justify-content:center;transition:all 0.3s ease}.remove-preview:hover{background:#dc3545;transform:scale(1.1)}.file-count{background:#000000;color:white;border-radius:15px;padding:5px 12px;font-size:12px;font-weight:600;display:inline-block;margin-top:10px}.empty-state{text-align:center;padding:60px 20px;color:#ccc}.empty-state i{font-size:4rem;margin-bottom:20px;color:var(--primary-pink)}.empty-state h5{color:white;text-shadow:0 2px 4px rgba(0,0,0,0.5)}.empty-state p{color:#ccc;text-shadow:0 1px 2px rgba(0,0,0,0.3)}.toast-container{position:fixed;top:180px;right:20px;z-index:9999}.toast{background:rgba(0,0,0,0.9);border-radius:15px;box-shadow:0 5px 20px rgba(0,0,0,0.4);border:1px solid rgba(255,255,255,0.1);color:white}.toast.success{border-left:4px solid #28a745}.toast.error{border-left:4px solid #dc3545}.toast.warning{border-left:4px solid #ffc107}.loading-spinner{display:none;position:fixed;top:50%;left:50%;transform:translate(-50%,-50%);z-index:9999;background:rgba(0,0,0,0.9);padding:30px;border-radius:15px;box-shadow:0 10px 30px rgba(0,0,0,0.4)}.loading-spinner h5{color:white;text-shadow:0 2px 4px rgba(0,0,0,0.5)}.spinner{width:40px;height:40px;border:4px solid #1a1a1a;border-top:4px solid var(--primary-pink);border-radius:50%;animation:spin 1s linear infinite}@keyframes spin{0%{transform:rotate(0deg)}100%{transform:rotate(360deg)}}@keyframes heartBeat{0%{transform:scale(1)}15%{transform:scale(1.4)}30%{transform:scale(1.1)}45%{transform:scale(1.3)}60%{transform:scale(1.05)}75%{transform:scale(1.2)}100%{transform:scale(1)}}@keyframes heartFloatInstant{0%{opacity:1;transform:translateY(0) scale(0.5) rotate(0deg);filter:brightness(1.2)}25%{opacity:1;transform:translateY(-20px) scale(1.2) rotate(180deg);filter:brightness(1.5)}50%{opacity:0.8;transform:translateY(-40px) scale(1.5) rotate(360deg);filter:brightness(1.8)}75%{opacity:0.4;transform:translateY(-55px) scale(1.8) rotate(540deg);filter:brightness(2)}100%{opacity:0;transform:translateY(-70px) scale(2.2) rotate(720deg);filter:brightness(2.5)}}@keyframes likeCountPulse{0%{transform:scale(1);color:white}30%{transform:scale(1.3) rotate(2deg);color:var(--primary-pink);text-shadow:0 0 10px rgba(248,158,157,0.8)}60%{transform:scale(1.1) rotate(-1deg);color:#ff6b9d}100%{transform:scale(1) rotate(0deg);color:white;text-shadow:none}}@keyframes statsGlow{0%{transform:scale(1);filter:brightness(1)}50%{transform:scale(1.2);filter:brightness(1.5) drop-shadow(0 0 10px var(--primary-pink))}100%{transform:scale(1);filter:brightness(1)}}@keyframes likeButtonSuccess{0%{transform:scale(1) rotate(0deg)}25%{transform:scale(1.4) rotate(10deg)}50%{transform:scale(1.2) rotate(-5deg)}75%{transform:scale(1.3) rotate(3deg)}100%{transform:scale(1) rotate(0deg)}}@keyframes feedbackPop{0%{opacity:0;transform:translateX(-50%) translateY(-20px) scale(0.8)}20%{opacity:1;transform:translateX(-50%) translateY(0) scale(1.1)}80%{opacity:1;transform:translateX(-50%) translateY(0) scale(1)}100%{opacity:0;transform:translateX(-50%) translateY(-10px) scale(0.9)}}.heart-explosion-instant{animation:heartFloatInstant 1.2s cubic-bezier(0.25,0.46,0.45,0.94) forwards}.stat-number.updating{animation:statsGlow 0.6s ease-out}.likes-count.updated{animation:likeCountPulse 0.6s cubic-bezier(0.68,-0.55,0.265,1.55)}.like-btn.liked{animation:likeButtonSuccess 0.8s cubic-bezier(0.68,-0.55,0.265,1.55)}.instant-feedback{position:fixed;top:20%;left:50%;transform:translateX(-50%);background:linear-gradient(45deg,var(--primary-pink),#ff6b9d);color:white;padding:10px 20px;border-radius:25px;font-size:14px;font-weight:600;z-index:9999;animation:feedbackPop 1.5s ease-out forwards;box-shadow:0 5px 20px rgba(248,158,157,0.4)}@media (max-width:768px){body{padding-top:180px}.event-avatar{width:50px;height:50px;font-size:20px}.event-details h5{font-size:1.1rem}.stats-row{gap:15px}.stat-number{font-size:18px}.stat-label{font-size:10px}.header-buttons{flex-direction:column;gap:8px;width:100%}.btn-upload,.btn-download{width:100%;justify-content:center;font-size:13px;padding:8px 16px}.view-toggle{padding:6px 12px;font-size:0.8em;margin:2px}.post-image{height:300px!important}.upload-area{padding:30px 15px}.image-previews{grid-template-columns:repeat(auto-fill,minmax(80px,1fr))}.preview-image{height:80px}.post-header{padding:10px 15px}.report-btn{font-size:12px}.layout-2-column .photo-item{padding:5px}.layout-2-column .instagram-post{margin-bottom:10px;border-radius:12px;overflow:hidden}.layout-2-column .post-image{height:180px!important;object-fit:cover;width:100%}.layout-2-column .post-header{padding:8px 10px;min-height:auto}.layout-2-column .user-avatar{width:28px;height:28px;font-size:11px;border:1px solid white}.layout-2-column .post-user-info h6{font-size:11px;margin:0;line-height:1.2;font-weight:600}.layout-2-column .post-user-info small{font-size:9px;line-height:1}.layout-2-column .report-btn{font-size:11px;padding:4px;border-radius:4px}.layout-2-column .post-actions{padding:8px 10px;gap:8px}.layout-2-column .like-btn{font-size:16px;padding:4px}.layout-2-column .likes-count{font-size:10px;font-weight:600}.layout-2-column .photo-item{flex:0 0 50%;max-width:50%}.layout-2-column .instagram-post{transition:all 0.3s ease}.layout-2-column .post-image{transition:height 0.3s ease}.layout-2-column .instagram-post:hover{transform:none}.layout-2-column .post-image:hover{filter:none}.layout-2-column .row{margin:0 -3px}.layout-2-column .photo-item.col-lg-6{flex:0 0 50%;max-width:50%;padding:0 3px}}@media (max-width:576px){body{padding-top:200px}.header-content{padding:10px 0}.event-info{margin-bottom:10px}.stats-row{gap:10px}.stat-number{font-size:16px}.header-buttons{gap:6px}.btn-upload,.btn-download{font-size:12px;padding:6px 12px}.layout-2-column .photo-item{padding:3px}.layout-2-column .post-image{height:160px!important}.layout-2-column .post-header{padding:6px 8px}.layout-2-column .user-avatar{width:24px;height:24px;font-size:10px}.layout-2-column .post-user-info h6{font-size:10px}.layout-2-column .post-user-info small{font-size:8px}.layout-2-column .post-actions{padding:6px 8px;gap:6px}.layout-2-column .like-btn{font-size:14px;padding:3px}.layout-2-column .likes-count{font-size:9px}.layout-2-column .report-btn{font-size:10px;padding:3px}}</style>
</head>
<body>
    <div class="fixed-header">
        <div class="container">
            <div class="header-content">
                <div class="event-info">
                    <div class="event-avatar user-avatar-large <?php echo getUserAvatarStyle($gallery_data['nombre_evento']); ?>">
                        <?php echo getUserInitials($gallery_data['nombre_evento']); ?>
                    </div>
                    <div class="event-details">
                        <h5><?php echo htmlspecialchars($gallery_data['nombre_evento']); ?></h5>
                        <small>
                            <i class="fas fa-user"></i> <?php echo htmlspecialchars($gallery_data['cliente_nombre']); ?> • 
                            <i class="fas fa-calendar"></i> <?php echo date('d/m/Y', strtotime($gallery_data['fecha_evento'])); ?>
                        </small>
                    </div>
                </div>

                <div class="header-stats">
                    <div class="row align-items-center">
                        <div class="col-md-6">
                            <div class="stats-row">
                                <div class="stat-item">
                                    <span class="stat-number" id="photosCount"><?php echo count($photos); ?></span>
                                    <span class="stat-label">Fotos</span>
                                </div>
                                <div class="stat-item">
                                    <span class="stat-number" id="totalLikesCount"><?php echo array_sum(array_column($photos, 'total_likes')); ?></span>
                                    <span class="stat-label">Likes</span>
                                </div>
                                <div class="stat-item">
                                    <span class="stat-number"><?php echo count(array_unique(array_column($photos, 'nombre_invitado'))); ?></span>
                                    <span class="stat-label">Participantes</span>
                                </div>
                                <div class="stat-item">
                                    <span class="stat-number" id="remainingCount"><?php echo $fotos_restantes; ?></span>
                                    <span class="stat-label">Restantes</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 text-end">
                            <div class="header-buttons">
                                <button type="button" class="btn btn-upload" data-bs-toggle="modal" data-bs-target="#uploadModal">
                                    <i class="fas fa-camera"></i> Subir Foto
                                </button>
                                <a href="descargar_album.php?id=<?php echo $gallery_data['id']; ?>" class="btn btn-download">
                                    <i class="fas fa-download"></i> Descargar
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="container py-4">
        <?php echo $message; ?>

        <div class="view-controls">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <h6 class="mb-2"><i class="fas fa-th"></i> Vista de Galería</h6>
                    <div class="btn-group" role="group">
                        <button type="button" class="view-toggle active" data-layout="1">
                            <i class="fas fa-list"></i> 1 Columna
                        </button>
                        <button type="button" class="view-toggle" data-layout="2">
                            <i class="fas fa-th"></i> 2 Columnas
                        </button>
                    </div>
                </div>
                <div class="col-md-6 text-end mt-3 mt-md-0">
                    <small class="text-muted">
                        <i class="fas fa-eye"></i> <?php echo count($photos); ?> fotos en la galería
                    </small>
                </div>
            </div>
        </div>

        <?php if (!empty($photos)): ?>
        <div class="photo-grid layout-1-column" id="photoGrid">
            <div class="row" id="photoContainer">
                <?php foreach ($photos as $index => $photo): ?>
                    <div class="photo-item col-12" data-index="<?php echo $index; ?>">
                        <div class="instagram-post">
                            <div class="post-header">
                                <div class="post-header-left">
                                    <div class="user-avatar <?php echo getUserAvatarStyle($photo['nombre_invitado']); ?>">
                                        <?php echo getUserInitials($photo['nombre_invitado']); ?>
                                    </div>
                                    <div class="post-user-info">
                                        <h6><?php echo htmlspecialchars($photo['nombre_invitado'] ?: 'Usuario Anónimo'); ?></h6>
                                        <small><?php echo date('d/m/Y H:i', strtotime($photo['fecha_subida'])); ?></small>
                                    </div>
                                </div>
                                <button class="report-btn <?php echo $photo['usuario_reporto'] ? 'reported' : ''; ?>" 
                                        data-photo-id="<?php echo $photo['id']; ?>"
                                        title="<?php echo $photo['usuario_reporto'] ? 'Ya reportaste esta foto' : 'Reportar contenido inapropiado'; ?>"
                                        <?php echo $photo['usuario_reporto'] ? 'disabled' : ''; ?>>
                                    <i class="fas fa-flag"></i>
                                </button>
                            </div>
                            
                            <img src="<?php echo htmlspecialchars($photo['ruta_foto']); ?>" 
                                 alt="Foto del evento" 
                                 class="post-image"
                                 data-bs-toggle="modal" 
                                 data-bs-target="#photoModal"
                                 data-photo-src="<?php echo htmlspecialchars($photo['ruta_foto']); ?>"
                                 data-photo-name="<?php echo htmlspecialchars($photo['nombre_invitado'] ?: 'Usuario Anónimo'); ?>"
                                 data-photo-date="<?php echo date('d/m/Y H:i', strtotime($photo['fecha_subida'])); ?>"
                                 data-photo-id="<?php echo $photo['id']; ?>"
                                 loading="lazy">
                            
                            <div class="post-actions">
                                <button class="like-btn <?php echo $photo['usuario_dio_like'] ? 'liked' : ''; ?>" 
                                        data-photo-id="<?php echo $photo['id']; ?>"
                                        <?php echo $photo['usuario_dio_like'] ? 'disabled' : ''; ?>>
                                    <i class="fas fa-heart"></i>
                                </button>
                                <span class="likes-count">
                                    <?php echo (int)$photo['total_likes']; ?> <?php echo ((int)$photo['total_likes'] == 1) ? 'like' : 'likes'; ?>
                                </span>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-camera"></i>
                <h4>¡Sé el primero en compartir!</h4>
                <p>No hay fotos aún. Comparte las primeras fotos del evento.</p>
                <button type="button" class="btn btn-upload btn-lg" data-bs-toggle="modal" data-bs-target="#uploadModal">
                    <i class="fas fa-camera"></i> Subir Primera Foto
                </button>
            </div>
        <?php endif; ?>
    </div>

    <div class="modal fade" id="uploadModal" tabindex="-1" aria-labelledby="uploadModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="uploadModalLabel">
                        <i class="fas fa-camera"></i> Subir Fotos al Evento
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="uploadForm" enctype="multipart/form-data">
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="nombre_invitado" class="form-label">
                                    <i class="fas fa-user"></i> Tu nombre *
                                </label>
                                <input type="text" 
                                       class="form-control" 
                                       id="nombre_invitado" 
                                       name="nombre_invitado" 
                                       placeholder="Ej: María García" 
                                       maxlength="50"
                                       required>
                                <div class="form-text">
                                    <i class="fas fa-info-circle"></i> Tu nombre aparecerá en las fotos
                                </div>
                            </div>
                            <div class="col-md-8 mb-3">
                                <label for="fotos" class="form-label">
                                    <i class="fas fa-images"></i> Seleccionar fotos
                                </label>
                                <div class="upload-area" onclick="document.getElementById('fotos').click()">
                                    <input type="file" class="d-none" id="fotos" name="fotos[]" accept="image/*" multiple required>
                                    <i class="fas fa-cloud-upload-alt fa-2x mb-2"></i>
                                    <p class="mb-1">Haz clic o arrastra tus fotos aquí</p>
                                    <small class="text-muted">JPG, PNG, GIF, WEBP - Máximo 10MB cada una</small>
                                    <small class="d-block mt-1">
                                        Puedes subir hasta <span id="maxPhotosText"><?php echo $fotos_restantes; ?></span> fotos
                                    </small>
                                    
                                    <div id="fileCount" class="file-count d-none">
                                        0 archivos seleccionados
                                    </div>
                                    
                                    <div id="imagePreviews" class="image-previews"></div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-upload" id="submitUpload" disabled>
                        <i class="fas fa-upload"></i> Subir Fotos
                    </button>
                </div>
            </div>
        </div>
    </div>
<style>.custom-close{background:rgba(0,0,0,0.8)!important;border-radius:50%!important;width:40px!important;height:40px!important;display:flex!important;align-items:center!important;justify-content:center!important;transition:all 0.3s ease!important}.custom-close:hover{background:rgba(220,53,69,0.9)!important;transform:scale(1.1)!important}.btn-close-overlay{position:absolute;top:20px;right:20px;background:rgba(0,0,0,0.7);border:none;color:white;width:50px;height:50px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:20px;cursor:pointer;z-index:1001;transition:all 0.3s ease}.btn-close-overlay:hover{background:rgba(220,53,69,0.9);transform:scale(1.1);color:white}.btn-close-overlay i{pointer-events:none}</style>
    <div class="modal fade" id="photoModal" tabindex="-1" aria-labelledby="photoModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title text-white" id="photoModalLabel">
                          <button type="button" class="btn-close-overlay" data-bs-dismiss="modal" aria-label="Close">
                    <i class="fas fa-times"></i>
                </button>
                        <i class="fas fa-image"></i> Foto del Evento
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                
                
                <div class="modal-body text-center">
                    <img id="modalImage" src="" alt="Foto del evento" class="img-fluid">
                    <div class="mt-3">
                        <p id="modalPhotoInfo" class="text-muted"></p>
                        <div class="d-flex justify-content-center gap-2">
                            <button id="modalLikeBtn" class="btn btn-outline-primary" onclick="likeFromModal()">
                                <i class="fas fa-heart"></i> Me gusta
                            </button>
                            <button id="modalReportBtn" class="btn btn-outline-warning" onclick="reportFromModal()">
                                <i class="fas fa-flag"></i> Reportar
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="reportModal" tabindex="-1" aria-labelledby="reportModalLabel" aria-hidden="true">
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-warning text-dark">
                    <h5 class="modal-title" id="reportModalLabel">
                        <i class="fas fa-flag"></i> Reportar Contenido
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p><strong>¿Por qué reportas esta foto?</strong></p>
                    <p class="text-muted small">Los reportes son anónimos y ayudan a mantener un ambiente apropiado para todos.</p>
                    
                    <div class="mb-3">
                        <select class="form-select" id="reportReason" required>
                            <option value="">Selecciona un motivo...</option>
                            <option value="Contenido inapropiado">Contenido inapropiado</option>
                            <option value="Contenido ofensivo">Contenido ofensivo</option>
                            <option value="Spam o publicidad">Spam o publicidad</option>
                            <option value="Violencia o contenido peligroso">Violencia o contenido peligroso</option>
                            <option value="Derechos de autor">Viola derechos de autor</option>
                            <option value="Contenido falso">Contenido falso o engañoso</option>
                            <option value="Otros">Otros motivos</option>
                        </select>
                    </div>
                    
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i>
                        <small>Tu reporte será revisado por los administradores del evento.</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-warning" onclick="submitReport()">
                        <i class="fas fa-flag"></i> Enviar Reporte
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="toast-container" id="toastContainer"></div>

    <div class="loading-spinner" id="loadingSpinner">
        <div class="spinner"></div>
        <div class="mt-3 text-center">
            <small class="text-muted">Procesando...</small>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>// Variables globales
let selectedFiles = [];
let maxFiles = parseInt(document.getElementById('maxPhotosText')?.textContent) || 999;
let currentPhotoIdForReport = null;
let currentLayout = 1;
let loadingTimeout = null;

// Inicialización principal
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM cargado - iniciando galería');
    setupViewControls();
    setupLikeHandlers();
    setupReportHandlers();
    setupModalHandlers();
    setupFileHandlers();
    setupUploadModal();
    hideLoading();
    
    // Cargar layout guardado
    const savedLayout = localStorage.getItem('gallery_layout');
    if (savedLayout && savedLayout !== '1') {
        const layoutBtn = document.querySelector(`[data-layout="${savedLayout}"]`);
        if (layoutBtn) {
            setTimeout(() => layoutBtn.click(), 100);
        }
    }
});

// ==================== FUNCIONES DE LOADING ====================

function showLoading() {
    try {
        const spinner = document.getElementById('loadingSpinner');
        if (spinner) {
            spinner.style.display = 'block';
            spinner.dataset.startTime = Date.now();
            
            if (loadingTimeout) {
                clearTimeout(loadingTimeout);
            }
            
            loadingTimeout = setTimeout(() => {
                console.warn('Loading timeout - ocultando automáticamente');
                hideLoading();
            }, 10000);
        }
    } catch (error) {
        console.error('Error en showLoading:', error);
    }
}

function hideLoading() {
    try {
        const spinner = document.getElementById('loadingSpinner');
        if (spinner) {
            spinner.style.display = 'none';
            delete spinner.dataset.startTime;
        }
        
        if (loadingTimeout) {
            clearTimeout(loadingTimeout);
            loadingTimeout = null;
        }
    } catch (error) {
        console.error('Error en hideLoading:', error);
    }
}

// ==================== CONTROLES DE VISTA ====================

function setupViewControls() {
    console.log('Configurando controles de vista');
    document.querySelectorAll('.view-toggle').forEach(toggle => {
        toggle.addEventListener('click', function(e) {
            e.preventDefault();
            const layout = parseInt(this.dataset.layout);
            console.log('Cambiando a layout:', layout);
            
            // Remover clase active de todos los botones
            document.querySelectorAll('.view-toggle').forEach(btn => {
                btn.classList.remove('active');
            });
            
            // Agregar clase active al botón clickeado
            this.classList.add('active');
            
            // Cambiar layout
            changeLayout(layout);
        });
    });
}

function changeLayout(layout) {
    if (layout === currentLayout) {
        console.log('Layout ya activo:', layout);
        return;
    }
    
    console.log('Cambiando de layout', currentLayout, 'a', layout);
    showLoading();
    
    setTimeout(() => {
        try {
            const photoGrid = document.getElementById('photoGrid');
            const photoItems = document.querySelectorAll('.photo-item');
            
            if (!photoGrid) {
                console.error('No se encontró photoGrid');
                hideLoading();
                return;
            }
            
            // Cambiar clase del contenedor principal
            photoGrid.className = `photo-grid layout-${layout}-column`;
            
            // Determinar clases de columna
            let colClass;
            if (layout === 1) {
                colClass = 'col-12';
            } else if (layout === 2) {
                colClass = 'col-lg-6 col-md-6 col-12';
            }
            
            console.log('Aplicando clases:', colClass);
            
            if (photoItems.length === 0) {
                console.log('No hay fotos, solo cambiando layout');
                currentLayout = layout;
                hideLoading();
                localStorage.setItem('gallery_layout', layout);
                return;
            }
            
            // Aplicar transición y nuevas clases
            let processedItems = 0;
            const totalItems = photoItems.length;
            
            photoItems.forEach((item, index) => {
                // Animación de salida
                item.style.opacity = '0';
                item.style.transform = 'translateY(20px)';
                
                setTimeout(() => {
                    try {
                        // Cambiar clases
                        item.className = `photo-item ${colClass}`;
                        
                        // Animación de entrada
                        setTimeout(() => {
                            item.style.transition = 'all 0.5s cubic-bezier(0.25, 0.46, 0.45, 0.94)';
                            item.style.opacity = '1';
                            item.style.transform = 'translateY(0)';
                            
                            processedItems++;
                            
                            if (processedItems === totalItems) {
                                setTimeout(() => {
                                    hideLoading();
                                    console.log('Layout cambiado exitosamente a:', layout);
                                }, 100);
                            }
                        }, 50);
                        
                    } catch (error) {
                        console.error('Error procesando item:', error);
                        processedItems++;
                        if (processedItems === totalItems) {
                            hideLoading();
                        }
                    }
                }, index * 50);
            });
            
            currentLayout = layout;
            localStorage.setItem('gallery_layout', layout);
            
            // Timeout de seguridad
            setTimeout(() => {
                hideLoading();
            }, (totalItems * 50) + 2000);
            
        } catch (error) {
            console.error('Error en changeLayout:', error);
            hideLoading();
        }
    }, 200);
}

// ==================== SISTEMA DE LIKES ====================

function setupLikeHandlers() {
    console.log('Configurando handlers de likes');
    document.querySelectorAll('.like-btn').forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            if (this.disabled) {
                console.log('Botón de like deshabilitado');
                return;
            }
            
            const photoId = this.dataset.photoId;
            console.log('Like clickeado para foto:', photoId);
            likePhotoInstant(photoId, this);
        });
    });
}

function likePhotoInstant(photoId, button) {
    console.log('Procesando like para foto:', photoId);
    
    const likesCount = button.parentElement.querySelector('.likes-count');
    if (!likesCount) {
        console.error('No se encontró contador de likes');
        return;
    }
    
    const currentLikes = parseInt(likesCount.textContent) || 0;
    const newLikes = currentLikes + 1;
    
    // Efectos visuales inmediatos
    createHeartExplosion(button);
    button.classList.add('liked');
    button.disabled = true;
    
    // Actualizar contador
    likesCount.classList.add('updated');
    likesCount.textContent = `${newLikes} ${newLikes === 1 ? 'like' : 'likes'}`;
    
    // Actualizar estadísticas globales
    updateGlobalStats(1);
    showInstantFeedback('❤️ ¡Like agregado!');
    
    // Remover clase de animación
    setTimeout(() => {
        likesCount.classList.remove('updated');
    }, 600);
    
    // Enviar al servidor
    fetch(window.location.href, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: `action=like&foto_id=${encodeURIComponent(photoId)}`
    })
    .then(response => response.json())
    .then(data => {
        console.log('Respuesta del servidor (like):', data);
        if (!data.success) {
            console.warn('Error del servidor:', data.message);
            // Revertir cambios si hay error del servidor
            button.classList.remove('liked');
            button.disabled = false;
            likesCount.textContent = `${currentLikes} ${currentLikes === 1 ? 'like' : 'likes'}`;
            updateGlobalStats(-1);
            showToast(data.message || 'Error al procesar like', 'error');
        }
    })
    .catch(error => {
        console.error('Error de conexión (like):', error);
        showToast('Error de conexión al procesar like', 'warning');
    });
}

function updateGlobalStats(likeDelta) {
    const totalLikesElement = document.getElementById('totalLikesCount');
    if (totalLikesElement) {
        const currentGlobalLikes = parseInt(totalLikesElement.textContent) || 0;
        const newGlobalLikes = Math.max(0, currentGlobalLikes + likeDelta);
        
        totalLikesElement.classList.add('updating');
        totalLikesElement.style.transition = 'all 0.4s cubic-bezier(0.25, 0.46, 0.45, 0.94)';
        totalLikesElement.style.transform = 'scale(1.3)';
        totalLikesElement.style.color = 'var(--primary-pink)';
        
        setTimeout(() => {
            totalLikesElement.textContent = newGlobalLikes;
        }, 200);
        
        setTimeout(() => {
            totalLikesElement.style.transform = 'scale(1)';
            totalLikesElement.style.color = 'var(--primary-brown)';
            totalLikesElement.classList.remove('updating');
        }, 400);
    }
}

function createHeartExplosion(button) {
    const buttonRect = button.getBoundingClientRect();
    const hearts = ['💖', '💕', '💗', '💝', '❤️', '💓', '💘', '😍'];
    
    // Vibración si está disponible
    if ('vibrate' in navigator) {
        navigator.vibrate([50, 30, 50]);
    }
    
    // Crear múltiples corazones
    for (let i = 0; i < 8; i++) {
        setTimeout(() => {
            const heart = document.createElement('div');
            heart.textContent = hearts[Math.floor(Math.random() * hearts.length)];
            heart.style.position = 'fixed';
            heart.style.left = (buttonRect.left + buttonRect.width / 2) + 'px';
            heart.style.top = (buttonRect.top + buttonRect.height / 2) + 'px';
            heart.style.pointerEvents = 'none';
            heart.style.zIndex = '9999';
            heart.style.fontSize = '20px';
            heart.style.color = '#F89E9D';
            heart.style.transition = 'all 1s ease-out';
            heart.style.opacity = '1';
            
            document.body.appendChild(heart);
            
            // Animar
            setTimeout(() => {
                const angle = (i * 45) * Math.PI / 180;
                const distance = 50 + Math.random() * 30;
                heart.style.transform = `translate(${Math.cos(angle) * distance}px, ${Math.sin(angle) * distance - 50}px) scale(1.5)`;
                heart.style.opacity = '0';
            }, 10);
            
            setTimeout(() => {
                if (heart.parentNode) {
                    document.body.removeChild(heart);
                }
            }, 1000);
        }, i * 50);
    }
}

function showInstantFeedback(message) {
    const existingFeedback = document.querySelector('.instant-feedback');
    if (existingFeedback) {
        existingFeedback.remove();
    }
    
    const feedback = document.createElement('div');
    feedback.innerHTML = message;
    feedback.className = 'instant-feedback';
    feedback.style.cssText = `
        position: fixed;
        top: 20%;
        left: 50%;
        transform: translateX(-50%);
        background: linear-gradient(45deg, var(--primary-pink), #ff6b9d);
        color: white;
        padding: 10px 20px;
        border-radius: 25px;
        font-size: 14px;
        font-weight: 600;
        z-index: 9999;
        box-shadow: 0 5px 20px rgba(248, 158, 157, 0.4);
        animation: feedbackPop 1.5s ease-out forwards;
    `;
    
    document.body.appendChild(feedback);
    
    setTimeout(() => {
        if (feedback.parentNode) {
            feedback.remove();
        }
    }, 1500);
}

function likeFromModal() {
    const modalImage = document.getElementById('modalImage');
    const photoId = modalImage?.dataset.photoId;
    
    if (photoId) {
        const galleryLikeBtn = document.querySelector(`.like-btn[data-photo-id="${photoId}"]`);
        if (galleryLikeBtn && !galleryLikeBtn.disabled) {
            const modalLikeBtn = document.getElementById('modalLikeBtn');
            if (modalLikeBtn) {
                modalLikeBtn.disabled = true;
                modalLikeBtn.innerHTML = '<i class="fas fa-heart text-danger"></i> ¡Te gusta!';
            }
            
            likePhotoInstant(photoId, galleryLikeBtn);
        }
    }
}

// ==================== SISTEMA DE REPORTES ====================

function setupReportHandlers() {
    console.log('Configurando handlers de reportes');
    document.querySelectorAll('.report-btn').forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            if (this.disabled) {
                showToast('Ya reportaste esta foto anteriormente', 'warning');
                return;
            }
            
            const photoId = this.dataset.photoId;
            currentPhotoIdForReport = photoId;
            const modal = new bootstrap.Modal(document.getElementById('reportModal'));
            modal.show();
        });
    });
}

function reportFromModal() {
    const modalImage = document.getElementById('modalImage');
    const photoId = modalImage?.dataset.photoId;
    
    if (photoId) {
        currentPhotoIdForReport = photoId;
        const photoModal = bootstrap.Modal.getInstance(document.getElementById('photoModal'));
        if (photoModal) {
            photoModal.hide();
        }
        
        setTimeout(() => {
            const modal = new bootstrap.Modal(document.getElementById('reportModal'));
            modal.show();
        }, 300);
    }
}

function submitReport() {
    const reason = document.getElementById('reportReason').value;
    
    if (!reason) {
        showToast('Por favor selecciona un motivo para el reporte', 'error');
        return;
    }
    
    if (!currentPhotoIdForReport) {
        showToast('Error: No se pudo identificar la foto', 'error');
        return;
    }
    
    const submitBtn = event.target;
    const originalText = submitBtn.innerHTML;
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Enviando...';
    
    fetch(window.location.href, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: `action=report&foto_id=${encodeURIComponent(currentPhotoIdForReport)}&motivo=${encodeURIComponent(reason)}`
    })
    .then(response => response.json())
    .then(data => {
        console.log('Respuesta del servidor (reporte):', data);
        if (data.success) {
            showToast(data.message, 'success');
            
            const reportBtn = document.querySelector(`[data-photo-id="${currentPhotoIdForReport}"]`);
            if (reportBtn) {
                reportBtn.classList.add('reported');
                reportBtn.disabled = true;
                reportBtn.title = 'Ya reportaste esta foto';
            }
            
            const modal = bootstrap.Modal.getInstance(document.getElementById('reportModal'));
            if (modal) {
                modal.hide();
            }
        } else {
            showToast(data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showToast('Error al enviar el reporte. Inténtalo de nuevo.', 'error');
    })
    .finally(() => {
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalText;
        currentPhotoIdForReport = null;
    });
}

// ==================== MODALES ====================

function setupModalHandlers() {
    console.log('Configurando handlers de modales');
    
    const photoModal = document.getElementById('photoModal');
    if (photoModal) {
        photoModal.addEventListener('show.bs.modal', function(event) {
            const trigger = event.relatedTarget;
            if (!trigger) return;
            
            const photoSrc = trigger.getAttribute('data-photo-src');
            const photoName = trigger.getAttribute('data-photo-name');
            const photoDate = trigger.getAttribute('data-photo-date');
            const photoId = trigger.getAttribute('data-photo-id');
            
            const modalImage = document.getElementById('modalImage');
            const modalPhotoInfo = document.getElementById('modalPhotoInfo');
            const modalLikeBtn = document.getElementById('modalLikeBtn');
            const modalReportBtn = document.getElementById('modalReportBtn');
            
            if (modalImage) {
                modalImage.src = photoSrc;
                modalImage.dataset.photoId = photoId;
            }
            
            if (modalPhotoInfo) {
                modalPhotoInfo.innerHTML = `<i class="fas fa-user"></i> Subida por: <strong>${photoName}</strong><br><i class="fas fa-clock"></i> Fecha: ${photoDate}`;
            }
            
            // Actualizar botón de like
            const likeBtn = document.querySelector(`.like-btn[data-photo-id="${photoId}"]`);
            if (modalLikeBtn) {
                if (likeBtn && likeBtn.disabled) {
                    modalLikeBtn.disabled = true;
                    modalLikeBtn.innerHTML = '<i class="fas fa-heart text-danger"></i> Ya te gusta';
                } else {
                    modalLikeBtn.disabled = false;
                    modalLikeBtn.innerHTML = '<i class="fas fa-heart"></i> Me gusta';
                }
            }
            
            // Actualizar botón de reporte
            const reportBtn = document.querySelector(`.report-btn[data-photo-id="${photoId}"]`);
            if (modalReportBtn) {
                if (reportBtn && reportBtn.disabled) {
                    modalReportBtn.style.display = 'none';
                } else {
                    modalReportBtn.style.display = 'inline-block';
                }
            }
        });
    }
    
    // Limpiar modal de reportes al cerrarse
    const reportModal = document.getElementById('reportModal');
    if (reportModal) {
        reportModal.addEventListener('hidden.bs.modal', function() {
            const reasonSelect = document.getElementById('reportReason');
            if (reasonSelect) {
                reasonSelect.value = '';
            }
            currentPhotoIdForReport = null;
        });
    }
}

// ==================== MANEJO DE ARCHIVOS ====================

function setupFileHandlers() {
    console.log('Configurando handlers de archivos');
    
    const fileInput = document.getElementById('fotos');
    const uploadArea = document.querySelector('.upload-area');
    
    if (fileInput) {
        fileInput.addEventListener('change', function(e) {
            handleFileSelection(e.target.files);
        });
    }
    
    if (uploadArea) {
        ['dragover', 'dragleave', 'drop'].forEach(eventName => {
            uploadArea.addEventListener(eventName, preventDefaults);
        });
        
        function preventDefaults(e) {
            e.preventDefault();
            e.stopPropagation();
        }
        
        uploadArea.addEventListener('dragover', function(e) {
            uploadArea.classList.add('dragover');
        });
        
        uploadArea.addEventListener('dragleave', function(e) {
            uploadArea.classList.remove('dragover');
        });
        
        uploadArea.addEventListener('drop', function(e) {
            uploadArea.classList.remove('dragover');
            const files = e.dataTransfer.files;
            if (files.length > 0) {
                handleFileSelection(files);
                syncFilesToInput();
            }
        });
    }
}

function setupUploadModal() {
    const submitBtn = document.getElementById('submitUpload');
    if (submitBtn) {
        submitBtn.addEventListener('click', function(e) {
            e.preventDefault();
            submitUpload();
        });
    }
}

function handleFileSelection(files) {
    const filesArray = Array.from(files);
    console.log('Archivos seleccionados:', filesArray.length);
    
    if (filesArray.length > maxFiles) {
        showToast(`Solo puedes seleccionar hasta ${maxFiles} fotos. Has seleccionado ${filesArray.length}.`, 'warning');
        return;
    }
    
    const validFiles = [];
    const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
    
    filesArray.forEach((file) => {
        if (!allowedTypes.some(type => file.type.toLowerCase().includes(type.split('/')[1]))) {
            showToast(`El archivo "${file.name}" no es un tipo de imagen válido.`, 'error');
            return;
        }
        
        if (file.size > 10 * 1024 * 1024) {
            showToast(`El archivo "${file.name}" es demasiado grande. Máximo 10MB.`, 'error');
            return;
        }
        
        validFiles.push(file);
    });
    
    selectedFiles = validFiles;
    updateFileDisplay();
    updateUploadButton();
    syncFilesToInput();
}

function syncFilesToInput() {
    const fileInput = document.getElementById('fotos');
    if (fileInput) {
        const dt = new DataTransfer();
        selectedFiles.forEach(file => dt.items.add(file));
        fileInput.files = dt.files;
    }
}

function updateFileDisplay() {
    const fileCount = document.getElementById('fileCount');
    const previews = document.getElementById('imagePreviews');
    
    if (fileCount) {
        if (selectedFiles.length > 0) {
            fileCount.textContent = `${selectedFiles.length} archivo(s) seleccionado(s)`;
            fileCount.classList.remove('d-none');
        } else {
            fileCount.classList.add('d-none');
        }
    }
    
    if (previews) {
        previews.innerHTML = '';
        
        selectedFiles.forEach((file, index) => {
            const reader = new FileReader();
            reader.onload = function(e) {
                const previewItem = document.createElement('div');
                previewItem.className = 'preview-item';
                previewItem.innerHTML = `
                    <img src="${e.target.result}" alt="Preview ${index + 1}" class="preview-image">
                    <button type="button" class="remove-preview" onclick="removeFile(${index})">
                        <i class="fas fa-times"></i>
                    </button>
                `;
                previews.appendChild(previewItem);
            };
            reader.readAsDataURL(file);
        });
    }
}

function removeFile(index) {
    console.log('Removiendo archivo en índice:', index);
    selectedFiles.splice(index, 1);
    updateFileDisplay();
    updateUploadButton();
    syncFilesToInput();
}

function updateUploadButton() {
    const uploadBtn = document.getElementById('submitUpload');
    if (uploadBtn) {
        uploadBtn.disabled = selectedFiles.length === 0;
    }
}

// ==================== SUBIDA DE ARCHIVOS ====================

function submitUpload() {
    console.log('Iniciando subida de archivos');
    
    const nombreInput = document.getElementById('nombre_invitado');
    const nombre = nombreInput.value.trim();
    
    if (!nombre || nombre.length < 2) {
        showToast('El nombre es obligatorio y debe tener al menos 2 caracteres.', 'error');
        nombreInput.focus();
        return;
    }
    
    if (selectedFiles.length === 0) {
        showToast('Por favor selecciona al menos una foto.', 'warning');
        return;
    }
    
    const submitBtn = document.getElementById('submitUpload');
    const originalText = submitBtn.innerHTML;
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Subiendo...';
    
    const formData = new FormData();
    formData.append('nombre_invitado', nombre);
    selectedFiles.forEach((file) => {
        formData.append('fotos[]', file);
    });
    
    fetch(window.location.href, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        console.log('Respuesta de subida:', data);
        
        if (data.success) {
            showToast(data.message, 'success');
            
            // Limpiar formulario
            selectedFiles = [];
            updateFileDisplay();
            updateUploadButton();
            nombreInput.value = '';
            
            // Cerrar modal
            const modal = bootstrap.Modal.getInstance(document.getElementById('uploadModal'));
            if (modal) {
                modal.hide();
            }
            
            // Recargar página
            setTimeout(() => {
                window.location.reload();
            }, 1500);
        } else {
            showToast(data.message, 'error');
        }
        
        if (data.warnings && data.warnings.length > 0) {
            data.warnings.forEach(warning => {
                showToast(warning, 'warning');
            });
        }
    })
    .catch(error => {
        console.error('Error en subida:', error);
        showToast('Error al subir las fotos. Inténtalo de nuevo.', 'error');
    })
    .finally(() => {
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalText;
    });
}

// ==================== SISTEMA DE TOASTS ====================

function showToast(message, type = 'info') {
    console.log('Mostrando toast:', message, type);
    
    const toastContainer = document.getElementById('toastContainer');
    if (!toastContainer) {
        console.error('No se encontró contenedor de toasts');
        return;
    }
    
    const toastId = 'toast_' + Date.now();
    const toastClass = {
        'success': 'success',
        'error': 'error', 
        'warning': 'warning',
        'info': 'info'
    }[type] || 'info';
    
    const iconClass = {
        'success': 'fa-check-circle',
        'error': 'fa-exclamation-circle',
        'warning': 'fa-exclamation-triangle',
        'info': 'fa-info-circle'
    }[type] || 'fa-info-circle';
    
    const toastHTML = `
        <div class="toast ${toastClass}" id="${toastId}" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="toast-header">
                <i class="fas ${iconClass} me-2"></i>
                <strong class="me-auto">Galería</strong>
                <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
            <div class="toast-body">${message}</div>
        </div>
    `;
    
    toastContainer.insertAdjacentHTML('beforeend', toastHTML);
    
    const toastElement = document.getElementById(toastId);
    const toast = new bootstrap.Toast(toastElement, {
        autohide: true,
        delay: type === 'error' ? 6000 : 4000
    });
    
    toast.show();
    
    toastElement.addEventListener('hidden.bs.toast', () => {
        toastElement.remove();
    });
}

// ==================== EVENTOS GLOBALES ====================

// Proteger imágenes del click derecho
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.post-image').forEach(function(img) {
        img.addEventListener('contextmenu', function(e) {
            e.preventDefault();
            return false;
        });
    });
});

// Protección adicional para el loading
document.addEventListener('DOMContentLoaded', function() {
    setInterval(() => {
        const spinner = document.getElementById('loadingSpinner');
        if (spinner && spinner.style.display === 'block') {
            if (!spinner.dataset.startTime) {
                spinner.dataset.startTime = Date.now();
            } else {
                const elapsed = Date.now() - parseInt(spinner.dataset.startTime);
                if (elapsed > 15000) {
                    console.warn('Loading visible por más de 15 segundos - forzando ocultado');
                    hideLoading();
                }
            }
        }
    }, 2000);
});

// Ocultar loading antes de abandonar la página
window.addEventListener('beforeunload', function() {
    hideLoading();
});

// Hacer funciones globales accesibles
window.removeFile = removeFile;
window.likeFromModal = likeFromModal;
window.reportFromModal = reportFromModal;
window.submitReport = submitReport;
</script>
</body>
</html>