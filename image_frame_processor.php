<?php
/**
 * Procesador de Marcos Temáticos para Imágenes
 * Añade diseños personalizados según el tipo de evento
 */

class ImageFrameProcessor {
    
    private $frame_templates = [
        'boda' => [
            'border_color' => '#f8f9fa',
            'border_width' => 30,
            'corner_style' => 'ornate',
            'watermark' => 'elegant_hearts',
            'text_color' => '#8b7355',
            'font_family' => 'serif'
        ],
        'cumpleanos' => [
            'border_color' => '#ff6b9d',
            'border_width' => 25,
            'corner_style' => 'fun',
            'watermark' => 'balloons',
            'text_color' => '#ffffff',
            'font_family' => 'sans-serif'
        ],
        'quinceanos' => [
            'border_color' => '#e91e63',
            'border_width' => 35,
            'corner_style' => 'princess',
            'watermark' => 'crown',
            'text_color' => '#ffffff',
            'font_family' => 'script'
        ],
        'bautizo' => [
            'border_color' => '#e3f2fd',
            'border_width' => 20,
            'corner_style' => 'gentle',
            'watermark' => 'cross',
            'text_color' => '#1976d2',
            'font_family' => 'serif'
        ],
        'graduacion' => [
            'border_color' => '#1a237e',
            'border_width' => 25,
            'corner_style' => 'academic',
            'watermark' => 'graduation_cap',
            'text_color' => '#ffffff',
            'font_family' => 'sans-serif'
        ],
        'baby_shower' => [
            'border_color' => '#fff3e0',
            'border_width' => 30,
            'corner_style' => 'soft',
            'watermark' => 'baby_items',
            'text_color' => '#ff8a65',
            'font_family' => 'script'
        ],
        'aniversario' => [
            'border_color' => '#3e2723',
            'border_width' => 25,
            'corner_style' => 'classic',
            'watermark' => 'rings',
            'text_color' => '#d4af37',
            'font_family' => 'serif'
        ],
        'corporativo' => [
            'border_color' => '#263238',
            'border_width' => 20,
            'corner_style' => 'modern',
            'watermark' => 'none',
            'text_color' => '#ffffff',
            'font_family' => 'sans-serif'
        ]
    ];

    public function processImage($image_path, $event_type, $event_name, $date, $contributor_name) {
        try {
            // Verificar que la imagen existe
            if (!file_exists($image_path)) {
                throw new Exception("Imagen no encontrada: $image_path");
            }

            // Obtener configuración del marco
            $frame_config = $this->frame_templates[$event_type] ?? $this->frame_templates['corporativo'];
            
            // Crear imagen desde archivo
            $image_info = getimagesize($image_path);
            $image = $this->createImageFromFile($image_path, $image_info[2]);
            
            if (!$image) {
                throw new Exception("No se pudo crear la imagen desde: $image_path");
            }

            // Obtener dimensiones originales
            $original_width = imagesx($image);
            $original_height = imagesy($image);
            
            // Calcular nuevas dimensiones con marco
            $border_width = $frame_config['border_width'];
            $new_width = $original_width + ($border_width * 2);
            $new_height = $original_height + ($border_width * 2) + 60; // +60 para texto inferior
            
            // Crear lienzo con marco
            $framed_image = imagecreatetruecolor($new_width, $new_height);
            
            // Color de fondo del marco
            $border_color = $this->hexToRgb($frame_config['border_color']);
            $bg_color = imagecolorallocate($framed_image, $border_color[0], $border_color[1], $border_color[2]);
            imagefill($framed_image, 0, 0, $bg_color);
            
            // Añadir patrón decorativo según el estilo
            $this->addDecorativePattern($framed_image, $frame_config, $new_width, $new_height);
            
            // Copiar imagen original al centro
            imagecopy($framed_image, $image, $border_width, $border_width, 0, 0, $original_width, $original_height);
            
            // Añadir texto del evento
            $this->addEventText($framed_image, $frame_config, $event_name, $date, $contributor_name, $new_width, $new_height);
            
            // Añadir marca de agua temática
            $this->addThematicWatermark($framed_image, $frame_config, $new_width, $new_height);
            
            // Limpiar imagen original
            imagedestroy($image);
            
            return $framed_image;
            
        } catch (Exception $e) {
            error_log("Error procesando imagen: " . $e->getMessage());
            return false;
        }
    }
    
