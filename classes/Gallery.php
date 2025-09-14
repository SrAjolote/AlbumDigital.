<?php
/**
 * Clase para manejo de galerías
 * Sistema de Galerías con QR
 */

require_once 'config/database.php';
require_once 'includes/qr_generator.php';

class Gallery {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Crear nueva galería
     */
    public function createGallery($nombre_evento, $cliente_nombre, $fecha_evento) {
        try {
            // Generar token único
            $token = generate_token();
            
            // Insertar galería en la base de datos
            $sql = "INSERT INTO galerias (nombre_evento, cliente_nombre, fecha_evento, token) VALUES (?, ?, ?, ?)";
            $stmt = $this->db->query($sql, [$nombre_evento, $cliente_nombre, $fecha_evento, $token]);
            
            $galeria_id = $this->db->lastInsertId();
            
            // Generar código QR
            $qr_generator = new QRGenerator();
            $gallery_url = BASE_URL . "/galeria.php?token=" . $token;
            $qr_filename = "qr_galeria_" . $galeria_id . ".png";
            
            $qr_path = $qr_generator->generateQR($gallery_url, $qr_filename);
            
            if ($qr_path) {
                // Actualizar la ruta del QR en la base de datos
                $update_sql = "UPDATE galerias SET qr_path = ? WHERE id = ?";
                $this->db->query($update_sql, [$qr_path, $galeria_id]);
            }
            
            return [
                'success' => true,
                'galeria_id' => $galeria_id,
                'token' => $token,
                'qr_path' => $qr_path
            ];
            
        } catch (Exception $e) {
            error_log("Error creando galería: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Obtener galería por token
     */
    public function getGalleryByToken($token) {
        try {
            $sql = "SELECT * FROM galerias WHERE token = ? AND activa = 1";
            $stmt = $this->db->query($sql, [$token]);
            return $stmt->fetch();
        } catch (Exception $e) {
            error_log("Error obteniendo galería: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Obtener todas las galerías
     */
    public function getAllGalleries() {
        try {
            $sql = "SELECT g.*, COUNT(f.id) as total_fotos 
                    FROM galerias g 
                    LEFT JOIN fotos f ON g.id = f.galeria_id 
                    GROUP BY g.id 
                    ORDER BY g.fecha_creacion DESC";
            $stmt = $this->db->query($sql);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            error_log("Error obteniendo galerías: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Obtener fotos de una galería
     */
    public function getGalleryPhotos($galeria_id) {
        try {
            $sql = "SELECT * FROM fotos WHERE galeria_id = ? ORDER BY fecha_subida DESC";
            $stmt = $this->db->query($sql, [$galeria_id]);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            error_log("Error obteniendo fotos: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Subir foto a galería
     */
    public function uploadPhoto($galeria_id, $file, $nombre_invitado = null) {
        try {
            // Validar archivo
            $validation = $this->validateFile($file);
            if (!$validation['valid']) {
                return ['success' => false, 'error' => $validation['error']];
            }
            
            // Generar nombre único para el archivo
            $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $filename = 'foto_' . $galeria_id . '_' . time() . '_' . mt_rand(1000, 9999) . '.' . $extension;
            $upload_path = UPLOAD_DIR . $filename;
            
            // Mover archivo
            if (move_uploaded_file($file['tmp_name'], $upload_path)) {
                // Registrar en base de datos
                $sql = "INSERT INTO fotos (galeria_id, nombre_invitado, ruta_foto, nombre_archivo, ip_usuario) VALUES (?, ?, ?, ?, ?)";
                $stmt = $this->db->query($sql, [
                    $galeria_id,
                    $nombre_invitado,
                    $upload_path,
                    $file['name'],
                    get_user_ip()
                ]);
                
                return [
                    'success' => true,
                    'foto_id' => $this->db->lastInsertId(),
                    'ruta' => $upload_path
                ];
            } else {
                return ['success' => false, 'error' => 'Error al subir el archivo'];
            }
            
        } catch (Exception $e) {
            error_log("Error subiendo foto: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Validar archivo subido
     */
    private function validateFile($file) {
        // Verificar errores de subida
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return ['valid' => false, 'error' => 'Error en la subida del archivo'];
        }
        
        // Verificar tamaño (50MB para sistema ilimitado)
        if ($file['size'] > MAX_FILE_SIZE) {
            return ['valid' => false, 'error' => 'El archivo es demasiado grande. Máximo ' . (MAX_FILE_SIZE / 1024 / 1024) . 'MB'];
        }
        
        // Verificar extensión
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($extension, ALLOWED_EXTENSIONS)) {
            return ['valid' => false, 'error' => 'Formato de archivo no permitido. Use: ' . implode(', ', ALLOWED_EXTENSIONS)];
        }
        
        // Verificar que sea una imagen real
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime_type = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        $allowed_mimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        if (!in_array($mime_type, $allowed_mimes)) {
            return ['valid' => false, 'error' => 'El archivo no es una imagen válida'];
        }
        
        return ['valid' => true];
    }
    
    /**
     * Eliminar galería
     */
    public function deleteGallery($id) {
        try {
            // Obtener fotos para eliminar archivos
            $photos = $this->getGalleryPhotos($id);
            
            // Eliminar archivos de fotos
            foreach ($photos as $photo) {
                if (file_exists($photo['ruta_foto'])) {
                    unlink($photo['ruta_foto']);
                }
            }
            
            // Obtener QR para eliminar
            $sql = "SELECT qr_path FROM galerias WHERE id = ?";
            $stmt = $this->db->query($sql, [$id]);
            $gallery = $stmt->fetch();
            
            if ($gallery && $gallery['qr_path'] && file_exists($gallery['qr_path'])) {
                unlink($gallery['qr_path']);
            }
            
            // Eliminar galería (las fotos se eliminan por CASCADE)
            $delete_sql = "DELETE FROM galerias WHERE id = ?";
            $this->db->query($delete_sql, [$id]);
            
            return ['success' => true];
            
        } catch (Exception $e) {
            error_log("Error eliminando galería: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}
?>