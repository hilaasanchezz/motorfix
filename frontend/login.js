/**
 * Controlador de la vista login.html.
 *
 * Envía el email y la contraseña a auth_sw.php con fetch(), guarda el token
 * (auth_key) en localStorage y lleva al usuario a su panel según su rol.
 *
 * @author Hilario Sánchez Pérez
 */

// Dirección del servicio web de autenticación (sube de frontend/ a backend/)
const url = "../backend/auth_sw.php";

/**
 * Inicia sesión con el email y la contraseña del formulario.
 *
 * Se llama desde el botón "Entrar" de login.html con onClick="login()".
 * Si va bien, guarda el auth_key en localStorage y redirige: el cliente va a
 * cliente.html y el resto de roles ven un aviso (sus paneles llegan en la semana 5).
 * Si falla, muestra el mensaje del servidor en rojo.
 */
function login() {
    // Recoge el email y la contraseña que ha escrito el usuario
    let usuario = {};
    usuario.email = document.getElementById("email").value;
    usuario.password = document.getElementById("password").value;

    // Prepara la petición con la acción y los datos que espera auth_sw.php
    let request = {};
    request.action = "login";
    request.data = usuario;

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
            // Si el login ha fallado muestra el mensaje del servidor en rojo
            mostrarMensaje(response.msg, false);
            return;
        }

        /* Guarda el token en localStorage, la memoria del navegador que no se borra
           al cambiar de página. Las demás pantallas lo leerán con localStorage.getItem()
           para enviarlo al servidor en cada petición */
        localStorage.setItem("auth_key", response.data.auth_key);

        /* Lleva a cada usuario a su panel según su rol. De momento solo existe
           el panel de cliente; los de oficina, mecánico y admin llegarán en la semana 5 */
        if (response.data.rol === "cliente") {
            window.location.href = "cliente.html";
        } else {
            mostrarMensaje("Tu panel todavía no está disponible", false);
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
