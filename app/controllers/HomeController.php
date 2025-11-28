<?php
class HomeController {
    public function index() {
        // ⭐⭐ QUITAR la redirección automática al chat
        // Dejar que el usuario decida a dónde ir
        
        // Si el usuario ya está logueado, redirigir al chat
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (isset($_SESSION['usuario_id'])) {
            header('Location: /robinutri/index.php/chat');
            exit();
        } else {
            // ⭐⭐ Si NO está logueado, redirigir al LOGIN
            header('Location: /robinutri/index.php/login');
            exit();
        }
    }
}
?>