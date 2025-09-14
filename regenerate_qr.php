<?php
/**
 * Script para regenerar códigos QR con URLs correctas
 * Ejecutar este script para corregir códigos QR existentes
 */

require_once 'config/database.php';

// Verificación de acceso (opcional)
$password = 'charlyadmin 2415691611+Charly';
if (!isset($_GET['password']) || $_GET['password'] !== $password) {
    die('Error: Acceso no autorizado. Usar: ?password=charlyadmin 2415691611+Charly');
}

try {
    $db = Database::getInstance();
    
    // Obtener todas las galerías
    $sql = "SELECT id, token, nombre_evento FROM galerias ORDER BY id";
    $stmt = $db->query($sql);
    $galleries = $stmt->fetchAll();
    
    $updated = 0;
    $errors = 0;
    
    echo "<h2>Regenerando códigos QR</h2>\n";
    echo "<p>BASE_URL configurado: " . BASE_URL . "</p>\n";
    
    foreach ($galleries as $gallery) {
        try {
            // Generar nueva URL del QR
            $gallery_url = rtrim(BASE_URL, '/') . "/galeria.php?token=" . $gallery['token'];
            $qr_url = "https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=" . urlencode($gallery_url);
            
            echo "<div style='border: 1px solid #ccc; margin: 10px; padding: 10px;'>\n";
            echo "<h4>Galería ID: {$gallery['id']} - {$gallery['nombre_evento']}</h4>\n";
            echo "<p>URL generada: <a href='{$gallery_url}' target='_blank'>{$gallery_url}</a></p>\n";
            
            // Crear directorio si no existe
            if (!is_dir(QR_DIR)) {
                mkdir(QR_DIR, 0755, true);
            }
            
            $qr_filename = "qr_galeria_" . $gallery['id'] . ".png";
            $qr_path = QR_DIR . $qr_filename;
            
            // Descargar nuevo QR
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
                
                // Actualizar base de datos
                $update_sql = "UPDATE galerias SET qr_path = ? WHERE id = ?";
                $db->query($update_sql, [$qr_path, $gallery['id']]);
                
                echo "<p style='color: green;'>✅ QR regenerado exitosamente</p>\n";
                echo "<img src='{$qr_path}' alt='QR Code' style='max-width: 150px;'><br>\n";
                $updated++;
            } else {
                echo "<p style='color: red;'>❌ Error descargando QR (HTTP: {$http_code})</p>\n";
                $errors++;
            }
            
            echo "</div>\n";
            
        } catch (Exception $e) {
            echo "<p style='color: red;'>❌ Error procesando galería {$gallery['id']}: " . $e->getMessage() . "</p>\n";
            $errors++;
        }
    }
    
    echo "<h3>Resumen:</h3>\n";
    echo "<p>QRs actualizados: {$updated}</p>\n";
    echo "<p>Errores: {$errors}</p>\n";
    echo "<p>Total galerías procesadas: " . count($galleries) . "</p>\n";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error fatal: " . $e->getMessage() . "</p>\n";
}
?>