    private function createImageFromFile($path, $type) {
        switch ($type) {
            case IMAGETYPE_JPEG:
                return imagecreatefromjpeg($path);
            case IMAGETYPE_PNG:
                return imagecreatefrompng($path);
            case IMAGETYPE_GIF:
                return imagecreatefromgif($path);
            case IMAGETYPE_WEBP:
                return imagecreatefromwebp($path);
            default:
                return false;
        }
    }
    
    private function hexToRgb($hex) {
        $hex = ltrim($hex, '#');
        return [
            hexdec(substr($hex, 0, 2)),
            hexdec(substr($hex, 2, 2)),
            hexdec(substr($hex, 4, 2))
        ];
    }
    
    private function addDecorativePattern($image, $config, $width, $height) {
        $border_width = $config['border_width'];
        
        switch ($config['corner_style']) {
            case 'ornate': // Para bodas
                $this->drawOrnateCorners($image, $width, $height, $border_width);
                break;
            case 'fun': // Para cumpleaños
                $this->drawFunPattern($image, $width, $height, $border_width);
                break;
            case 'princess': // Para quinceaños
                $this->drawPrincessPattern($image, $width, $height, $border_width);
                break;
            case 'gentle': // Para bautizos
                $this->drawGentlePattern($image, $width, $height, $border_width);
                break;
            case 'academic': // Para graduaciones
                $this->drawAcademicPattern($image, $width, $height, $border_width);
                break;
        }
    }
    
    private function drawOrnateCorners($image, $width, $height, $border) {
        // Color dorado para ornamentos
        $gold = imagecolorallocate($image, 212, 175, 55);
        
        // Esquinas ornamentadas para bodas
        $corner_size = $border / 2;
        
        // Esquina superior izquierda
        for ($i = 0; $i < $corner_size; $i++) {
            imagearc($image, $border, $border, $i * 2, $i * 2, 0, 90, $gold);
        }
        
        // Esquina superior derecha
        for ($i = 0; $i < $corner_size; $i++) {
            imagearc($image, $width - $border, $border, $i * 2, $i * 2, 90, 180, $gold);
        }
        
        // Esquina inferior izquierda
        for ($i = 0; $i < $corner_size; $i++) {
            imagearc($image, $border, $height - $border - 60, $i * 2, $i * 2, 270, 360, $gold);
        }
        
        // Esquina inferior derecha
        for ($i = 0; $i < $corner_size; $i++) {
            imagearc($image, $width - $border, $height - $border - 60, $i * 2, $i * 2, 180, 270, $gold);
        }
    }
    
    private function drawFunPattern($image, $width, $height, $border) {
        // Colores divertidos
        $colors = [
            imagecolorallocate($image, 255, 193, 7),   // Amarillo
            imagecolorallocate($image, 76, 175, 80),   // Verde
            imagecolorallocate($image, 33, 150, 243),  // Azul
            imagecolorallocate($image, 233, 30, 99)    // Rosa
        ];
        
        // Puntos coloridos en el marco
        for ($i = 0; $i < 50; $i++) {
            $x = rand(5, $width - 5);
            $y = rand(5, $border - 5); // Solo en el borde superior
            $color = $colors[array_rand($colors)];
            imagefilledellipse($image, $x, $y, 8, 8, $color);
        }
        
        // También en el borde inferior
        for ($i = 0; $i < 50; $i++) {
            $x = rand(5, $width - 5);
            $y = rand($height - $border, $height - 5);
            $color = $colors[array_rand($colors)];
            imagefilledellipse($image, $x, $y, 8, 8, $color);
        }
    }
    
    private function drawPrincessPattern($image, $width, $height, $border) {
        // Color rosa/dorado para quinceaños
        $pink = imagecolorallocate($image, 233, 30, 99);
        $gold = imagecolorallocate($image, 255, 215, 0);
        
        // Estrellas en las esquinas
        for ($i = 0; $i < 20; $i++) {
            $x = rand(5, $border - 5);
            $y = rand(5, $border - 5);
            $this->drawStar($image, $x, $y, 5, $pink);
        }
        
        // Border decorativo
        for ($x = 0; $x < $width; $x += 20) {
            imageline($image, $x, 5, $x + 10, 15, $gold);
            imageline($image, $x, $height - 65, $x + 10, $height - 55, $gold);
        }
    }
    
