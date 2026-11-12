/**
 * Controlador de la vista registro.html.
 *
 * Envía los datos del formulario a auth_sw.php con fetch() para crear la cuenta
 * de cliente, guarda el token (auth_key) en localStorage y lleva al cliente a su panel.
 *
 * @author Hilario Sánchez Pérez
 */

// Dirección del servicio web de autenticación (sube de frontend/html/ a backend/sw/)
const url = "../../backend/sw/auth_sw.php";

/**
 * Crea la cuenta de cliente con los datos del formulario.
 *
 * Se llama desde el botón "Crear cuenta" de registro.html con onClick="registro()".
 * Si va bien y llega un auth_key, lo guarda en localStorage y redirige a cliente.html.
 * Si falla (por ejemplo, email o DNI repetidos), muestra el mensaje del servidor en rojo.
 */
function registro() {
    // Recoge los datos que ha escrito el cliente en el formulario
    let cliente = {};
    cliente.nombre = document.getElementById("nombre").value;
    cliente.apellidos = document.getElementById("apellidos").value;
    cliente.dni = document.getElementById("dni").value;
    cliente.telefono = document.getElementById("telefono").value;
    cliente.direccion = document.getElementById("direccion").value;
    cliente.email = document.getElementById("email").value;
    cliente.password = document.getElementById("password").value;

    // Prepara la petición con la acción y los datos que espera auth_sw.php
    let request = {};
    request.action = "registro";
    request.data = cliente;

    /* Envía la petición por POST sin recargar la página. JSON.stringify() convierte
       el objeto en texto, que es lo que viaja entre el navegador y el servidor */
    fetch(url, {
        method: "POST",
        body: JSON.stringify(request),
        headers: {
            "Content-Type": "application/json",
        },
    })
    // Convierte el texto que devuelve el servidor en un objeto de JavaScript
    .then((res) => res.json())
    .then((response) => {
        if (!response.success) {
            // Si no se ha podido crear la cuenta muestra el mensaje del servidor en rojo
            mostrarMensaje(response.msg, false);
            return;
        }

        /* Si el servidor ha devuelto un token, lo guarda en localStorage (igual que en el login)
           y lleva al cliente a su panel. Si no, muestra el mensaje para que inicie sesión */
        if (response.data.auth_key) {
            localStorage.setItem("auth_key", response.data.auth_key);
            window.location.href = "cliente.html";
        } else {
            mostrarMensaje(response.msg, true);
        }
    })
    // Si la petición falla (por ejemplo, el servidor no responde) muestra un error
    .catch((error) => {
        console.error("Error:", error);
        mostrarMensaje("No se ha podido conectar con el servidor", false);
    });
}

/**
 * Escribe el texto en el párrafo #mensaje y le pone la clase "exito" o "error"
 * para que login.css lo pinte en verde o en rojo.
 *
 * @param {string}  texto Mensaje que se muestra
 * @param {boolean} exito true para verde (éxito), false para rojo (error)
 */
function mostrarMensaje(texto, exito) {
    const mensaje = document.getElementById("mensaje");
    mensaje.textContent = texto;
    mensaje.className = exito ? "exito" : "error";
}
