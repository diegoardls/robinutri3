<?php
require_once __DIR__ . '/../models/Database.php';
require_once __DIR__ . '/../models/UserModel.php';

class LoginController {
    private $model;

    public function __construct() {
        try {
            $database = new Database();
            $db = $database->getConnection();
            $this->model = new UserModel($db);
        } catch (Exception $e) {
            die("Error de conexión a la base de datos: " . $e->getMessage());
        }
    }
    
    public function index() {
        require_once __DIR__ . '/../views/login/inicio.php';
    }
    
    public function registro() {
        require_once __DIR__ . '/../views/login/registro.php';
    }
    
    public function auth() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (isset($_POST['accion']) && $_POST['accion'] === 'login') {
                $this->procesarLogin();
            }
            
            if (isset($_POST['accion']) && $_POST['accion'] === 'register') {
                $this->procesarRegistro();
            }
        }
    }
    
    private function procesarLogin() {
        $email = htmlspecialchars($_POST['email']);
        $password = htmlspecialchars($_POST['password']);
        
        // Validar campos
        if (empty($email) || empty($password)) {
            $this->mostrarError("Por favor, completa todos los campos", "/robinutri/index.php/login");
            return;
        }
        
        // Validar formato de email
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->mostrarError("Por favor, ingresa un correo electrónico válido", "/robinutri/index.php/login");
            return;
        }
        
        // ⭐⭐ VERIFICAR SI EL USUARIO EXISTE
        if (!$this->model->existeCorreo($email)) {
            $this->mostrarError("El correo electrónico no está registrado", "/robinutri/index.php/login");
            return;
        }
        
        // Obtener contraseña de la base de datos
        $contraseñaBD = $this->model->obtenerPasswordPorEmail($email);
        
        if ($contraseñaBD && $password === $contraseñaBD) {
            // Login exitoso - obtener datos del usuario
            $usuario = $this->model->obtenerUsuarioPorEmail($email);
            
            if ($usuario) {
                if (session_status() === PHP_SESSION_NONE) {
                    session_start();
                }
                $_SESSION['usuario_id'] = $usuario['id_cuenta'];
                $_SESSION['usuario_nombre'] = $usuario['nombre_cuenta'];
                $_SESSION['usuario_email'] = $usuario['email'];
                
                header('Location: /robinutri/index.php/chat');
                exit();
            } else {
                $this->mostrarError("Error al obtener datos del usuario", "/robinutri/index.php/login");
            }
        } else {
            $this->mostrarError("Contraseña incorrecta", "/robinutri/index.php/login");
        }
    }
    
    private function procesarRegistro() {
        $nombre = htmlspecialchars($_POST['nombre']);
        $apellido = htmlspecialchars($_POST['apellido']);
        $correo = htmlspecialchars($_POST['email']);
        $contraseña = htmlspecialchars($_POST['password']);
        
        // Validar campos
        if (empty($nombre) || empty($apellido) || empty($correo) || empty($contraseña)) {
            $this->mostrarError("Por favor, completa todos los campos", "/robinutri/index.php/login/registro");
            return;
        }
        
        // Validar formato de email
        if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
            $this->mostrarError("Por favor, ingresa un correo electrónico válido", "/robinutri/index.php/login/registro");
            return;
        }
        
        // Verificar si el correo ya existe
        if ($this->model->existeCorreo($correo)) {
            $this->mostrarError("El correo electrónico ya está registrado", "/robinutri/index.php/login/registro");
            return;
        }
        
        // Crear usuario
        $success = $this->model->crear($nombre, $apellido, $correo, $contraseña);
        
        if($success) {
            // Registro exitoso
            $usuario = $this->model->obtenerUsuarioPorEmail($correo);
            
            if ($usuario) {
                if (session_status() === PHP_SESSION_NONE) {
                    session_start();
                }
                $_SESSION['usuario_id'] = $usuario['id_cuenta'];
                $_SESSION['usuario_nombre'] = $usuario['nombre_cuenta'];
                $_SESSION['usuario_email'] = $usuario['email'];
                
                $this->mostrarExito("¡Registro exitoso! Bienvenido a RobiNutri", "/robinutri/index.php/chat");
            }
        } else {
            $this->mostrarError("Error al crear la cuenta. Inténtalo de nuevo.", "/robinutri/index.php/login/registro");
        }
    }
    
    private function mostrarError($mensaje, $redirect) {
        echo '<script type="text/javascript">
                alert("' . $mensaje . '");
                window.location.href = "' . $redirect . '";
              </script>';
    }
    
    private function mostrarExito($mensaje, $redirect) {
        echo '<script type="text/javascript">
                alert("' . $mensaje . '");
                window.location.href = "' . $redirect . '";
              </script>';
    }
    
    public function logout() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        session_destroy();
        header('Location: /robinutri/index.php/login');
        exit();
    }
}
?>