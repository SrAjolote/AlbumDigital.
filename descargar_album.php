<?php
/**
 * Descarga de álbum completo con contraseña
 * Sistema de Galerías con QR - Versión Simple
 * Colores: #826948 #F89E9D #F7EEDE
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config/database.php';

$message = '';
$gallery_data = null;
$show_download = false;

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $message = '<div class="alert alert-danger">ID de galería no válido</div>';
} else {
    $galeria_id = (int)$_GET['id'];
    
    try {
        $db = Database::getInstance();
        $sql = "SELECT * FROM galerias WHERE id = ? AND activa = 1";
        $stmt = $db->query($sql, [$galeria_id]);
        $gallery_data = $stmt->fetch();
        
        if (!$gallery_data) {
            $message = '<div class="alert alert-danger">Galería no encontrada o inactiva</div>';
        }
    } catch (Exception $e) {
        $message = '<div class="alert alert-danger">Error de base de datos: ' . htmlspecialchars($e->getMessage()) . '</div>';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['verify_password']) && $gallery_data) {
    $password = trim($_POST['password'] ?? '');
    
    if ($password === $gallery_data['token']) {
        $show_download = true;
        $message = '<div class="alert alert-success"><i class="fas fa-check"></i> Contraseña correcta. Ahora puedes descargar el álbum.</div>';
    } else {
        $message = '<div class="alert alert-danger"><i class="fas fa-times"></i> Contraseña incorrecta. Contacta al organizador del evento.</div>';
    }
}

// Procesar descarga del álbum
if (isset($_POST['download']) && isset($_POST['password_verified']) && $gallery_data) {
    $password = trim($_POST['password_verified']);
    
    if ($password === $gallery_data['token']) {
        try {
            if (!class_exists('ZipArchive')) {
                throw new Exception('La extensión ZipArchive no está disponible en este servidor');
            }
            
            $sql = "SELECT * FROM fotos WHERE galeria_id = ? ORDER BY fecha_subida ASC";
            $stmt = $db->query($sql, [$galeria_id]);
            $photos = $stmt->fetchAll();
            
            if (empty($photos)) {
                $message = '<div class="alert alert-warning">No hay fotos para descargar en esta galería.</div>';
            } else {
                $zip = new ZipArchive();
                $safe_event_name = preg_replace('/[^a-zA-Z0-9_-]/', '_', $gallery_data['nombre_evento']);
                $zip_filename = 'album_' . $safe_event_name . '_' . date('Y-m-d_H-i-s') . '.zip';
                
                // Crear directorio temporal si no existe
                $temp_dir = './temp';
                if (!is_dir($temp_dir)) {
                    mkdir($temp_dir, 0755, true);
                }
                
                $zip_path = $temp_dir . '/' . $zip_filename;
                
                // Si no se puede escribir en temp, usar directorio actual
                if (!is_writable($temp_dir)) {
                    $zip_path = $zip_filename;
                }
                
                $zip_result = $zip->open($zip_path, ZipArchive::CREATE | ZipArchive::OVERWRITE);
                if ($zip_result !== TRUE) {
                    throw new Exception('Error creando archivo ZIP (código: ' . $zip_result . ')');
                }
                
                $file_counter = 1;
                $files_added = 0;
                
                foreach ($photos as $photo) {
                    if (file_exists($photo['ruta_foto'])) {
                        $extension = strtolower(pathinfo($photo['ruta_foto'], PATHINFO_EXTENSION));
                        $guest_name = $photo['nombre_invitado'] ? 
                            preg_replace('/[^a-zA-Z0-9_-]/', '_', $photo['nombre_invitado']) : 'Anonimo';
                        
                        $new_name = sprintf('%03d', $file_counter) . '_' . 
                                   $guest_name . '_' . 
                                   date('Y-m-d_H-i', strtotime($photo['fecha_subida'])) . 
                                   '.' . $extension;
                        
                        if ($zip->addFile($photo['ruta_foto'], $new_name)) {
                            $files_added++;
                        }
                        
                        $file_counter++;
                    }
                }
                
                if ($files_added === 0) {
                    $zip->close();
                    if (file_exists($zip_path)) unlink($zip_path);
                    throw new Exception('No se encontraron fotos válidas para agregar al ZIP');
                }
                
                $zip->close();
                
                if (!file_exists($zip_path) || filesize($zip_path) === 0) {
                    throw new Exception('El archivo ZIP no se creó correctamente');
                }
                
                // Limpiar cualquier salida previa
                while (ob_get_level()) {
                    ob_end_clean();
                }
                
                // Headers para descarga
                header('Content-Type: application/zip');
                header('Content-Disposition: attachment; filename="' . $zip_filename . '"');
                header('Content-Length: ' . filesize($zip_path));
                header('Cache-Control: no-cache, must-revalidate');
                header('Expires: 0');
                header('Pragma: public');
                
                // Leer y enviar archivo
                readfile($zip_path);
                
                // Limpiar archivo temporal
                unlink($zip_path);
                exit();
            }
        } catch (Exception $e) {
            $message = '<div class="alert alert-danger">
                <strong>Error:</strong> ' . htmlspecialchars($e->getMessage()) . '
            </div>';
        }
    } else {
        $message = '<div class="alert alert-danger">Acceso no autorizado</div>';
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Descargar Álbum - <?php echo $gallery_data ? htmlspecialchars($gallery_data['nombre_evento']) : 'Galería'; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
:root {
            --primary-brown: #1a1a1a;
            --primary-pink: #F89E9D;
            --primary-cream: #2a2a2a;
        }
        
        body {
            background: linear-gradient(135deg, #000000 0%, var(--primary-pink) 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: #000000;
        }
        
        .main-card {
            backdrop-filter: blur(15px);
            background: rgba(42, 42, 42, 0.95);
            border-radius: 25px;
            box-shadow: 0 15px 45px rgba(0, 0, 0, 0.4);
            max-width: 600px;
            margin: 10vh auto;
            overflow: hidden;
        }
        
        .header-section {
            background: linear-gradient(45deg, #000000, var(--primary-pink));
            color: white;
            padding: 40px 30px;
            text-align: center;
            position: relative;
        }
        
        .header-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grain" width="100" height="100" patternUnits="userSpaceOnUse"><circle cx="25" cy="25" r="1" fill="white" opacity="0.1"/><circle cx="75" cy="75" r="1" fill="white" opacity="0.1"/></pattern></defs><rect width="100" height="100" fill="url(%23grain)"/></svg>');
            pointer-events: none;
        }
        
        .btn-download {
            background: linear-gradient(45deg, #000000, var(--primary-pink));
            border: none;
            border-radius: 50px;
            padding: 18px 40px;
            color: white;
            font-weight: 600;
            font-size: 16px;
            width: 100%;
            transition: all 0.4s ease;
            text-transform: uppercase;
            letter-spacing: 1px;
            position: relative;
            overflow: hidden;
        }
        
        .btn-download:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.4);
            color: white;
            background: linear-gradient(45deg, #333333, #f48b8a);
        }
        
        .btn-download:active {
            transform: translateY(-1px);
        }
        
        .btn-verify {
            background: linear-gradient(45deg, #000000, var(--primary-pink));
            border: none;
            border-radius: 50px;
            padding: 15px 30px;
            color: white;
            font-weight: 600;
            width: 100%;
            transition: all 0.3s ease;
        }
        
        .btn-verify:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.4);
            color: white;
            background: linear-gradient(45deg, #333333, #f48b8a);
        }
        
        .btn-paste {
            background: linear-gradient(45deg, var(--primary-pink), #000000);
            border: none;
            border-radius: 20px;
            padding: 10px 18px;
            color: white;
            font-weight: 600;
            font-size: 14px;
            transition: all 0.3s ease;
            box-shadow: 0 3px 10px rgba(248, 158, 157, 0.3);
        }
        
        .btn-paste:hover {
            color: white;
            transform: scale(1.05);
            box-shadow: 0 5px 15px rgba(248, 158, 157, 0.4);
            background: linear-gradient(45deg, #f48b8a, #333333);
        }
        
        .form-control {
            border-radius: 20px;
            border: 3px solid #444444;
            padding: 15px 25px;
            font-size: 16px;
            transition: all 0.3s ease;
            background: rgba(42, 42, 42, 0.9);
            color: #000000;
        }
        
        .form-control:focus {
            border-color: var(--primary-pink);
            box-shadow: 0 0 0 0.3rem rgba(248, 158, 157, 0.25);
            background: #2a2a2a;
            color: #000000;
        }
        
        .password-info {
            background: linear-gradient(135deg, rgba(0, 0, 0, 0.2), rgba(248, 158, 157, 0.1));
            border-radius: 20px;
            padding: 30px;
            margin: 30px 0;
            border: 2px solid rgba(255, 255, 255, 0.1);
        }
        
        .password-input-group {
            position: relative;
        }
        
        .paste-btn-container {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            z-index: 10;
        }
        
        .debug-info {
            background: #1a1a1a;
            border-radius: 15px;
            padding: 20px;
            font-size: 13px;
            margin-top: 20px;
            border-left: 4px solid #000000;
            color: #ccc;
        }
        
        .alert {
            border-radius: 15px;
            border: none;
            padding: 20px;
            margin: 20px 0;
        }
        
        .alert-success {
            background: linear-gradient(135deg, rgba(40, 167, 69, 0.2), rgba(248, 158, 157, 0.1));
            border-left: 4px solid #28a745;
            color: #20c997;
        }
        
        .alert-danger {
            background: linear-gradient(135deg, rgba(220, 53, 69, 0.2), rgba(248, 158, 157, 0.1));
            border-left: 4px solid #dc3545;
            color: #dc3545;
        }
        
        .alert-info {
            background: linear-gradient(135deg, rgba(0, 0, 0, 0.2), rgba(248, 158, 157, 0.1));
            border-left: 4px solid #000000;
            color: #000000;
        }
        
        .alert-warning {
            background: linear-gradient(135deg, rgba(248, 158, 157, 0.2), rgba(0, 0, 0, 0.1));
            border-left: 4px solid var(--primary-pink);
            color: var(--primary-pink);
        }
        
        .text-primary {
            color: #000000 !important;
        }
        
        .loading {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.9);
            z-index: 9999;
            justify-content: center;
            align-items: center;
        }
        
        .loading-content {
            background: #2a2a2a;
            padding: 40px;
            border-radius: 20px;
            text-align: center;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.6);
            max-width: 400px;
            color: #000000;
        }
        
        .spinner {
            border: 4px solid #1a1a1a;
            border-top: 4px solid var(--primary-pink);
            border-radius: 50%;
            width: 60px;
            height: 60px;
            animation: spin 1s linear infinite;
            margin: 0 auto 20px;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        .pulse {
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }
        
        .download-ready {
            background: linear-gradient(135deg, rgba(40, 167, 69, 0.2), rgba(248, 158, 157, 0.1));
            border-radius: 20px;
            padding: 30px;
            margin: 30px 0;
            border: 2px solid rgba(40, 167, 69, 0.3);
        }
        
        .btn-outline-secondary {
            border-color: #000000;
            color: #000000;
            border-radius: 25px;
            padding: 12px 25px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .btn-outline-secondary:hover {
            background-color: #000000;
            border-color: #000000;
            color: white;
            transform: translateY(-2px);
        }
        
        .text-success {
            color: #28a745 !important;
        }
        
        .text-muted {
            color: #666 !important;
        }
        
        /* Mejoras responsive */
        @media (max-width: 768px) {
            .main-card {
                margin: 5vh auto;
                max-width: 95%;
            }
            
            .header-section {
                padding: 30px 20px;
            }
            
            .btn-download, .btn-verify {
                padding: 15px 30px;
                font-size: 15px;
            }
            
            .password-info {
                padding: 20px;
                margin: 20px 0;
            }
        }
        
        /* Efectos adicionales */
        .main-card:hover {
            transform: translateY(-5px);
            transition: transform 0.3s ease;
        }
        
        .form-control:hover {
            border-color: var(--primary-pink);
        }
        
        .form-text {
            color: #000000 !important;
        }
    </style>
