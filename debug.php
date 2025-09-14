<?php
/**
 * Script de debug para verificar URLs de QR
 */

require_once 'config/database.php';

echo "<h2>Debug de URLs de QR</h2>\n";
echo "<p><strong>BASE_URL configurado:</strong> " . BASE_URL . "</p>\n";

// Verificar $_SERVER variables
echo "<h3>Variables del servidor:</h3>\n";
echo "<ul>\n";
echo "<li>HTTP_HOST: " . ($_SERVER['HTTP_HOST'] ?? 'no definido') . "</li>\n";
echo "<li>REQUEST_URI: " . ($_SERVER['REQUEST_URI'] ?? 'no definido') . "</li>\n";
echo "<li>SCRIPT_NAME: " . ($_SERVER['SCRIPT_NAME'] ?? 'no definido') . "</li>\n";
echo "<li>HTTPS: " . ($_SERVER['HTTPS'] ?? 'no definido') . "</li>\n";
echo "</ul>\n";

// Simular generación de URL
$test_token = "test123456789";
$gallery_url = rtrim(BASE_URL, '/') . "/galeria.php?token=" . $test_token;

echo "<h3>Prueba de generación de URL:</h3>\n";
echo "<p><strong>Token de prueba:</strong> {$test_token}</p>\n";
echo "<p><strong>URL generada:</strong> <a href='{$gallery_url}' target='_blank'>{$gallery_url}</a></p>\n";

// Mostrar algunas galerías existentes
try {
    $db = Database::getInstance();
    $sql = "SELECT id, token, nombre_evento, qr_path FROM galerias ORDER BY id DESC LIMIT 3";
    $stmt = $db->query($sql);
    $galleries = $stmt->fetchAll();
    
    echo "<h3>Últimas 3 galerías en base de datos:</h3>\n";
    
    foreach ($galleries as $gallery) {
        $current_url = rtrim(BASE_URL, '/') . "/galeria.php?token=" . $gallery['token'];
        
        echo "<div style='border: 1px solid #ccc; margin: 10px; padding: 10px;'>\n";
        echo "<h4>Galería ID: {$gallery['id']} - {$gallery['nombre_evento']}</h4>\n";
        echo "<p><strong>Token:</strong> {$gallery['token']}</p>\n";
        echo "<p><strong>URL actual que debería generar el QR:</strong><br>";
        echo "<a href='{$current_url}' target='_blank'>{$current_url}</a></p>\n";
        echo "<p><strong>Archivo QR:</strong> {$gallery['qr_path']}</p>\n";
        
        if (!empty($gallery['qr_path']) && file_exists($gallery['qr_path'])) {
            echo "<p><strong>QR existente:</strong><br>";
            echo "<img src='{$gallery['qr_path']}' alt='QR Code' style='max-width: 150px;'></p>\n";
        }
        
        echo "</div>\n";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error conectando a base de datos: " . $e->getMessage() . "</p>\n";
}

// Instrucciones
echo "<h3>Instrucciones para probar:</h3>\n";
echo "<ol>\n";
echo "<li>Escanea uno de los códigos QR mostrados arriba</li>\n";
echo "<li>Verifica que te lleve a la URL correcta</li>\n";
echo "<li>Si la URL no es correcta, ejecuta <code>regenerate_qr.php?password=charlyadmin 2415691611+Charly</code></li>\n";
echo "</ol>\n";
?>