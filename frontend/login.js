// Dirección del servicio web de autenticación (sube de frontend/ a backend/)
const url = "../backend/auth_sw.php";

// Se llama desde el botón "Entrar" de login.html con onClick="login()"
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
        // Muestra el mensaje del servidor en verde si ha ido bien o en rojo si no
        mostrarMensaje(response.msg, response.success);
    })
    // Si la petición falla (por ejemplo, el servidor no responde) muestra un error
    .catch((error) => {
        console.error("Error:", error);
        mostrarMensaje("No se ha podido conectar con el servidor", false);
    });
}

/* Escribe el texto en el párrafo #mensaje y le pone la clase "exito" o "error"
   para que login.css lo pinte en verde o en rojo */
function mostrarMensaje(texto, exito) {
    const mensaje = document.getElementById("mensaje");
    mensaje.textContent = texto;
    mensaje.className = exito ? "exito" : "error";
}
