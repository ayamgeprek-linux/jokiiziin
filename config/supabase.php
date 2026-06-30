<?php
/**
 * =====================================================
 * FILE: config/supabase.php
 * FUNGSI: Upload file ke Supabase Storage
 * VERSION: FINAL - No curl_close
 * =====================================================
 */

// 🔥 GANTI DENGAN DATA DARI SUPABASE CONSOLE
define('SUPABASE_URL', 'https://vfuxygadxbmvrwcptkqo.supabase.co');
define('SUPABASE_ANON_KEY', 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6InZmdXh5Z2FkeGJtdnJ3Y3B0a3FvIiwicm9sZSI6ImFub24iLCJpYXQiOjE3ODI2NDAwOTQsImV4cCI6MjA5ODIxNjA5NH0.3nOSmCU3hQWCWF9ixwis0di4BwymRtDm-5QCYY_QP8I');
define('SUPABASE_BUCKET', 'documents');

class SupabaseAPI {
    private static $url;
    private static $apiKey;
    private static $bucket;
    
    public static function init() {
        self::$url = rtrim(SUPABASE_URL, '/');
        self::$apiKey = SUPABASE_ANON_KEY;
        self::$bucket = SUPABASE_BUCKET;
    }
    
    /**
     * Upload file ke Supabase Storage
     */
    public static function uploadFile($localPath, $destinationPath) {
        self::init();
        
        if (!file_exists($localPath)) {
            return null;
        }
        
        $fileData = file_get_contents($localPath);
        if ($fileData === false) {
            return null;
        }
        
        $mimeType = mime_content_type($localPath);
        $fileSize = filesize($localPath);
        
        if ($fileSize > 5 * 1024 * 1024) {
            return null;
        }
        
        $url = self::$url . '/storage/v1/object/' . self::$bucket . '/' . $destinationPath;
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $fileData);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: ' . $mimeType,
            'Authorization: Bearer ' . self::$apiKey,
            'Content-Length: ' . $fileSize
        ]);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        // 🔥 HAPUS curl_close($ch) - deprecated di PHP 8.5
        
        if ($httpCode === 200 || $httpCode === 201) {
            return self::$url . '/storage/v1/object/public/' . self::$bucket . '/' . $destinationPath;
        }
        
        return null;
    }
}
?>
