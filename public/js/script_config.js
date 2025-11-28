// ==========================================================
// === FUNCIÓN: MUESTRA UN MENSAJE DE ESTADO TEMPORAL ===
// ==========================================================
function displayStatusMessage(message, type = 'success') {
    const perfilesListaContenedor = document.getElementById('perfiles-creados-lista');
    if (!perfilesListaContenedor) return;

    // 1. Crear el elemento del mensaje
    const messageElement = document.createElement('div');
    messageElement.className = `status-message status-${type}`; // status-success o status-error
    messageElement.textContent = message;

    // 2. Insertarlo al inicio de la lista
    perfilesListaContenedor.prepend(messageElement);

    // 3. Programar la desaparición (después de 3 segundos)
    setTimeout(() => {
        messageElement.style.opacity = '0'; // Inicia la transición CSS
        messageElement.style.transform = 'translateY(-15px)';
        
        // Quitar el elemento del DOM después de la transición
        messageElement.addEventListener('transitionend', () => {
            messageElement.remove();
        });
    }, 3000); 
}

// ==========================================================
// === FUNCIÓN PARA CARGAR TODOS LOS PERFILES ===
// ==========================================================
function loadProfiles() {
    console.log('🔄 loadProfiles() llamado');
    
    const perfilesListaContenedor = document.getElementById('perfiles-creados-lista');
    if (!perfilesListaContenedor) {
        console.error('❌ No se encontró el contenedor de perfiles');
        return;
    }
    
    console.log('📡 Cargando perfiles desde:', '/robinutri/index.php/profiles/loadAll');
    
    fetch('/robinutri/index.php/profiles/loadAll')
        .then(response => {
            console.log('📡 Response status:', response.status);
            return response.json();
        })
        .then(data => {
            console.log('📡 Datos recibidos:', data);
            
            perfilesListaContenedor.innerHTML = ''; // Limpiar lista
            
            if (data.success && data.perfiles.length > 0) {
                console.log('✅ Perfiles cargados:', data.perfiles.length);
                
                data.perfiles.forEach(perfil => {
                    const perfilHTML = `
                        <div class="profile-display-item" data-id="${perfil.id}">
                            <div class="profile-info">
                                <span class="profile-name">
                                    <strong>${perfil.nombre} ${perfil.apellido}</strong>
                                </span>
                                <span class="profile-details">
                                    (Edad: ${perfil.edad})
                                </span>
                            </div>
                            <div class="profile-actions">
                                <i class="fas fa-pen edit-profile-icon" title="Editar perfil"></i>
                                <i class="fas fa-trash-alt delete-profile-icon" title="Eliminar perfil"></i>
                            </div>
                        </div>
                    `;
                    perfilesListaContenedor.innerHTML += perfilHTML;
                });
                
            } else {
                console.log('ℹ️ No hay perfiles o error');
                perfilesListaContenedor.innerHTML = '<p style="padding: 10px;">Aún no hay perfiles creados.</p>';
            }
        })
        .catch(error => {
            console.error('❌ Error cargando perfiles:', error);
            perfilesListaContenedor.innerHTML = '<p style="color: red; padding: 10px;">Error al conectar con el servidor.</p>';
        });
}