    private function drawGentlePattern($image, $width, $height, $border) {
        // Patrón suave para bautizos
        $light_blue = imagecolorallocate($image, 144, 202, 249);
        
        // Líneas suaves
        for ($i = 0; $i < $border; $i += 5) {
            imageline($image, $i, 0, $i, $height, $light_blue);
            imageline($image, $width - $i, 0, $width - $i, $height, $light_blue);
        }
    }
    
    private function drawAcademicPattern($image, $width, $height, $border) {
        // Patrón académico para graduaciones
        $gold = imagecolorallocate($image, 255, 215, 0);
        
        // Líneas diagonales (como togas)
        for ($i = 0; $i < $width; $i += 30) {
            imageline($image, $i, 0, $i + 15, $border, $gold);
            imageline($image, $i, $height - 60, $i + 15, $height, $gold);
        }
    }
    
    private function drawStar($image, $cx, $cy, $size, $color) {
        // Dibujar una estrella simple
        $points = [];
        for ($i = 0; $i < 10; $i++) {
            $angle = $i * M_PI / 5;
            $radius = ($i % 2) ? $size / 2 : $size;
            $points[] = $cx + cos($angle) * $radius;
            $points[] = $cy + sin($angle) * $radius;
        }
        imagefilledpolygon($image, $points, 10, $color);
    }
    
    private function addEventText($image, $config, $event_name, $date, $contributor, $width, $height) {
        // Color del texto
        $text_color_rgb = $this->hexToRgb($config['text_color']);
        $text_color = imagecolorallocate($image, $text_color_rgb[0], $text_color_rgb[1], $text_color_rgb[2]);
        
        // Fuente (usar fuente del sistema)
        $font_size = 12;
        
        // Texto del evento (parte superior)
        $text_y = $height - 45;
        imagettftext($image, $font_size, 0, 10, $text_y, $text_color, $this->getSystemFont(), 
                    strtoupper($event_name));
        
        // Fecha y contribuyente (parte inferior)
        $bottom_text = date('d/m/Y', strtotime($date)) . " • " . $contributor;
        imagettftext($image, 10, 0, 10, $height - 15, $text_color, $this->getSystemFont(), $bottom_text);
    }
    
    private function addThematicWatermark($image, $config, $width, $height) {
        // Añadir marca de agua temática según el tipo de evento
        $watermark_color = imagecolorallocatealpha($image, 255, 255, 255, 100); // Semi-transparente
        
        switch ($config['watermark']) {
            case 'elegant_hearts':
                $this->drawHearts($image, $width, $height, $watermark_color);
                break;
            case 'balloons':
                $this->drawBalloons($image, $width, $height, $watermark_color);
                break;
            case 'crown':
                $this->drawCrown($image, $width - 50, 30, $watermark_color);
                break;
            case 'cross':
                $this->drawCross($image, $width - 40, 30, $watermark_color);
                break;
            case 'graduation_cap':
                $this->drawGraduationCap($image, $width - 60, 30, $watermark_color);
                break;
        }
    }
    
    private function drawHearts($image, $width, $height, $color) {
        // Corazones pequeños dispersos
        for ($i = 0; $i < 5; $i++) {
            $x = rand(50, $width - 100);
            $y = rand(50, $height - 150);
            $this->drawHeart($image, $x, $y, 15, $color);
        }
    }
    
    private function drawHeart($image, $cx, $cy, $size, $color) {
        // Dibujar un corazón simple
        imagefilledellipse($image, $cx - $size/3, $cy - $size/3, $size, $size, $color);
        imagefilledellipse($image, $cx + $size/3, $cy - $size/3, $size, $size, $color);
        
        $points = [
            $cx - $size/2, $cy,
            $cx, $cy + $size,
            $cx + $size/2, $cy
        ];
        imagefilledpolygon($image, $points, 3, $color);
    }
    
    private function drawBalloons($image, $width, $height, $color) {
        // Globos para cumpleaños
        for ($i = 0; $i < 3; $i++) {
            $x = rand(100, $width - 150);
            $y = rand(80, $height - 200);
            imagefilledellipse($image, $x, $y, 20, 30, $color);
            imageline($image, $x, $y + 15, $x, $y + 40, $color);
        }
    }
    
