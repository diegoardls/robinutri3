<?php
session_start();

// Cargar configuración
require_once 'config/database.php';
require_once 'app/models/Database.php';

$request = $_SERVER['REQUEST_URI'];
$base_path = '/robinutri';

if (strpos($request, '/index.php/') !== false) {
    $path = str_replace($base_path . '/index.php', '', $request);
} else {
    $path = str_replace($base_path, '', $request);
}

$path = parse_url($path, PHP_URL_PATH);
$path = trim($path, '/');

// ⭐⭐ RUTAS CORREGIDAS
$routes = [
    '' => 'HomeController@index',
    'home' => 'HomeController@index',  
    'chat' => 'ChatController@index',
    'chat/send' => 'ChatController@sendMessage',
    'chat/loadMessages' => 'ChatController@loadMessages', 
    'chat/loadChats' => 'ChatController@loadChats', 
    'chat/chatsPorPerfil' => 'ChatController@verChatsPorPerfil',
    'chat/getChatsUsuario' => 'ChatController@getChatsUsuario',
    'chat/delete' => 'ChatController@deleteChat',
    'chat/create' => 'ChatController@createChat',
    'chat/rename' => 'ChatController@renameChat',
    'profiles' => 'ProfileController@index',
    'profiles/save' => 'ProfileController@save',
    'profiles/load' => 'ProfileController@load',
    'profiles/loadAll' => 'ProfileController@loadAll',
    'profiles/delete' => 'ProfileController@delete',
    'login' => 'LoginController@index',
    'login/registro' => 'LoginController@registro',
    'login/auth' => 'LoginController@auth',
    'logout' => 'LoginController@logout',
];

$controllerName = 'HomeController';
$method = 'index';

foreach ($routes as $route => $action) {
    if ($path === $route) {
        list($controllerName, $method) = explode('@', $action);
        break;
    }
}

$controllerFile = "app/controllers/{$controllerName}.php";

if (file_exists($controllerFile)) {
    require_once $controllerFile;
    
    if (class_exists($controllerName)) {
        $controller = new $controllerName();
        
        if (method_exists($controller, $method)) {
            $controller->$method();
        } else {
            http_response_code(404);
            echo "Error: Método '$method' no encontrado";
        }
    } else {
        http_response_code(500);
        echo "Error: Clase '$controllerName' no encontrada";
    }
} else {
    http_response_code(404);
    echo "Error: Archivo no encontrado: $controllerFile";
}
?>