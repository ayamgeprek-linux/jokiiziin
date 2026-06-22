<?php
/**
 * =====================================================
 * FILE: includes/auth.php
 * FUNGSI: Manajemen autentikasi dengan REST API
 * VERSION: 4.0 - REST API Compatible
 * =====================================================
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/firebase.php';

class AuthManager {
    private $auth;
    private $database;

    public function __construct() {
        try {
            $this->auth = FirebaseConfig::getAuth();
            $this->database = FirebaseConfig::getDatabase();
        } catch (Exception $e) {
            die('Error Firebase: ' . $e->getMessage());
        }
    }

    /**
     * Login user menggunakan email dan password
     */
    public function login($email, $password) {
        try {
            // 🔥 REST API Auth
            $user = $this->auth->signInWithEmailAndPassword($email, $password);
            
            // 🔥 Ambil UID dari REST API response
            // REST API mengembalikan object dengan property 'firebaseUserId' atau 'uid'
            if (isset($user->firebaseUserId)) {
                $uid = $user->firebaseUserId;
            } elseif (isset($user->uid)) {
                $uid = $user->uid;
            } elseif (property_exists($user, 'localId')) {
                $uid = $user->localId;
            } else {
                throw new Exception('UID tidak ditemukan');
            }
            
            // Ambil data user dari Realtime Database
            $userData = $this->database
                ->getReference('users/' . $uid)
                ->getValue();
            
            if (!$userData) {
                // Jika user belum ada di database, buat baru
                $userData = [
                    'name' => $user->displayName ?? explode('@', $email)[0],
                    'email' => $email,
                    'role' => 'user',
                    'sisa_cuti' => 12,
                    'created_at' => date('Y-m-d H:i:s')
                ];
                $this->database
                    ->getReference('users/' . $uid)
                    ->set($userData);
            }
            
            // Simpan session
            $_SESSION['uid'] = $uid;
            $_SESSION['user'] = $userData;
            $_SESSION['user_email'] = $email;
            
            return $userData;
            
        } catch (Exception $e) {
            error_log('Login Error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Register user baru
     */
    public function register($email, $password, $name, $nip, $jabatan, $departemen) {
        try {
            if (strlen($password) < 6) {
                return 'Kata sandi minimal 6 karakter';
            }
            
            // 🔥 REST API Register
            $user = $this->auth->createUserWithEmailAndPassword($email, $password);
            
            // 🔥 Ambil UID
            if (isset($user->firebaseUserId)) {
                $uid = $user->firebaseUserId;
            } elseif (isset($user->uid)) {
                $uid = $user->uid;
            } elseif (property_exists($user, 'localId')) {
                $uid = $user->localId;
            } else {
                $uid = 'user_' . rand(1000, 9999);
            }
            
            // Data user
            $userData = [
                'name' => $name,
                'email' => $email,
                'nip' => $nip,
                'jabatan' => $jabatan,
                'departemen' => $departemen,
                'role' => 'user',
                'status' => 'aktif',
                'sisa_cuti' => 12,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ];
            
            // 🔥 Simpan ke Realtime Database via REST API
            $this->database
                ->getReference('users/' . $uid)
                ->set($userData);
            
            return true;
            
        } catch (Exception $e) {
            return 'Registrasi gagal: ' . $e->getMessage();
        }
    }

    /**
     * Cek apakah user sudah login
     */
    public function isLoggedIn() {
        return isset($_SESSION['uid']) && !empty($_SESSION['uid']);
    }

    /**
     * Mendapatkan data user yang sedang login
     */
    public function getCurrentUser() {
        if (!$this->isLoggedIn()) {
            return null;
        }
        return $_SESSION['user'] ?? null;
    }

    /**
     * Mendapatkan UID user yang sedang login
     */
    public function getCurrentUid() {
        return $_SESSION['uid'] ?? null;
    }

    /**
     * Cek apakah user adalah admin
     */
    public function isAdmin() {
        $user = $this->getCurrentUser();
        return $user && isset($user['role']) && $user['role'] === 'admin';
    }

    /**
     * Logout user
     */
    public function logout() {
        session_destroy();
        header('Location: login.php');
        exit;
    }
}

// Inisialisasi AuthManager
$auth = new AuthManager();
?>