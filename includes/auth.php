<?php
/**
 * =====================================================
 * FILE: includes/auth.php
 * FUNGSI: Autentikasi dengan Firebase + Cookie Session
 * VERSION: 2.0 - Fix Vercel Session
 * =====================================================
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/firebase.php';
require_once __DIR__ . '/functions.php';

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
     * 🔥 LOGIN - Simpan session + cookie
     */
    public function login($email, $password) {
        try {
            $user = $this->auth->signInWithEmailAndPassword($email, $password);
            
            // Ambil UID
            if (isset($user->uid)) {
                $uid = $user->uid;
            } elseif (isset($user->firebaseUserId)) {
                $uid = $user->firebaseUserId;
            } elseif (property_exists($user, 'localId')) {
                $uid = $user->localId;
            } else {
                throw new Exception('UID tidak ditemukan');
            }
            
            // Simpan ID Token
            if (isset($user->idToken)) {
                $_SESSION['idToken'] = $user->idToken;
            }
            
            $userData = $this->database
                ->getReference('users/' . $uid)
                ->getValue();
            
            if (!$userData || !is_array($userData)) {
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
            
            // 🔥 SESSION
            $_SESSION['uid'] = $uid;
            $_SESSION['user'] = $userData;
            $_SESSION['user_email'] = $email;
            $_SESSION['login_time'] = time();
            
            // 🔥 COOKIE (agar session tetap hidup di Vercel)
            setcookie('uid', $uid, time() + 86400 * 7, '/', '', false, true);
            setcookie('user_email', $email, time() + 86400 * 7, '/', '', false, true);
            setcookie('user_data', json_encode($userData), time() + 86400 * 7, '/', '', false, true);
            
            return $userData;
            
        } catch (Exception $e) {
            error_log('Login Error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * 🔥 CEK LOGIN - Session + Cookie fallback
     */
    public function isLoggedIn() {
        // Cek session dulu
        if (isset($_SESSION['uid']) && !empty($_SESSION['uid'])) {
            return true;
        }
        
        // 🔥 FALLBACK: Cek cookie
        if (isset($_COOKIE['uid']) && !empty($_COOKIE['uid'])) {
            // Restore session dari cookie
            $_SESSION['uid'] = $_COOKIE['uid'];
            
            if (isset($_COOKIE['user_data'])) {
                $_SESSION['user'] = json_decode($_COOKIE['user_data'], true);
            }
            if (isset($_COOKIE['user_email'])) {
                $_SESSION['user_email'] = $_COOKIE['user_email'];
            }
            
            // 🔥 Refresh data user dari database
            try {
                $userData = $this->database
                    ->getReference('users/' . $_SESSION['uid'])
                    ->getValue();
                if ($userData && is_array($userData)) {
                    $_SESSION['user'] = $userData;
                    setcookie('user_data', json_encode($userData), time() + 86400 * 7, '/', '', false, true);
                }
            } catch (Exception $e) {
                // Jika gagal, biarkan session dari cookie
            }
            
            return true;
        }
        
        return false;
    }

    /**
     * GET CURRENT USER
     */
    public function getCurrentUser() {
        if ($this->isLoggedIn()) {
            return $_SESSION['user'] ?? null;
        }
        return null;
    }

    /**
     * GET CURRENT UID
     */
    public function getCurrentUid() {
        if ($this->isLoggedIn()) {
            return $_SESSION['uid'] ?? null;
        }
        return null;
    }

    /**
     * CEK ADMIN
     */
    public function isAdmin() {
        $user = $this->getCurrentUser();
        return $user && isset($user['role']) && $user['role'] === 'admin';
    }

    /**
     * LOGOUT - Hapus semua session dan cookie
     */
    public function logout() {
        // Hapus session
        session_destroy();
        
        // Hapus cookie
        setcookie('uid', '', time() - 3600, '/');
        setcookie('user_email', '', time() - 3600, '/');
        setcookie('user_data', '', time() - 3600, '/');
        
        header('Location: ../auth/login.php');
        exit;
    }

    /**
     * 🔥 REGISTER
     */
    public function register($email, $password, $name, $nip, $jabatan, $departemen) {
        try {
            if (strlen($password) < 6) {
                return 'Kata sandi minimal 6 karakter';
            }
            
            $user = $this->auth->createUserWithEmailAndPassword($email, $password);
            
            if (isset($user->uid)) {
                $uid = $user->uid;
            } elseif (isset($user->firebaseUserId)) {
                $uid = $user->firebaseUserId;
            } elseif (property_exists($user, 'localId')) {
                $uid = $user->localId;
            } else {
                $uid = 'user_' . rand(1000, 9999);
            }
            
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
            
            $this->database
                ->getReference('users/' . $uid)
                ->set($userData);
            
            return true;
            
        } catch (Exception $e) {
            return 'Registrasi gagal: ' . $e->getMessage();
        }
    }
}

$auth = new AuthManager();
?>