</head>
<body>
    <div class="loading" id="loadingScreen">
        <div class="loading-content">
            <div class="spinner"></div>
            <h5 style="color: var(--primary-brown);">Preparando descarga...</h5>
            <p class="text-muted">Creando archivo ZIP con todas las fotos</p>
            <small class="text-muted">Esto puede tomar unos segundos</small>
        </div>
    </div>

    <div class="container py-4">
        <div class="main-card">
            <div class="header-section">
                <i class="fas fa-download fa-4x mb-4 pulse"></i>
                <h1 class="mb-3">Descargar Álbum</h1>
                <?php if ($gallery_data): ?>
                    <h4 class="mb-2"><?php echo htmlspecialchars($gallery_data['nombre_evento']); ?></h4>
                    <p class="mb-0 opacity-75">
                        <i class="fas fa-user me-2"></i><?php echo htmlspecialchars($gallery_data['cliente_nombre']); ?>
                        <span class="mx-3">•</span>
                        <i class="fas fa-calendar me-2"></i><?php echo date('d/m/Y', strtotime($gallery_data['fecha_evento'])); ?>
                    </p>
                <?php endif; ?>
            </div>

            <div class="p-5">
                <?php echo $message; ?>

                <?php if ($gallery_data && !$show_download): ?>
                    <div class="password-info text-center">
                        <i class="fas fa-shield-alt fa-3x mb-4" style="color: var(--primary-brown);"></i>
                        <h4 class="text-primary mb-3">Área Protegida</h4>
                        <p class="text-muted mb-0">
                            Para acceder a todas las fotos del evento necesitas la contraseña 
                            especial proporcionada por el organizador.
                        </p>
                    </div>

                    <form method="POST" class="mt-5">
                        <div class="mb-4">
                            <label for="password" class="form-label h6">
                                <i class="fas fa-key me-2" style="color: var(--primary-brown);"></i>Contraseña de Acceso
                            </label>
                            <div class="password-input-group">
                                <input type="password" 
                                       class="form-control" 
                                       id="password" 
                                       name="password" 
                                       placeholder="Ingresa o pega la contraseña aquí" 
                                       required
                                       autocomplete="off" 
                                       style="padding-right: 100px;">
                                <div class="paste-btn-container">
                                    <button type="button" class="btn btn-paste" onclick="pastePassword()">
                                        <i class="fas fa-paste me-1"></i>Pegar
                                    </button>
                                </div>
                            </div>
                            <div class="form-text mt-3">
                                <i class="fas fa-info-circle me-1"></i>
                                Puedes pegar directamente la contraseña usando Ctrl+V o el botón "Pegar"
                            </div>
                        </div>
                        
                        <button type="submit" name="verify_password" class="btn btn-verify mb-3">
                            <i class="fas fa-unlock me-2"></i>Verificar Contraseña
                        </button>
                    </form>

                <?php elseif ($gallery_data && $show_download): ?>
                    <div class="text-center">
                        <i class="fas fa-check-circle fa-4x text-success mb-4"></i>
                        <h4 class="text-success mb-3">¡Acceso Autorizado!</h4>
                        <p class="text-muted mb-4 lead">
                            Tu descarga está lista. El archivo ZIP incluirá todas las fotos 
                            organizadas y nombradas con fecha y contribuyente.
                        </p>

                        <?php
                        try {
                            $sql = "SELECT COUNT(*) as total FROM fotos WHERE galeria_id = ?";
                            $stmt = $db->query($sql, [$galeria_id]);
                            $count = $stmt->fetch();
                            $total_fotos = $count['total'];
                        } catch (Exception $e) {
                            $total_fotos = 0;
                        }
                        ?>

                        <div class="download-ready">
                            <div class="row align-items-center">
                                <div class="col-md-8">
                                    <h5 class="mb-2">
                                        <i class="fas fa-images text-success me-2"></i>
                                        <strong><?php echo $total_fotos; ?></strong> fotos listas para descarga
                                    </h5>
                                    <p class="text-muted mb-0">
                                        Las fotos se descargarán organizadas por fecha y contribuyente
                                    </p>
                                </div>
                                <div class="col-md-4 text-center">
                                    <i class="fas fa-file-archive fa-3x text-success"></i>
                                </div>
                            </div>
                        </div>

                        <form method="POST" class="mb-4">
                            <input type="hidden" name="password_verified" value="<?php echo htmlspecialchars($gallery_data['token']); ?>">
                            <button type="submit" name="download" value="1" class="btn btn-download" onclick="showLoading()">
                                <i class="fas fa-cloud-download-alt me-2"></i>Descargar Álbum Completo
                            </button>
                        </form>

                        <div class="debug-info">
                            <h6 style="color: var(--primary-brown);"><i class="fas fa-cog me-2"></i>Información Técnica</h6>
                            <div class="row text-start">
                                <div class="col-6">
                                    <small>
                                        <strong>ZipArchive:</strong> <?php echo class_exists('ZipArchive') ? '✅ Disponible' : '❌ No disponible'; ?><br>
                                        <strong>Fotos en BD:</strong> <?php echo $total_fotos; ?>
                                    </small>
                                </div>
                                <div class="col-6">
                                    <small>
                                        <strong>Temp dir:</strong> <?php echo is_writable('./temp') ? '✅ Escribible' : '❌ Sin permisos'; ?><br>
                                        <strong>PHP Memory:</strong> <?php echo ini_get('memory_limit'); ?>
                                    </small>
                                </div>
                            </div>
                        </div>

                        <div class="mt-4">
                            <small class="text-muted">
                                <i class="fas fa-info-circle me-1"></i>
                                La descarga comenzará automáticamente cuando hagas clic en el botón
                            </small>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="text-center mt-5">
                    <a href="index.php" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-2"></i>Volver al Inicio
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Auto-focus en el campo de contraseña
        document.addEventListener('DOMContentLoaded', function() {
            const passwordField = document.getElementById('password');
            if (passwordField) {
                passwordField.focus();
            }
        });

        // Función mejorada para pegar contraseña
        async function pastePassword() {
            const passwordField = document.getElementById('password');
            const btn = event.target.closest('button');
            const originalHTML = btn.innerHTML;
            
            try {
                // Primero intentar con la API moderna del clipboard
                const text = await navigator.clipboard.readText();
                
                if (text && text.trim()) {
                    passwordField.value = text.trim();
                    showPasteSuccess(btn, originalHTML, passwordField);
                } else {
                    showPasteError('No hay texto en el portapapeles');
                }
                
            } catch (err) {
                // Si falla la API, intentar con método alternativo
                try {
                    // Crear un campo temporal para el pegado
                    const tempInput = document.createElement('input');
                    tempInput.style.position = 'absolute';
                    tempInput.style.left = '-9999px';
                    document.body.appendChild(tempInput);
                    
                    tempInput.focus();
                    document.execCommand('paste');
                    
                    if (tempInput.value && tempInput.value.trim()) {
                        passwordField.value = tempInput.value.trim();
                        showPasteSuccess(btn, originalHTML, passwordField);
                    } else {
                        // Si no funciona ningún método, solo mostrar que intentó pegar
                        btn.innerHTML = '<i class="fas fa-info me-1"></i>Usa Ctrl+V';
                        btn.style.background = 'linear-gradient(45deg, #17a2b8, #138496)';
                        
                        setTimeout(function() {
                            btn.innerHTML = originalHTML;
                            btn.style.background = 'linear-gradient(45deg, var(--primary-pink), var(--primary-brown))';
                        }, 2000);
                    }
                    
                    document.body.removeChild(tempInput);
                    
                } catch (fallbackErr) {
                    // Último recurso: solo indicar que use Ctrl+V
                    btn.innerHTML = '<i class="fas fa-keyboard me-1"></i>Usa Ctrl+V';
                    btn.style.background = 'linear-gradient(45deg, #6c757d, #495057)';
                    
                    setTimeout(function() {
                        btn.innerHTML = originalHTML;
                        btn.style.background = 'linear-gradient(45deg, var(--primary-pink), var(--primary-brown))';
                    }, 2000);
                }
            }
        }

        function showPasteSuccess(btn, originalHTML, passwordField) {
            passwordField.type = 'text'; // Mostrar brevemente
            
            btn.innerHTML = '<i class="fas fa-check me-1"></i>¡Pegado!';
            btn.style.background = 'linear-gradient(45deg, #28a745, #20c997)';
            
            setTimeout(function() {
                passwordField.type = 'password';
                btn.innerHTML = originalHTML;
                btn.style.background = 'linear-gradient(45deg, var(--primary-pink), var(--primary-brown))';
            }, 1500);
        }

        function showPasteError(message) {
            // No mostrar alert molesto, solo feedback visual silencioso
            console.log('Clipboard info:', message);
        }

        // Atajo de teclado mejorado para pegar (Ctrl+V)
        document.getElementById('password')?.addEventListener('keydown', function(e) {
            if (e.ctrlKey && e.keyCode === 86) {
                // Permitir el pegado nativo del navegador
                setTimeout(() => {
                    const field = e.target;
                    if (field.value) {
                        // Si se pegó algo, mostrar feedback visual
                        field.style.borderColor = '#28a745';
                        field.style.boxShadow = '0 0 0 0.2rem rgba(40, 167, 69, 0.25)';
                        
                        setTimeout(() => {
                            field.style.borderColor = 'var(--primary-pink)';
                            field.style.boxShadow = '0 0 0 0.3rem rgba(248, 158, 157, 0.25)';
                        }, 1000);
                    }
                }, 100);
            }
        });

        // También detectar pegado con clic derecho
        document.getElementById('password')?.addEventListener('paste', function(e) {
            const field = e.target;
            setTimeout(() => {
                if (field.value) {
                    field.style.borderColor = '#28a745';
                    field.style.boxShadow = '0 0 0 0.2rem rgba(40, 167, 69, 0.25)';
                    
                    setTimeout(() => {
                        field.style.borderColor = 'var(--primary-pink)';
                        field.style.boxShadow = '0 0 0 0.3rem rgba(248, 158, 157, 0.25)';
                    }, 1000);
                }
            }, 100);
        });

        // Mostrar pantalla de carga al descargar
        function showLoading() {
            document.getElementById('loadingScreen').style.display = 'flex';
            
            // Ocultar después de 30 segundos por seguridad
            setTimeout(function() {
                document.getElementById('loadingScreen').style.display = 'none';
            }, 30000);
        }

        // Ocultar carga si la página se recarga (por error)
        window.addEventListener('beforeunload', function() {
            document.getElementById('loadingScreen').style.display = 'none';
        });

        // Validación del formulario
        document.querySelector('form[method="POST"]')?.addEventListener('submit', function(e) {
            const password = document.getElementById('password')?.value;
            if (password && password.length < 10) {
                if (!confirm('La contraseña parece muy corta. ¿Estás seguro de que es correcta?')) {
                    e.preventDefault();
                }
            }
        });

        // Efectos de entrada
        document.addEventListener('DOMContentLoaded', function() {
            const mainCard = document.querySelector('.main-card');
            if (mainCard) {
                mainCard.style.opacity = '0';
                mainCard.style.transform = 'translateY(30px)';
                
                setTimeout(() => {
                    mainCard.style.transition = 'all 0.6s ease';
                    mainCard.style.opacity = '1';
                    mainCard.style.transform = 'translateY(0)';
                }, 100);
            }
        });

        // Mejorar experiencia del formulario
        const passwordField = document.getElementById('password');
        if (passwordField) {
            passwordField.addEventListener('input', function() {
                const value = this.value;
                if (value.length > 0) {
                    this.style.borderColor = 'var(--primary-pink)';
                } else {
                    this.style.borderColor = 'var(--primary-cream)';
                }
            });
        }
    </script>
</body>
</html>

<!-- 
FUNCIONALIDAD DE MARCOS COMENTADA - Para implementar más adelante:

1. Incluir require_once 'image_frame_processor.php'; al inicio
2. Agregar selector de tipos de descarga (original vs con marcos)
3. Agregar selector de tipos de evento (boda, cumpleaños, etc.)
4. Modificar la lógica de descarga para procesar imágenes con marcos

El código del procesador de marcos está listo en el artifact anterior
-->