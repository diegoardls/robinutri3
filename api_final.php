<?php
// api_final.php - CON OPENAI REAL
header('Content-Type: application/json');

// Limpiar cualquier output antes del JSON
if (ob_get_level()) ob_clean();

// ⭐⭐ CONFIGURACIÓN OPENAI - CAMBIA ESTA API KEY
$OPENAI_API_KEY = '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';

try {
    // Solo POST requests
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Método no permitido');
    }

    // Obtener datos
    $mensaje = trim($_POST['mensaje'] ?? '');
    $chatId = $_POST['chat_id'] ?? '1';
    $perfilId = $_POST['perfil_id'] ?? null;
    
    if (empty($mensaje)) {
        throw new Exception('Mensaje vacío');
    }

    // Obtener información del perfil si está disponible
    $contextoPerfil = "";
    if ($perfilId) {
        $contextoPerfil = obtenerContextoPerfil($perfilId);
    }

    // Generar respuesta con OpenAI
    $respuesta = obtenerRespuestaOpenAI($mensaje, $contextoPerfil, $OPENAI_API_KEY);
    
    echo json_encode([
        'success' => true,
        'bot_response' => $respuesta,
        'timestamp' => date('H:i:s'),
        'source' => 'openai'
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

exit;

// ⭐⭐ FUNCIÓN PARA OBTENER CONTEXTO DEL PERFIL
function obtenerContextoPerfil($perfilId) {
    // Si tienes base de datos funcionando, puedes obtener info del perfil aquí
    // Por ahora retornamos un contexto vacío
    return "";
    
    /* EJEMPLO CON BASE DE DATOS:
    try {
        require_once __DIR__ . '/app/config/database.php';
        require_once __DIR__ . '/app/models/Database.php';
        require_once __DIR__ . '/app/models/ProfileModel.php';
        
        $profileModel = new ProfileModel();
        $perfil = $profileModel->getById($perfilId);
        
        if ($perfil) {
            return "Información del niño: {$perfil['nombre']} {$perfil['apellido']}, {$perfil['edad']} años. " .
                   (!empty($perfil['alergias']) ? "Alergias: {$perfil['alergias']}. " : "") .
                   (!empty($perfil['enfermedades']) ? "Enfermedades: {$perfil['enfermedades']}. " : "");
        }
    } catch (Exception $e) {
        error_log("Error obteniendo perfil: " . $e->getMessage());
    }
    return "";
    */
}

// ⭐⭐ FUNCIÓN PRINCIPAL PARA OPENAI
function obtenerRespuestaOpenAI($mensajeUsuario, $contextoPerfil, $apiKey) {
    // Si no hay API key configurada, usar respuestas predefinidas
    if (empty($apiKey) || $apiKey === 'sk-tu-api-key-real-aqui') {
        return generarRespuestaPredefinida($mensajeUsuario);
    }
    
    // Personalidad del chatbot (traducida de tu Python)
    $personalidad = "Eres RobiNutri, un chatbot amigable y especializado que da consejos de nutrición para niños de 8 a 12 años. 
    
Contexto del niño: $contextoPerfil

INSTRUCCIONES ESPECÍFICAS:
- Solo respondes temas de nutrición infantil
- Para cualquier otro tema, respondes amablemente que solo puedes ayudar con nutrición
- Usa un lenguaje amigable, claro y apropiado para padres
- Incluye emojis relevantes ocasionalmente
- Sé conciso pero informativo
- Da recomendaciones prácticas y específicas
- Si preguntan sobre edades diferentes a 8-12 años, adapta tus consejos";

    try {
        $data = [
            'model' => 'gpt-3.5-turbo',
            'messages' => [
                [
                    'role' => 'system',
                    'content' => $personalidad
                ],
                [
                    'role' => 'user', 
                    'content' => $mensajeUsuario
                ]
            ],
            'max_tokens' => 500,
            'temperature' => 0.7
        ];
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => 'https://api.openai.com/v1/chat/completions',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $apiKey
            ],
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => true
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);
        
        if ($curlError) {
            throw new Exception("Error de conexión: " . $curlError);
        }
        
        if ($httpCode !== 200) {
            throw new Exception("Error API OpenAI: HTTP $httpCode");
        }
        
        $result = json_decode($response, true);
        
        if (isset($result['choices'][0]['message']['content'])) {
            return $result['choices'][0]['message']['content'];
        } else {
            throw new Exception("Respuesta inesperada de OpenAI");
        }
        
    } catch (Exception $e) {
        // Si falla OpenAI, usar respuestas predefinidas
        error_log("Error OpenAI: " . $e->getMessage());
        return generarRespuestaPredefinida($mensajeUsuario);
    }
}

// ⭐⭐ RESPUESTAS PREDEFINIDAS COMO FALLBACK
function generarRespuestaPredefinida($mensajeUsuario) {
    $mensaje = strtolower(trim($mensajeUsuario));
    
    if (strpos($mensaje, 'hola') !== false) {
        return "¡Hola! 👋 Soy RobiNutri, tu asistente especializado en nutrición infantil.\n\n¿En qué puedo ayudarte hoy? ¿Tienes alguna pregunta sobre alimentación, recetas o crecimiento?";
    }
    
    if (strpos($mensaje, 'fruta') !== false) {
        return "🍎 **FRUTAS ESENCIALES** 🍌\n\n• **Plátanos**: Potasio para músculos\n• **Manzanas**: Fibra para digestión  \n• **Naranjas**: Vitamina C para defensas\n• **Fresas**: Antioxidantes\n\n📊 **Porciones recomendadas:**\n- 8-12 años: 3-4 porciones/día";
    }
    
    if (strpos($mensaje, 'verdura') !== false) {
        return "🥦 **VERDURAS NUTRIENTES** 🥕\n\n• **Zanahorias**: Vitamina A (visión)\n• **Brócoli**: Hierro y calcio\n• **Espinacas**: Ácido fólico\n• **Calabazas**: Fibra y vitaminas\n\n💡 **Tip**: Sirve las verduras en formas divertidas.";
    }
    
    if (strpos($mensaje, 'alergia') !== false) {
        return "⚠️ **ALERGIAS ALIMENTARIAS COMUNES**\n\n• Leche • Huevos • Maní • Mariscos • Trigo\n\n🔍 Si sospechas de alergia, consulta con pediatra.";
    }
    
    if (strpos($mensaje, 'receta') !== false) {
        return "👩‍🍳 **RECETA FÁCIL**\n\n**🍌 Panqueques de Plátano**\n• 1 plátano\n• 1 huevo  \n• 2 cucharadas de avena\n\nMachacar, mezclar y cocinar. ¡Listo en 10 minutos!";
    }
    
    // Respuesta por defecto
    return "🤖 **ROBINUTRI - NUTRICIÓN INFANTIL**\n\n¡Hola! Soy especialista en nutrición para niños de 8-12 años.\n\nPuedo ayudarte con:\n🍎 Alimentación balanceada\n🥦 Recetas saludables  \n⚠️ Alergias alimentarias\n💧 Hidratación\n📈 Crecimiento\n\n¿En qué área necesitas ayuda?";
}
?>