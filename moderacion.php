<?php
/**
 * PANEL DE MODERACIÓN DE FOTOS
 * Archivo: moderacion.php
 * Sistema para ver, filtrar y eliminar fotos inapropiadas
 */

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
    if (file_exists('login_form.php')) {
        include 'login_form.php';
    } else {
        // Formulario de login inline simple
        echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Login</title><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet"></head><body class="bg-light d-flex align-items-center min-vh-100"><div class="container"><div class="row justify-content-center"><div class="col-md-6"><div class="card"><div class="card-body"><h5 class="card-title text-center">Acceso Administrador</h5>';
        if (isset($auth_error)) echo '<div class="alert alert-danger">' . $auth_error . '</div>';
        echo '<form method="POST"><div class="mb-3"><input type="password" class="form-control" name="password" placeholder="Contraseña" required></div><button type="submit" class="btn btn-primary w-100">Entrar</button></form></div></div></div></div></div></body></html>';
    }
    exit();
}

require_once 'config/database.php';

$message = '';
$db = null;

try {
    $db = Database::getInstance();
} catch (Exception $e) {
    $message = '<div class="alert alert-danger">Error de conexión: ' . htmlspecialchars($e->getMessage()) . '</div>';
}

// FUNCIÓN SANITIZE_INPUT
function sanitize_input($data) {
    if (!is_string($data)) return '';
    return htmlspecialchars(trim(stripslashes($data)), ENT_QUOTES, 'UTF-8');
}

// Función para eliminar foto
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_photo']) && $db) {
    $foto_id = (int)$_POST['foto_id'];
    $motivo = sanitize_input($_POST['motivo'] ?? 'Sin motivo especificado');
    
    try {
        // 1. Obtener información de la foto
        $sql = "SELECT f.*, g.nombre_evento 
                FROM fotos f 
                JOIN galerias g ON f.galeria_id = g.id 
                WHERE f.id = ?";
        $stmt = $db->query($sql, [$foto_id]);
        $foto = $stmt->fetch();
        
        if (!$foto) {
            $message = '<div class="alert alert-warning">❌ Foto no encontrada en la base de datos (ID: ' . $foto_id . ')</div>';
        } else {
            $ruta_bd = $foto['ruta_foto'];
            $archivo_eliminado = false;
            $ruta_utilizada = '';
            
            // 2. Intentar eliminar archivo físico
            if (!empty($ruta_bd)) {
                // Ruta directa
                if (file_exists($ruta_bd)) {
                    $archivo_eliminado = unlink($ruta_bd);
                    $ruta_utilizada = $ruta_bd;
                }
                // Sin uploads/
                else if (file_exists(str_replace('uploads/', '', $ruta_bd))) {
                    $ruta_sin_uploads = str_replace('uploads/', '', $ruta_bd);
                    $archivo_eliminado = unlink($ruta_sin_uploads);
                    $ruta_utilizada = $ruta_sin_uploads;
                }
                // Con ./
                else if (file_exists('./' . $ruta_bd)) {
                    $archivo_eliminado = unlink('./' . $ruta_bd);
                    $ruta_utilizada = './' . $ruta_bd;
                }
            }
            
            // 3. Eliminar registros relacionados
            try {
                $db->query("DELETE FROM foto_likes WHERE foto_id = ?", [$foto_id]);
            } catch (Exception $e) {}
            
            try {
                $db->query("DELETE FROM foto_reportes WHERE foto_id = ?", [$foto_id]);
            } catch (Exception $e) {}
            
            // 4. Eliminar registro principal
            $sql_delete = "DELETE FROM fotos WHERE id = ?";
            $result = $db->query($sql_delete, [$foto_id]);
            
            // 5. Mostrar resultado
            if ($result) {
                if ($archivo_eliminado) {
                    $message = '<div class="alert alert-success">
                        ✅ <strong>Foto eliminada completamente</strong><br>
                        📸 Álbum: ' . htmlspecialchars($foto['nombre_evento']) . '<br>
                        📝 Motivo: ' . htmlspecialchars($motivo) . '<br>
                        📁 Archivo eliminado: ' . htmlspecialchars($ruta_utilizada) . '
                    </div>';
                } else {
                    $message = '<div class="alert alert-warning">
                        ⚠️ <strong>Foto eliminada de la base de datos</strong><br>
                        📸 Álbum: ' . htmlspecialchars($foto['nombre_evento']) . '<br>
                        📝 Motivo: ' . htmlspecialchars($motivo) . '<br>
                        ❌ Archivo físico no encontrado: ' . htmlspecialchars($ruta_bd) . '
                    </div>';
                }
            } else {
                $message = '<div class="alert alert-danger">❌ Error eliminando de la base de datos</div>';
            }
        }
    } catch (Exception $e) {
        $message = '<div class="alert alert-danger">❌ Error: ' . htmlspecialchars($e->getMessage()) . '</div>';
    }
}

