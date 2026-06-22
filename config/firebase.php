<?php
/**
 * =====================================================
 * FILE: config/firebase.php
 * FUNGSI: Koneksi ke Firebase via REST API
 * VERSION: 4.0 - REST API Only (Tanpa Composer)
 * =====================================================
 */

// ============================================
// 🔥 KONFIGURASI FIREBASE
// ============================================
// 1. API Key: Firebase Console → Project Settings → Web API Key
// 2. Database URL: Firebase Console → Realtime Database → URL
// ============================================

class FirebaseConfig {
    private static $auth = null;
    private static $database = null;
    
    /**
     * 🔥 Ganti dengan Web API Key dari Firebase Console
     */
    public static function getApiKey() {
        return 'AIzaSyAjVROuwHCfhUQDrA7Xek-CsyjsQpcFrHs'; // <-- GANTI!
    }
    
    /**
     * 🔥 Ganti dengan URL Database dari Firebase Console
     */
    public static function getDatabaseUrl() {
        return 'https://perizinan-db492-default-rtdb.asia-southeast1.firebasedatabase.app/'; // <-- GANTI!
    }

    public static function getAuth() {
        if (self::$auth === null) {
            self::$auth = new RestAuth(self::getApiKey());
        }
        return self::$auth;
    }

    public static function getDatabase() {
        if (self::$database === null) {
            self::$database = new RestDatabase(self::getDatabaseUrl(), self::getApiKey());
        }
        return self::$database;
    }
}

// ============================================
// REST AUTH
// ============================================
class RestAuth {
    private $apiKey;
    
    public function __construct($apiKey) {
        $this->apiKey = $apiKey;
    }
    
    public function signInWithEmailAndPassword($email, $password) {
        $url = "https://identitytoolkit.googleapis.com/v1/accounts:signInWithPassword?key=" . $this->apiKey;
        
        $data = [
            'email' => $email,
            'password' => $password,
            'returnSecureToken' => true
        ];
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode === 200) {
            $result = json_decode($response);
            return (object)[
                'firebaseUserId' => $result->localId,
                'uid' => $result->localId,
                'idToken' => $result->idToken,
                'email' => $result->email,
                'displayName' => $result->displayName ?? ''
            ];
        } else {
            $error = json_decode($response);
            $message = $error->error->message ?? 'Unknown error';
            throw new Exception('Login gagal: ' . $message);
        }
    }
    
    public function createUserWithEmailAndPassword($email, $password) {
        $url = "https://identitytoolkit.googleapis.com/v1/accounts:signUp?key=" . $this->apiKey;
        
        $data = [
            'email' => $email,
            'password' => $password,
            'returnSecureToken' => true
        ];
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode === 200) {
            $result = json_decode($response);
            return (object)[
                'firebaseUserId' => $result->localId,
                'uid' => $result->localId,
                'idToken' => $result->idToken,
                'email' => $result->email
            ];
        } else {
            $error = json_decode($response);
            $message = $error->error->message ?? 'Unknown error';
            throw new Exception('Registrasi gagal: ' . $message);
        }
    }
}

// ============================================
// REST DATABASE
// ============================================
class RestDatabase {
    private $baseUrl;
    private $apiKey;
    
    public function __construct($baseUrl, $apiKey) {
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->apiKey = $apiKey;
    }
    
    public function getReference($path) {
        return new RestReference($this->baseUrl, $path, $this->apiKey);
    }
}

class RestReference {
    private $baseUrl;
    private $path;
    private $apiKey;
    private $orderBy = null;
    private $equalTo = null;
    private $limitToFirst = null;
    
    public function __construct($baseUrl, $path, $apiKey) {
        $this->baseUrl = $baseUrl;
        $this->path = ltrim($path, '/');
        $this->apiKey = $apiKey;
    }
    
    private function buildUrl() {
        $url = $this->baseUrl . '/' . $this->path . '.json';
        $params = ['auth=' . $this->apiKey];
        
        if ($this->orderBy) {
            $params[] = 'orderBy="' . $this->orderBy . '"';
        }
        if ($this->equalTo !== null) {
            $params[] = 'equalTo="' . $this->equalTo . '"';
        }
        if ($this->limitToFirst !== null) {
            $params[] = 'limitToFirst=' . $this->limitToFirst;
        }
        
        return $url . '?' . implode('&', $params);
    }
    
    public function getValue() {
        $url = $this->buildUrl();
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        
        $response = curl_exec($ch);
        curl_close($ch);
        
        return json_decode($response, true);
    }
    
    public function set($value) {
        $url = $this->baseUrl . '/' . $this->path . '.json?auth=' . $this->apiKey;
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($value));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        
        $response = curl_exec($ch);
        curl_close($ch);
        
        return json_decode($response, true);
    }
    
    public function push($value) {
        $url = $this->baseUrl . '/' . $this->path . '.json?auth=' . $this->apiKey;
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($value));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        
        $response = curl_exec($ch);
        curl_close($ch);
        
        $result = json_decode($response, true);
        $key = $result['name'] ?? null;
        
        return (object)[
            'getKey' => function() use ($key) { return $key; }
        ];
    }
    
    public function update($value) {
        $url = $this->baseUrl . '/' . $this->path . '.json?auth=' . $this->apiKey;
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PATCH');
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($value));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        
        $response = curl_exec($ch);
        curl_close($ch);
        
        return json_decode($response, true);
    }
    
    public function orderByChild($field) {
        $this->orderBy = $field;
        return $this;
    }
    
    public function equalTo($value) {
        $this->equalTo = $value;
        return $this;
    }
    
    public function limitToFirst($limit) {
        $this->limitToFirst = $limit;
        return $this;
    }
    
    public function getSnapshot() {
        $data = $this->getValue();
        return (object)[
            'numChildren' => function() use ($data) {
                return is_array($data) ? count($data) : 0;
            }
        ];
    }
}

// ============================================
// TEST CONNECTION
// ============================================
if (basename($_SERVER['PHP_SELF']) === 'firebase.php') {
    echo '<h1>Firebase REST API Test</h1>';
    echo '<p>API Key: ' . substr(FirebaseConfig::getApiKey(), 0, 10) . '...</p>';
    echo '<p>Database URL: ' . FirebaseConfig::getDatabaseUrl() . '</p>';
    echo '<p style="color:green;">✅ Firebase REST API siap digunakan!</p>';

    
    echo '<hr>';
    echo '<h3>Instruksi:</h3>';
    echo '<ol>';
    echo '<li>Ganti API Key di config/firebase.php (baris 21)</li>';
    echo '<li>Ganti Database URL di config/firebase.php (baris 26)</li>';
    echo '<li>Restart Apache</li>';
    echo '<li>Buka login.php</li>';
    echo '</ol>';
}
?>