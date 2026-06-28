<?php
/**
 * =====================================================
 * FILE: config/supabase.php
 * FUNGSI: Upload file ke Supabase Storage
 * VERSION: FINAL - Direct Upload
 * =====================================================
 */

class SupabaseConfig {
    // 🔥 GANTI DENGAN DATA SUPABASE ANDA
    private static $projectUrl = 'https://vfuxygadxbmvrwcptkqo.supabase.co';
    private static $apiKey = 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6InZmdXh5Z2FkeGJtdnJ3Y3B0a3FvIiwicm9sZSI6ImFub24iLCJpYXQiOjE3ODI2NDAwOTQsImV4cCI6MjA5ODIxNjA5NH0.3nOSmCU3hQWCWF9ixwis0di4BwymRtDm-5QCYY_QP8I';
    private static $bucket = 'documents';
    private static $lastError = null;

    public static function getProjectUrl() { return self::$projectUrl; }
    public static function getApiKey() { return self::$apiKey; }
    public static function getBucket() { return self::$bucket; }
    public static function getLastError() { return self::$lastError; }

    public static function uploadFile($localPath, $destinationPath) {
        self::$lastError = null;

        if (!file_exists($localPath)) {
            self::$lastError = 'File tidak ditemukan';
            return null;
        }

        if (!function_exists('curl_init')) {
            self::$lastError = 'CURL tidak aktif';
            return null;
        }

        $fileData = file_get_contents($localPath);
        if ($fileData === false) {
            self::$lastError = 'Gagal membaca file';
            return null;
        }

        $mimeType = mime_content_type($localPath);
        $fileSize = filesize($localPath);

        if ($fileSize > 5 * 1024 * 1024) {
            self::$lastError = 'File terlalu besar (max 5MB)';
            return null;
        }

        // 🔥 ENDPOINT UPLOAD KE SUPABASE
        $url = rtrim(self::$projectUrl, '/') . '/storage/v1/object/' . self::$bucket . '/' . $destinationPath;

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $fileData);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: ' . $mimeType,
            'Authorization: Bearer ' . self::$apiKey,
            'Content-Length: ' . $fileSize
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode === 200 || $httpCode === 201) {
            // 🔥 KEMBALIKAN PUBLIC URL
            return rtrim(self::$projectUrl, '/') . '/storage/v1/object/public/' . self::$bucket . '/' . $destinationPath;
        }

        self::$lastError = 'HTTP ' . $httpCode . ': ' . $response;
        return null;
    }
}
?>