/**
 * Controlador de la vista cliente.html (panel del cliente).
 *
 * De momento gestiona el cierre de sesión y el listado de vehículos. El resto
 * de la gestión de vehículos, las citas y las facturas se añadirán en la semana 4.
 *
 * @author Hilario Sánchez Pérez
 */

// Dirección del servicio web de autenticación (sube de frontend/html/ a backend/sw/)
const urlAuth = "../../backend/sw/auth_sw.php";

// Dirección del servicio web de los vehículos del cliente
const urlVehiculo = "../../backend/sw/vehiculo_sw.php";

/**
 * Cierra la sesión del cliente.
 *
 * Se llama desde el botón "Cerrar sesión" de cliente.html con onClick="cerrarSesion()".
 * Pide a auth_sw.php que borre el token de la BD, lo borra también de localStorage
 * y vuelve a login.html.
 */
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

function cargarVehiculos() {
    /* Prepara la petición para listar los vehículos con el token guardado en
       localStorage. El servidor solo devuelve los del cliente que ha iniciado sesión */
    let request = {};
    request.action = "listar";
    request.auth_key = localStorage.getItem("auth_key");

    // Envía la petición por POST a vehiculo_sw.php
    fetch(urlVehiculo, {
        method: "POST",
        body: JSON.stringify(request),
        headers: {
            "Content-Type": "application/json",
        },
    })
    // Convierte el texto que devuelve el servidor en un objeto de JavaScript
    .then((res) => res.json())
    .then((response) => {
        const mensaje = document.getElementById("mensaje-vehiculo");

        if (!response.success) {
            // Si el servidor no devuelve los vehículos muestra su mensaje en rojo
            mensaje.textContent = response.msg;
            mensaje.className = "error";
            return;
        }

        // Vacía la tabla antes de rellenarla para que no se repitan las filas
        const lista = document.getElementById("lista-vehiculos");
        lista.textContent = "";

        // Crea una fila por cada vehículo con sus datos en el mismo orden que la cabecera
        response.data.forEach((vehiculo) => {
            const fila = document.createElement("tr");

            const datos = [vehiculo.matricula, vehiculo.marca, vehiculo.modelo, vehiculo.anio,
                           vehiculo.color, vehiculo.kilometros, vehiculo.combustible];
            datos.forEach((dato) => {
                const celda = document.createElement("td");
                celda.textContent = dato;
                fila.appendChild(celda);
            });

            /* Añade la última celda con los botones de editar y eliminar. De momento
               no hacen nada: sus funciones llegarán en los siguientes commits */
            const acciones = document.createElement("td");

            const editar = document.createElement("input");
            editar.type = "button";
            editar.value = "Editar";
            acciones.appendChild(editar);

            const eliminar = document.createElement("input");
            eliminar.type = "button";
            eliminar.value = "Eliminar";
            acciones.appendChild(eliminar);

            fila.appendChild(acciones);
            lista.appendChild(fila);
        });
    })
    // Si la petición falla (por ejemplo, el servidor no responde) muestra un error
    .catch((error) => {
        console.error("Error:", error);
        const mensaje = document.getElementById("mensaje-vehiculo");
        mensaje.textContent = "No se ha podido conectar con el servidor";
        mensaje.className = "error";
    });
}