    private function drawCrown($image, $x, $y, $color) {
        // Corona para quinceaños
        $points = [
            $x, $y + 20,
            $x + 10, $y,
            $x + 20, $y + 15,
            $x + 30, $y,
            $x + 40, $y + 20,
            $x + 35, $y + 25,
            $x + 5, $y + 25
        ];
        imagefilledpolygon($image, $points, 7, $color);
    }
    
    private function drawCross($image, $x, $y, $color) {
        // Cruz para bautizos
        imagefilledrectangle($image, $x + 15, $y, $x + 25, $y + 40, $color);
        imagefilledrectangle($image, $x, $y + 15, $x + 40, $y + 25, $color);
    }
    
    private function drawGraduationCap($image, $x, $y, $color) {
        // Birrete de graduación
        imagefilledrectangle($image, $x, $y + 15, $x + 40, $y + 25, $color);
        imagefilledpolygon($image, [$x - 5, $y + 15, $x + 45, $y + 15, $x + 40, $y, $x, $y], 4, $color);
        imageline($image, $x + 40, $y, $x + 50, $y - 10, $color);
    }
    
    private function getSystemFont() {
        // Intentar usar fuentes del sistema
        $fonts = [
            '/System/Library/Fonts/Arial.ttf',
            '/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf',
            './fonts/arial.ttf',
            5 // Fuente incorporada como fallback
        ];
        
        foreach ($fonts as $font) {
            if (is_string($font) && file_exists($font)) {
                return $font;
            }
        }
        
        return 5; // Fuente incorporada
    }
    
    public function saveFramedImage($framed_image, $output_path, $quality = 90) {
        $extension = strtolower(pathinfo($output_path, PATHINFO_EXTENSION));
        
        switch ($extension) {
            case 'jpg':
            case 'jpeg':
                return imagejpeg($framed_image, $output_path, $quality);
            case 'png':
                return imagepng($framed_image, $output_path, (int)(9 - ($quality / 10)));
            case 'webp':
                return imagewebp($framed_image, $output_path, $quality);
            default:
                return imagejpeg($framed_image, $output_path, $quality);
        }
    }
}

/**
 * Función de uso principal
 */
function processEventPhotos($galeria_id, $event_type, $apply_frames = true) {
    if (!$apply_frames) {
        return false; // No procesar si no se requieren marcos
    }
    
    try {
        $db = Database::getInstance();
        
        // Obtener datos de la galería
        $sql = "SELECT * FROM galerias WHERE id = ?";
        $stmt = $db->query($sql, [$galeria_id]);
        $gallery = $stmt->fetch();
        
        if (!$gallery) {
            throw new Exception("Galería no encontrada");
        }
        
        // Obtener fotos
        $sql = "SELECT * FROM fotos WHERE galeria_id = ?";
        $stmt = $db->query($sql, [$galeria_id]);
        $photos = $stmt->fetchAll();
        
        $processor = new ImageFrameProcessor();
        $processed_photos = [];
        
        foreach ($photos as $photo) {
            if (file_exists($photo['ruta_foto'])) {
                // Procesar imagen con marco temático
                $framed_image = $processor->processImage(
                    $photo['ruta_foto'],
                    $event_type,
                    $gallery['nombre_evento'],
                    $gallery['fecha_evento'],
                    $photo['nombre_invitado'] ?: 'Invitado'
                );
                
                if ($framed_image) {
                    // Crear directorio para imágenes procesadas
                    $processed_dir = './uploads/processed/';
                    if (!is_dir($processed_dir)) {
                        mkdir($processed_dir, 0755, true);
                    }
                    
                    // Nombre del archivo procesado
                    $filename = pathinfo($photo['ruta_foto'], PATHINFO_FILENAME);
                    $processed_path = $processed_dir . $filename . '_framed.jpg';
                    
                    // Guardar imagen procesada
                    if ($processor->saveFramedImage($framed_image, $processed_path, 95)) {
                        $processed_photos[] = $processed_path;
                    }
                    
                    imagedestroy($framed_image);
                }
            }
        }
        
        return $processed_photos;
        
    } catch (Exception $e) {
        error_log("Error procesando fotos: " . $e->getMessage());
        return false;
    }
}
?>