// Función para marcar como reportada
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['report_photo']) && $db) {
    $foto_id = (int)$_POST['foto_id'];
    $motivo = sanitize_input($_POST['motivo'] ?? 'Contenido inapropiado');
    
    try {
        $sql = "UPDATE fotos SET reportada = 1, motivo_reporte = ?, fecha_reporte = NOW() WHERE id = ?";
        $result = $db->query($sql, [$motivo, $foto_id]);
        
        if ($result) {
            $message = '<div class="alert alert-warning">
                <i class="fas fa-flag"></i> <strong>Foto marcada como reportada</strong><br>
                <small><strong>Motivo:</strong> ' . htmlspecialchars($motivo) . '</small>
            </div>';
        } else {
            $message = '<div class="alert alert-danger">❌ Error actualizando el estado de la foto</div>';
        }
    } catch (Exception $e) {
        $message = '<div class="alert alert-danger">❌ Error reportando foto: ' . htmlspecialchars($e->getMessage()) . '</div>';
    }
}

// Obtener filtros
$filtro_galeria = $_GET['galeria'] ?? '';
$filtro_fecha = $_GET['fecha'] ?? '';
$filtro_reportadas = isset($_GET['reportadas']) ? 1 : 0;
$orden = $_GET['orden'] ?? 'recientes';

// Construir consulta con filtros
$where_conditions = [];
$params = [];

if ($filtro_galeria) {
    $where_conditions[] = "g.id = ?";
    $params[] = (int)$filtro_galeria;
}

if ($filtro_fecha) {
    $where_conditions[] = "DATE(f.fecha_subida) = ?";
    $params[] = $filtro_fecha;
}

if ($filtro_reportadas) {
    $where_conditions[] = "f.reportada = 1";
}

$where_clause = empty($where_conditions) ? '' : 'WHERE ' . implode(' AND ', $where_conditions);

// Determinar orden
$order_clause = match($orden) {
    'antiguos' => 'ORDER BY f.fecha_subida ASC',
    'galeria' => 'ORDER BY g.nombre_evento ASC, f.fecha_subida DESC',
    'reportadas' => 'ORDER BY f.reportada DESC, f.fecha_subida DESC',
    default => 'ORDER BY f.fecha_subida DESC'
};

$fotos = [];
$galerias = [];

