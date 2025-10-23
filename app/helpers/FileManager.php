<?php
/**
 * Classe FileManager - Gestion des fichiers et uploads
 */

class FileManager {

    /**
     * Upload un fichier avec sécurité
     */
    public static function upload($file, $folder = 'documents', $clientId = null) {
        // Vérifications de base
        if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            return ['success' => false, 'error' => 'Aucun fichier n\'a été uploadé.'];
        }

        // Vérifier la taille
        if ($file['size'] > UPLOAD_MAX_SIZE) {
            $maxSizeMB = UPLOAD_MAX_SIZE / 1048576;
            return ['success' => false, 'error' => "Le fichier est trop volumineux (maximum {$maxSizeMB} MB)."];
        }

        // Vérifier l'extension
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowedTypes = explode(',', UPLOAD_ALLOWED_TYPES);

        if (!in_array($extension, $allowedTypes)) {
            return ['success' => false, 'error' => 'Type de fichier non autorisé.'];
        }

        // Créer le dossier de destination
        $basePath = UPLOAD_PATH . '/' . $folder;
        if ($clientId) {
            $basePath .= '/' . $clientId;
        }

        if (!file_exists($basePath)) {
            mkdir($basePath, 0755, true);
        }

        // Générer un nom de fichier sécurisé avec UUID
        $uuid = Security::generateUUID();
        $originalName = pathinfo($file['name'], PATHINFO_FILENAME);
        $safeOriginalName = self::sanitizeFilename($originalName);
        $fileName = $uuid . '.' . $extension;
        $filePath = $basePath . '/' . $fileName;

        // Déplacer le fichier
        if (!move_uploaded_file($file['tmp_name'], $filePath)) {
            return ['success' => false, 'error' => 'Erreur lors de l\'upload du fichier.'];
        }

        // Scan de sécurité basique (vérification MIME)
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $filePath);
        finfo_close($finfo);

        return [
            'success' => true,
            'file_name' => $fileName,
            'original_name' => $file['name'],
            'safe_original_name' => $safeOriginalName,
            'file_path' => $filePath,
            'relative_path' => str_replace(APP_ROOT, '', $filePath),
            'file_size' => $file['size'],
            'mime_type' => $mimeType,
            'extension' => $extension,
            'uuid' => $uuid
        ];
    }

    /**
     * Nettoyer un nom de fichier
     */
    public static function sanitizeFilename($filename) {
        // Remplacer les caractères spéciaux
        $filename = preg_replace('/[^a-zA-Z0-9\-\_\.]/', '_', $filename);
        // Limiter la longueur
        if (strlen($filename) > 100) {
            $filename = substr($filename, 0, 100);
        }
        return $filename;
    }

    /**
     * Supprimer un fichier
     */
    public static function delete($filePath) {
        if (file_exists($filePath)) {
            return unlink($filePath);
        }
        return false;
    }

    /**
     * Obtenir l'icône pour un type de fichier
     */
    public static function getFileIcon($extension) {
        $icons = [
            'pdf' => '📄',
            'doc' => '📝',
            'docx' => '📝',
            'xls' => '📊',
            'xlsx' => '📊',
            'jpg' => '🖼️',
            'jpeg' => '🖼️',
            'png' => '🖼️',
            'zip' => '🗜️',
            'rar' => '🗜️',
        ];

        return $icons[$extension] ?? '📎';
    }

    /**
     * Formater la taille d'un fichier
     */
    public static function formatFileSize($bytes) {
        $units = ['o', 'Ko', 'Mo', 'Go', 'To'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= (1 << (10 * $pow));

        return round($bytes, 2) . ' ' . $units[$pow];
    }

    /**
     * Vérifier si un fichier est une image
     */
    public static function isImage($extension) {
        return in_array(strtolower($extension), ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp']);
    }

    /**
     * Vérifier si un fichier est un PDF
     */
    public static function isPDF($extension) {
        return strtolower($extension) === 'pdf';
    }

    /**
     * Créer une miniature pour une image
     */
    public static function createThumbnail($sourcePath, $destPath, $maxWidth = 200, $maxHeight = 200) {
        if (!file_exists($sourcePath)) {
            return false;
        }

        $imageInfo = getimagesize($sourcePath);
        if (!$imageInfo) {
            return false;
        }

        list($width, $height, $type) = $imageInfo;

        // Calculer les nouvelles dimensions
        $ratio = min($maxWidth / $width, $maxHeight / $height);
        $newWidth = intval($width * $ratio);
        $newHeight = intval($height * $ratio);

        // Créer l'image source
        switch ($type) {
            case IMAGETYPE_JPEG:
                $source = imagecreatefromjpeg($sourcePath);
                break;
            case IMAGETYPE_PNG:
                $source = imagecreatefrompng($sourcePath);
                break;
            case IMAGETYPE_GIF:
                $source = imagecreatefromgif($sourcePath);
                break;
            default:
                return false;
        }

        // Créer la miniature
        $thumb = imagecreatetruecolor($newWidth, $newHeight);

        // Préserver la transparence pour PNG et GIF
        if ($type == IMAGETYPE_PNG || $type == IMAGETYPE_GIF) {
            imagealphablending($thumb, false);
            imagesavealpha($thumb, true);
            $transparent = imagecolorallocatealpha($thumb, 255, 255, 255, 127);
            imagefilledrectangle($thumb, 0, 0, $newWidth, $newHeight, $transparent);
        }

        imagecopyresampled($thumb, $source, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

        // Sauvegarder la miniature
        $result = false;
        switch ($type) {
            case IMAGETYPE_JPEG:
                $result = imagejpeg($thumb, $destPath, 85);
                break;
            case IMAGETYPE_PNG:
                $result = imagepng($thumb, $destPath, 8);
                break;
            case IMAGETYPE_GIF:
                $result = imagegif($thumb, $destPath);
                break;
        }

        imagedestroy($source);
        imagedestroy($thumb);

        return $result;
    }

    /**
     * Nettoyer les fichiers temporaires anciens
     */
    public static function cleanTempFiles($olderThanHours = 24) {
        $tempPath = UPLOAD_PATH . '/temp';
        if (!file_exists($tempPath)) {
            return;
        }

        $now = time();
        $files = scandir($tempPath);

        foreach ($files as $file) {
            if ($file === '.' || $file === '..') continue;

            $filePath = $tempPath . '/' . $file;
            if (is_file($filePath)) {
                $fileTime = filemtime($filePath);
                if (($now - $fileTime) > ($olderThanHours * 3600)) {
                    unlink($filePath);
                }
            }
        }
    }

    /**
     * Obtenir l'espace disque utilisé
     */
    public static function getDiskUsage() {
        $totalSize = 0;
        $uploadPath = UPLOAD_PATH;

        if (!file_exists($uploadPath)) {
            return ['total' => 0, 'formatted' => '0 o'];
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($uploadPath, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $totalSize += $file->getSize();
            }
        }

        return [
            'total' => $totalSize,
            'formatted' => self::formatFileSize($totalSize)
        ];
    }
}
