// Dirección del servicio web de autenticación (sube de frontend/ a backend/)
const urlAuth = "../backend/auth_sw.php";

// Se llama desde el botón "Cerrar sesión" de cliente.html con onClick="cerrarSesion()"
function cerrarSesion() {
    /* Prepara la petición de logout con el token que se guardó en localStorage al
       iniciar sesión. El token va junto a "action", fuera de "data" */
    let request = {};
    request.action = "logout";
    request.auth_key = localStorage.getItem("auth_key");

    // Envía la petición por POST para que el servidor borre el token de la BD
    fetch(urlAuth, {
        method: "POST",
        body: JSON.stringify(request),
        headers: {
            "Content-Type": "application/json",
        },
    })
    // Convierte el texto que devuelve el servidor en un objeto de JavaScript
    .then((res) => res.json())
    .then((response) => {
        /* Borra el token del navegador y vuelve al login. Se hace aunque el servidor
           diga que no había sesión (por ejemplo, si el token ya había caducado) */
        localStorage.removeItem("auth_key");
        window.location.href = "login.html";
    })
    // Si la petición falla (por ejemplo, el servidor no responde) avisa al usuario
    .catch((error) => {
        console.error("Error:", error);
        alert("No se ha podido conectar con el servidor");
    });
}