if ($db) {
    try {
        $sql = "SELECT f.*, g.nombre_evento, g.cliente_nombre, g.fecha_evento,
                       COALESCE(f.reportada, 0) as es_reportada,
                       COALESCE(f.motivo_reporte, '') as motivo_reporte
                FROM fotos f 
                JOIN galerias g ON f.galeria_id = g.id 
                {$where_clause} 
                {$order_clause}";
        
        $stmt = $db->query($sql, $params);
        $fotos = $stmt->fetchAll();
        
        $galerias_sql = "SELECT id, nombre_evento, cliente_nombre FROM galerias ORDER BY nombre_evento";
        $galerias_stmt = $db->query($galerias_sql);
        $galerias = $galerias_stmt->fetchAll();
        
    } catch (Exception $e) {
        $message .= '<div class="alert alert-warning">Error obteniendo fotos: ' . htmlspecialchars($e->getMessage()) . '</div>';
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Moderación de Fotos - Panel Admin</title>
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
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: white;
        }
        
        .navbar-dark {
            background: linear-gradient(135deg, #000000 0%, var(--primary-pink) 100%) !important;
        }
        
        .photo-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        
        .photo-card {
            background: rgba(0, 0, 0, 0.8);
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.3);
            transition: all 0.3s ease;
            position: relative;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .photo-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 30px rgba(0, 0, 0, 0.4);
        }
        
        .photo-card.reported {
            border: 3px solid #dc3545;
            box-shadow: 0 4px 15px rgba(220, 53, 69, 0.5);
        }
        
        .photo-image {
            width: 100%;
            height: 200px;
            object-fit: cover;
            cursor: pointer;
        }
        
        .photo-info {
            padding: 15px;
        }
        
        .photo-gallery {
            color: white;
            font-weight: 600;
            font-size: 0.9rem;
            margin-bottom: 5px;
            text-shadow: 0 1px 2px rgba(0, 0, 0, 0.3);
        }
        
        .photo-date {
            color: #ccc;
            font-size: 0.8rem;
            margin-bottom: 10px;
            text-shadow: 0 1px 2px rgba(0, 0, 0, 0.3);
        }
        
        .photo-actions {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }
        
        .btn-sm {
            padding: 5px 10px;
            font-size: 0.8rem;
            border-radius: 20px;
        }
        
        .filters-card {
            background: rgba(0, 0, 0, 0.8);
            border-radius: 15px;
            padding: 20px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.3);
            margin-bottom: 20px;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .filters-card h5 {
            color: white;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.5);
        }
        
        .filters-card label {
            color: white;
            text-shadow: 0 1px 2px rgba(0, 0, 0, 0.3);
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .stat-card {
            background: linear-gradient(135deg, #000000 0%, var(--primary-pink) 100%);
            color: white;
            padding: 20px;
            border-radius: 15px;
            text-align: center;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.5);
        }
        
        .stat-card h3 {
            color: white;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.5);
        }
        
        .stat-card p {
            color: white;
            text-shadow: 0 1px 2px rgba(0, 0, 0, 0.3);
        }
        
        .reported-badge {
            position: absolute;
            top: 10px;
            right: 10px;
            background: #dc3545;
            color: white;
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 0.7rem;
            font-weight: bold;
        }
        
        .modal-image {
            max-width: 100%;
            max-height: 70vh;
            object-fit: contain;
        }
        
        .modal-content {
            background: rgba(0, 0, 0, 0.9);
            border: 1px solid rgba(255, 255, 255, 0.1);
            color: white;
        }
        
        .modal-header {
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            background: rgba(0, 0, 0, 0.95);
        }
        
        .modal-body {
            background: rgba(0, 0, 0, 0.9);
        }
        
        .modal-footer {
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            background: rgba(0, 0, 0, 0.95);
        }
        
        .modal-title {
            color: white;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.5);
        }
        
        .form-control {
            background: #1a1a1a;
            border: 1px solid #444;
            color: white;
        }
        
        .form-control:focus {
            border-color: var(--primary-pink);
            box-shadow: 0 0 0 0.2rem rgba(248, 158, 157, 0.25);
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
            box-shadow: 0 0 0 0.2rem rgba(248, 158, 157, 0.25);
            background: #1a1a1a;
            color: white;
        }
        
        .form-label {
            color: white;
            font-weight: 600;
        }
        
        .btn-primary {
            background: linear-gradient(45deg, #000000, var(--primary-pink));
            border: none;
            border-radius: 25px;
            color: white;
        }
        
        .btn-primary:hover {
            background: linear-gradient(45deg, #333333, #f48b8a);
            transform: translateY(-2px);
            color: white;
        }
        
        .btn-danger {
            background: linear-gradient(45deg, #dc3545, #c82333);
            border: none;
            color: white;
        }
        
        .btn-danger:hover {
            background: linear-gradient(45deg, #c82333, #bd2130);
            color: white;
        }
        
        .btn-warning {
            background: linear-gradient(45deg, #ffc107, #fd7e14);
            border: none;
            color: #212529;
        }
        
        .btn-warning:hover {
            background: linear-gradient(45deg, #e0a800, #e8590c);
            color: #212529;
        }
        
        .btn-success {
            background: linear-gradient(45deg, #28a745, #20c997);
            border: none;
            color: white;
        }
        
        .btn-success:hover {
            background: linear-gradient(45deg, #218838, #1ea085);
            color: white;
        }
        
        .btn-info {
            background: linear-gradient(45deg, #17a2b8, #138496);
            border: none;
            color: white;
        }
        
        .btn-info:hover {
            background: linear-gradient(45deg, #138496, #117a8b);
            color: white;
        }
        
        .btn-secondary {
            background: linear-gradient(45deg, #6c757d, #5a6268);
            border: none;
            color: white;
        }
        
        .btn-secondary:hover {
            background: linear-gradient(45deg, #5a6268, #495057);
            color: white;
        }
        
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #ccc;
        }
        
        .empty-state i {
            font-size: 4rem;
            margin-bottom: 20px;
            color: var(--primary-pink);
        }
        
        .empty-state h5 {
            color: white;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.5);
        }
        
        .empty-state p {
            color: #ccc;
            text-shadow: 0 1px 2px rgba(0, 0, 0, 0.3);
        }
        
        .alert {
            border-radius: 15px;
            border: none;
            padding: 15px 20px;
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
        
        .alert-info {
            background: linear-gradient(45deg, rgba(23, 162, 184, 0.2), rgba(19, 132, 150, 0.2));
            color: #17a2b8;
            border: 1px solid rgba(23, 162, 184, 0.3);
        }
        
        .navbar-brand {
            color: white !important;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.5);
        }
        
        .nav-link {
            color: white !important;
            text-shadow: 0 1px 2px rgba(0, 0, 0, 0.3);
        }
        
        .navbar-text {
            color: white !important;
            text-shadow: 0 1px 2px rgba(0, 0, 0, 0.3);
        }
        
        .container h1, .container h2, .container h3, .container h4, .container h5, .container h6 {
            color: white;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.5);
        }
        
        .text-muted {
            color: #ccc !important;
        }
        
        .badge {
            background: var(--primary-pink);
            color: white;
        }
        
        .badge-danger {
            background: #dc3545;
        }
        
        .badge-warning {
            background: #ffc107;
            color: #212529;
        }
        
        .badge-success {
            background: #28a745;
        }
        
        .badge-info {
            background: #17a2b8;
        }
        
        @media (max-width: 768px) {
            .photo-grid {
                grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
                gap: 15px;
            }
            
            .photo-actions {
                flex-direction: column;
            }
            
            .photo-actions .btn {
                width: 100%;
                margin-bottom: 5px;
            }
            
            .filters-card {
                padding: 15px;
            }
            
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 10px;
            }
        }
        
        @media (max-width: 576px) {
            .photo-grid {
                grid-template-columns: 1fr;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand" href="#" style="font-weight: 600;">
                <i class="fas fa-shield-alt"></i> Moderación de Fotos
            </a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="admin.php">
                    <i class="fas fa-cog"></i> Panel Admin
                </a>
                <a class="nav-link" href="index.php">
                    <i class="fas fa-home"></i> Inicio
                </a>
                <a class="nav-link" href="?logout=1">
                    <i class="fas fa-sign-out-alt"></i> Salir
                </a>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <?php if ($message): ?>
            <?php echo $message; ?>
        <?php endif; ?>
        
        <!-- Estadísticas -->
        <div class="stats-grid">
            <div class="stat-card">
                <h3><?php echo count($fotos); ?></h3>
                <p class="mb-0">Total Fotos</p>
            </div>
            <div class="stat-card">
                <h3><?php echo count(array_filter($fotos, fn($f) => $f['es_reportada'])); ?></h3>
                <p class="mb-0">Reportadas</p>
            </div>
            <div class="stat-card">
                <h3><?php echo count($galerias); ?></h3>
                <p class="mb-0">Galerías Activas</p>
            </div>
            <div class="stat-card">
                <h3><?php echo count(array_filter($fotos, fn($f) => date('Y-m-d', strtotime($f['fecha_subida'])) === date('Y-m-d'))); ?></h3>
                <p class="mb-0">Subidas Hoy</p>
            </div>
        </div>

        <!-- Filtros -->
        <div class="filters-card">
            <h5><i class="fas fa-filter"></i> Filtros y Búsqueda</h5>
            <form method="GET" class="row g-3">
                <div class="col-md-3">
                    <label for="galeria" class="form-label">Galería</label>
                    <select class="form-select" id="galeria" name="galeria">
                        <option value="">Todas las galerías</option>
                        <?php foreach ($galerias as $g): ?>
                            <option value="<?php echo $g['id']; ?>" <?php echo $filtro_galeria == $g['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($g['nombre_evento']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="fecha" class="form-label">Fecha de Subida</label>
                    <input type="date" class="form-control" id="fecha" name="fecha" value="<?php echo htmlspecialchars($filtro_fecha); ?>">
                </div>
                <div class="col-md-2">
                    <label for="orden" class="form-label">Ordenar por</label>
                    <select class="form-select" id="orden" name="orden">
                        <option value="recientes" <?php echo $orden === 'recientes' ? 'selected' : ''; ?>>Más recientes</option>
                        <option value="antiguos" <?php echo $orden === 'antiguos' ? 'selected' : ''; ?>>Más antiguos</option>
                        <option value="galeria" <?php echo $orden === 'galeria' ? 'selected' : ''; ?>>Por galería</option>
                        <option value="reportadas" <?php echo $orden === 'reportadas' ? 'selected' : ''; ?>>Reportadas primero</option>
                    </select>
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="reportadas" name="reportadas" <?php echo $filtro_reportadas ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="reportadas">
                            Solo reportadas
                        </label>
                    </div>
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-search"></i> Filtrar
                    </button>
                </div>
            </form>
            
            <div class="mt-3 d-flex gap-2 flex-wrap">
                <a href="?" class="btn btn-outline-secondary btn-sm">
                    <i class="fas fa-times"></i> Limpiar filtros
                </a>
                <a href="?reportadas=1" class="btn btn-outline-warning btn-sm">
                    <i class="fas fa-flag"></i> Ver solo reportadas
                </a>
                <a href="?fecha=<?php echo date('Y-m-d'); ?>" class="btn btn-outline-info btn-sm">
                    <i class="fas fa-calendar"></i> Subidas hoy
                </a>
            </div>
        </div>

        <!-- Grid de fotos -->
        <?php if (empty($fotos)): ?>
            <div class="empty-state">
                <i class="fas fa-images"></i>
                <h5>No se encontraron fotos</h5>
                <p>No hay fotos que coincidan con los filtros seleccionados</p>
            </div>
        <?php else: ?>
            <div class="photo-grid">
                <?php foreach ($fotos as $foto): ?>
                    <div class="photo-card <?php echo $foto['es_reportada'] ? 'reported' : ''; ?>">
                        <?php if ($foto['es_reportada']): ?>
                            <div class="reported-badge" title="Foto reportada: <?php echo htmlspecialchars($foto['motivo_reporte']); ?>">
                                <i class="fas fa-flag"></i> REPORTADA
                            </div>
                        <?php endif; ?>
                        
                        <img src="<?php echo htmlspecialchars($foto['ruta_foto']); ?>" 
                             alt="Foto" 
                             class="photo-image"
                             onclick="openImageModal('<?php echo htmlspecialchars($foto['ruta_foto']); ?>', <?php echo $foto['id']; ?>, '<?php echo addslashes($foto['nombre_evento']); ?>')"
                             loading="lazy">
                        
                        <div class="photo-info">
                            <div class="photo-gallery">
                                <i class="fas fa-camera"></i> <?php echo htmlspecialchars($foto['nombre_evento']); ?>
                            </div>
                            <div class="photo-date">
                                <i class="fas fa-clock"></i> 
                                <?php echo date('d/m/Y H:i', strtotime($foto['fecha_subida'])); ?>
                            </div>
                            
                            <?php if ($foto['es_reportada']): ?>
                                <div class="alert alert-warning p-2 mb-2">
                                    <small>
                                        <i class="fas fa-exclamation-triangle"></i>
                                        <strong>Reportada:</strong> <?php echo htmlspecialchars($foto['motivo_reporte']); ?>
                                    </small>
                                </div>
                            <?php endif; ?>
                            
                            <div class="photo-actions">
                                <button class="btn btn-info btn-sm" 
                                        onclick="openImageModal('<?php echo htmlspecialchars($foto['ruta_foto']); ?>', <?php echo $foto['id']; ?>, '<?php echo addslashes($foto['nombre_evento']); ?>')">
                                    <i class="fas fa-eye"></i> Ver
                                </button>
                                
                                <?php if (!$foto['es_reportada']): ?>
                                    <button class="btn btn-warning btn-sm" 
                                            onclick="reportPhoto(<?php echo $foto['id']; ?>, '<?php echo addslashes($foto['nombre_evento']); ?>')">
                                        <i class="fas fa-flag"></i> Reportar
                                    </button>
                                <?php endif; ?>
                                
                                <button class="btn btn-danger btn-sm" 
                                        onclick="deletePhoto(<?php echo $foto['id']; ?>, '<?php echo addslashes($foto['nombre_evento']); ?>')">
                                    <i class="fas fa-trash"></i> Eliminar
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Modal para ver imagen completa -->
    <div class="modal fade" id="imageModal" tabindex="-1" aria-labelledby="imageModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="imageModalLabel">
                        <i class="fas fa-image"></i> Vista previa de imagen
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-center">
                    <img id="modalImage" src="" alt="Imagen completa" class="modal-image">
                    <div id="modalImageInfo" class="mt-3"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-warning" onclick="reportCurrentPhoto()">
                        <i class="fas fa-flag"></i> Reportar
                    </button>
                    <button type="button" class="btn btn-danger" onclick="deleteCurrentPhoto()">
                        <i class="fas fa-trash"></i> Eliminar
                    </button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para confirmar eliminación -->
    <div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="deleteModalLabel">
                        <i class="fas fa-exclamation-triangle"></i> Confirmar Eliminación
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" id="deleteForm">
                    <div class="modal-body">
                        <input type="hidden" name="foto_id" id="deletePhotoId" value="">
                        <input type="hidden" name="delete_photo" value="1">
                        
                        <p><strong>¿Estás seguro de eliminar esta foto?</strong></p>
                        <p id="deletePhotoInfo"></p>
                        
                        <div class="mb-3">
                            <label for="deleteMotivo" class="form-label">Motivo de eliminación:</label>
                            <select class="form-select" id="deleteMotivo" name="motivo" required>
                                <option value="">Seleccionar motivo...</option>
                                <option value="Contenido inapropiado">Contenido inapropiado</option>
                                <option value="Contenido ofensivo">Contenido ofensivo</option>
                                <option value="Spam o publicidad">Spam o publicidad</option>
                                <option value="Violación de derechos de autor">Violación de derechos de autor</option>
                                <option value="Contenido duplicado">Contenido duplicado</option>
                                <option value="Calidad deficiente">Calidad deficiente</option>
                                <option value="Solicitud del cliente">Solicitud del cliente</option>
                                <option value="Otros">Otros</option>
                            </select>
                        </div>
                        
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle"></i>
                            <strong>Advertencia:</strong> Esta acción no se puede deshacer. La foto será eliminada permanentemente.
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" name="delete_photo" class="btn btn-danger">
                            <i class="fas fa-trash"></i> Eliminar Foto
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal para reportar -->
    <div class="modal fade" id="reportModal" tabindex="-1" aria-labelledby="reportModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-warning text-dark">
                    <h5 class="modal-title" id="reportModalLabel">
                        <i class="fas fa-flag"></i> Reportar Foto
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" id="reportForm">
                    <div class="modal-body">
                        <input type="hidden" name="foto_id" id="reportPhotoId">
                        <input type="hidden" name="report_photo" value="1">
                        
                        <p>Marcar esta foto como reportada para revisión posterior:</p>
                        <p id="reportPhotoInfo"></p>
                        
                        <div class="mb-3">
                            <label for="reportMotivo" class="form-label">Motivo del reporte:</label>
                            <select class="form-select" id="reportMotivo" name="motivo" required>
                                <option value="">Seleccionar motivo...</option>
                                <option value="Contenido inapropiado">Contenido inapropiado</option>
                                <option value="Contenido ofensivo">Contenido ofensivo</option>
                                <option value="Spam">Spam</option>
                                <option value="Revisión necesaria">Necesita revisión manual</option>
                                <option value="Calidad dudosa">Calidad dudosa</option>
                                <option value="Otros">Otros</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" name="report_photo" class="btn btn-warning">
                            <i class="fas fa-flag"></i> Reportar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Variables globales
        let currentPhotoId = null;
        let currentPhotoGallery = null;

        // Función para abrir modal de imagen
        function openImageModal(imageSrc, photoId, galleryName) {
            currentPhotoId = photoId;
            currentPhotoGallery = galleryName;
            
            document.getElementById('modalImage').src = imageSrc;
            document.getElementById('modalImageInfo').innerHTML = 
                '<strong>Galería:</strong> ' + galleryName + '<br>' +
                '<strong>ID de foto:</strong> ' + photoId;
            
            const modal = new bootstrap.Modal(document.getElementById('imageModal'));
            modal.show();
        }

        // Función para reportar foto desde modal
        function reportCurrentPhoto() {
            if (currentPhotoId) {
                reportPhoto(currentPhotoId, currentPhotoGallery);
                bootstrap.Modal.getInstance(document.getElementById('imageModal')).hide();
            }
        }

        // Función para eliminar foto desde modal
        function deleteCurrentPhoto() {
            if (currentPhotoId) {
                deletePhoto(currentPhotoId, currentPhotoGallery);
                bootstrap.Modal.getInstance(document.getElementById('imageModal')).hide();
            }
        }

        // Función para mostrar modal de eliminación
        function deletePhoto(photoId, galleryName) {
            document.getElementById('deletePhotoId').value = photoId;
            document.getElementById('deletePhotoInfo').innerHTML = 
                '<strong>Galería:</strong> ' + galleryName + '<br>' +
                '<strong>ID de foto:</strong> ' + photoId;
            
            const modal = new bootstrap.Modal(document.getElementById('deleteModal'));
            modal.show();
        }

        // Función para mostrar modal de reporte
        function reportPhoto(photoId, galleryName) {
            document.getElementById('reportPhotoId').value = photoId;
            document.getElementById('reportPhotoInfo').innerHTML = 
                '<strong>Galería:</strong> ' + galleryName + '<br>' +
                '<strong>ID de foto:</strong> ' + photoId;
            
            const modal = new bootstrap.Modal(document.getElementById('reportModal'));
            modal.show();
        }

        // Confirmar antes de enviar formulario de eliminación
        document.getElementById('deleteForm').addEventListener('submit', function(e) {
            const motivo = document.getElementById('deleteMotivo').value;
            if (!motivo) {
                e.preventDefault();
                alert('Por favor selecciona un motivo para la eliminación');
                return false;
            }
            
            const confirmMsg = '¿Confirmas que deseas eliminar esta foto?\n\n' +
                'Esta acción NO se puede deshacer.';
            
            if (!confirm(confirmMsg)) {
                e.preventDefault();
                return false;
            }
            
            // Mostrar loading en el botón
            const btn = e.target.querySelector('button[type="submit"]');
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Eliminando...';
            btn.disabled = true;
        });

        // Confirmar antes de enviar formulario de reporte
        document.getElementById('reportForm').addEventListener('submit', function(e) {
            const motivo = document.getElementById('reportMotivo').value;
            if (!motivo) {
                e.preventDefault();
                alert('Por favor selecciona un motivo para el reporte');
                return false;
            }
            
            // Mostrar loading en el botón
            const btn = e.target.querySelector('button[type="submit"]');
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Reportando...';
            btn.disabled = true;
        });

        // Auto-cerrar alertas después de 5 segundos
        setTimeout(function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(function(alert) {
                if (!alert.classList.contains('alert-warning')) {
                    alert.style.opacity = '0';
                    alert.style.transition = 'opacity 0.5s ease';
                    setTimeout(() => {
                        if (alert.parentNode) {
                            alert.parentNode.removeChild(alert);
                        }
                    }, 500);
                }
            });
        }, 5000);

        // Inicialización cuando el DOM esté listo
        document.addEventListener('DOMContentLoaded', function() {
            console.log('✅ Sistema de moderación cargado correctamente');
            console.log('📊 Total de fotos: <?php echo count($fotos); ?>');
            
            // Animaciones de entrada para las tarjetas
            const cards = document.querySelectorAll('.photo-card');
            cards.forEach((card, index) => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(20px)';
                
                setTimeout(() => {
                    card.style.transition = 'all 0.6s ease';
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }, index * 50);
            });

            // Contador de fotos reportadas
            const reportedCount = document.querySelectorAll('.photo-card.reported').length;
            if (reportedCount > 0) {
                console.log(`⚠️ ${reportedCount} foto(s) reportada(s) requieren atención`);
            }
        });

        // Manejar errores de JavaScript
        window.onerror = function(msg, url, lineNo, columnNo, error) {
            console.error('Error JS:', msg, 'en línea', lineNo);
            return false;
        };

    </script>
</body>
</html>