document.addEventListener('DOMContentLoaded', () => {

    // --- 1. REFERENCIAS DEL FORMULARIO DE REGISTRO ---
    const registroForm = document.getElementById('registro-form');
    const perfilNombre = document.getElementById('perfil-nombre');
    const perfilApellido = document.getElementById('perfil-apellido');
    const perfilEdad = document.getElementById('perfil-edad');
    const perfilAlergias = document.getElementById('perfil-alergias');
    const perfilEnfermedades = document.getElementById('perfil-enfermedades');
    const perfilObservaciones = document.getElementById('perfil-observaciones');
    const perfilesListaContenedor = document.getElementById('perfiles-creados-lista');

    // ==========================================================
    // === FUNCIÓN PARA CARGAR DATOS DEL PERFIL PARA EDICIÓN ===
    // ==========================================================
    function loadProfileForEditing(profileId) {
        console.log('📥 Cargando perfil para edición ID:', profileId);
        
        fetch(`/robinutri/index.php/profiles/load?id=${profileId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    console.log('✅ Datos del perfil recibidos:', data.perfil);
                    populateFormForEditing(data.perfil);
                } else {
                    alert(`Error al cargar el perfil: ${data.message}`);
                }
            })
            .catch(error => {
                console.error('Error de red al cargar perfil:', error);
                alert('No se pudo conectar con el servidor para cargar el perfil.');
            });
    }

    // ==========================================================
    // === FUNCIÓN PARA LLENAR EL FORMULARIO ===
    // ==========================================================
    function populateFormForEditing(perfil) {
        const submitButton = document.querySelector('#registro-form button[type="submit"]');

        console.log('📝 Llenando formulario con datos:', perfil);

        // 1. Llenar los campos de entrada
        perfilNombre.value = perfil.nombre || '';
        perfilApellido.value = perfil.apellido || '';
        perfilEdad.value = perfil.edad || '';
        perfilAlergias.value = perfil.alergias || '';
        perfilEnfermedades.value = perfil.enfermedades || '';
        perfilObservaciones.value = perfil.observaciones || '';

        // 2. CLAVE: Marcar el formulario en modo edición
        registroForm.dataset.editingId = perfil.id;

        // 3. Cambiar el texto del botón
        submitButton.textContent = "Guardar Cambios";
        submitButton.classList.add('edit-mode-btn');

        console.log('✅ Formulario llenado correctamente. Modo edición activado para ID:', perfil.id);

        // Opcional: Desplazarse al formulario para que el usuario vea que se llenó
        registroForm.scrollIntoView({ behavior: 'smooth' });
    }

    // ==========================================================
    // === FUNCIONES DE VALIDACIÓN Y CREACIÓN (validateRegistroForm) ===
    // ==========================================================
    function validateRegistroForm(event) {
        event.preventDefault(); 
        let isValid = true;
        
        // --- Funciones de utilidad ---
        const clearError = (element) => {
            element.style.borderColor = '#c2e2ff'; 
            element.style.boxShadow = 'none';
            element.removeAttribute('title'); 
        };
        const setError = (element, message) => {
            element.style.borderColor = 'red';
            element.style.boxShadow = '0 0 5px rgba(255, 0, 0, 0.5)';
            element.setAttribute('title', message);
            isValid = false; 
        };
        
        // --- 1. Validar Nombre y Apellido ---
        const nameRegex = /^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]+$/; 

        // Nombre
        clearError(perfilNombre);
        if (perfilNombre.value.trim() === "") {
            setError(perfilNombre, "El Nombre es obligatorio.");
        } else if (!nameRegex.test(perfilNombre.value.trim())) {
            setError(perfilNombre, "El Nombre solo debe contener letras.");
        }

        // Apellido
        clearError(perfilApellido);
        if (perfilApellido.value.trim() === "") {
            setError(perfilApellido, "El Apellido es obligatorio.");
        } else if (!nameRegex.test(perfilApellido.value.trim())) {
            setError(perfilApellido, "El Apellido solo debe contener letras.");
        }
        
        // --- 2. Validar Edad ---
        const edadValue = parseInt(perfilEdad.value);
        
        clearError(perfilEdad); 
        if (perfilEdad.value.trim() === "" || isNaN(edadValue)) {
            setError(perfilEdad, "La Edad es obligatoria y debe ser un número.");
        } else if (edadValue < 1 || edadValue > 120) {
            setError(perfilEdad, "La Edad debe ser entre 1 y 120 años.");
        }

        // --- 3. Campos Opcionales (Limpieza) ---
        clearError(perfilAlergias);
        clearError(perfilEnfermedades);
        clearError(perfilObservaciones);

        if (!isValid) {
            alert("Por favor, corrige los campos marcados en rojo.");
            return; // Detener el envío si hay errores de validación
        }
        
        // 2. Preparar el envío de datos
        const formData = new FormData(registroForm);
        const editingId = registroForm.dataset.editingId || null;


        console.log('📝 Modo:', editingId ? 'EDICIÓN' : 'CREACIÓN');
       console.log('📝 ID del perfil:', editingId);
        if (editingId) {
            formData.append('id', editingId);
        }

        // 3. Envío AJAX al servidor MVC (CON index.php explícito)
        fetch('/robinutri/index.php/profiles/save', {
            method: 'POST',
            body: formData
        })
        .then(response => {
            console.log('📡 Response status:', response.status);
            
            return response.text().then(text => {
                console.log('📡 Response text:', text);
                
                try {
                    return JSON.parse(text);
                } catch (e) {
                    console.error('❌ No es JSON válido:', text);
                    throw new Error('Respuesta no es JSON: ' + text.substring(0, 100));
                }
            });
        })
        .then(data => {
            console.log('✅ JSON parseado:', data);
            if (data.success) {
                displayStatusMessage(data.message, 'success');
            
                // Resetear formulario
                registroForm.reset(); 
                registroForm.removeAttribute('data-editing-id');
                const submitButton = document.querySelector('#registro-form button[type="submit"]');
                submitButton.textContent = "Crear Perfil";
                submitButton.classList.remove('edit-mode-btn');
                
                // Recargar la lista
                loadProfiles(); 
            } else {
                alert(`❌ Error: ${data.message}`);
            }
        })
        .catch(error => {
            console.error('❌ Error completo:', error);
            alert('Error: ' + error.message);
        });
    }

    // ==========================================================
    // === FUNCIÓN DE ELIMINACIÓN (deleteProfile) ===
    // ==========================================================
    function deleteProfile(profileId) {
        if (!confirm('¿Estás seguro de que quieres eliminar este perfil?')) {
            return; 
        }

        const formData = new FormData();
        formData.append('id', profileId); 

        fetch('/robinutri/index.php/profiles/delete', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displayStatusMessage(data.message, 'warning');
                loadProfiles(); // Recargar la lista
            } else {
                alert(`Error: ${data.message}`);
            }
        })
        .catch(error => {
            console.error('Error de red al intentar eliminar perfil:', error);
            alert('No se pudo conectar con el servidor para eliminar el perfil.');
        });
    }

    // ==========================================================
    // === ASIGNACIÓN DE EVENTOS ===
    // ==========================================================
    
    // 1. Evento SUBMIT del formulario
    if (registroForm) {
        registroForm.addEventListener('submit', validateRegistroForm);
    }

    // 2. Delegación de Eventos para ELIMINACIÓN y EDICIÓN
    if (perfilesListaContenedor) {
        perfilesListaContenedor.addEventListener('click', function(e) {
            console.log('🎯 Click detectado en:', e.target);
            
            const deleteIcon = e.target.closest('.delete-profile-icon');
            const editIcon = e.target.closest('.edit-profile-icon');

            if (deleteIcon) {
                e.stopPropagation(); 
                const profileItem = deleteIcon.closest('.profile-display-item');
                const profileId = profileItem.dataset.id;
                console.log('🗑️ Eliminar perfil ID:', profileId);
                
                if (profileId) {
                    deleteProfile(profileId); 
                }
            } else if (editIcon) {
                e.stopPropagation(); 
                const profileItem = editIcon.closest('.profile-display-item');
                const profileId = profileItem.dataset.id;
                console.log('✏️ Editar perfil ID:', profileId);

                if (profileId) {
                    loadProfileForEditing(profileId);
                }
            }
        });
    }
    
    // 3. LLAMADA INICIAL para cargar perfiles al iniciar
    loadProfiles(); 

}); // Cierre final de DOMContentLoaded