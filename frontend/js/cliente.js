/**
 * Controlador de la vista cliente.html (panel del cliente).
 *
 * De momento gestiona el cierre de sesión y el listado, el alta y la edición de
 * vehículos. El borrado de vehículos, las citas y las facturas se añadirán en la semana 4.
 *
 * @author Hilario Sánchez Pérez
 */

// Dirección del servicio web de autenticación (sube de frontend/html/ a backend/sw/)
const urlAuth = "../../backend/sw/auth_sw.php";

// Dirección del servicio web de los vehículos del cliente
const urlVehiculo = "../../backend/sw/vehiculo_sw.php";

// Id del vehículo que se está editando, o null si el formulario está en modo añadir
let vehiculoEditando = null;

// Vehículos del cliente que se han cargado en la tabla
let vehiculos = [];

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
    let request = { action: "logout", auth_key: localStorage.getItem("auth_key") };

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
    let request = { action: "listar", auth_key: localStorage.getItem("auth_key") };

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

        // Guarda los vehículos para poder rellenar el formulario al pulsar "Editar"
        vehiculos = response.data;

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

            /* Añade la última celda con los botones de editar y eliminar. "Editar" lleva
               el id del vehículo en su onClick; "Eliminar" todavía no hace nada */
            const acciones = document.createElement("td");

            const editar = document.createElement("input");
            editar.type = "button";
            editar.value = "Editar";
            editar.setAttribute("onClick", "editarVehiculo(" + vehiculo.id + ")");
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

function editarVehiculo(id) {
    // Busca en la lista cargada el vehículo con ese id
    let vehiculo = null;
    for (let i = 0; i < vehiculos.length; i++) {
        if (vehiculos[i].id === id) {
            vehiculo = vehiculos[i];
        }
    }

    // Rellena el formulario con sus datos para poder cambiarlos
    document.getElementById("matricula").value = vehiculo.matricula;
    document.getElementById("marca").value = vehiculo.marca;
    document.getElementById("modelo").value = vehiculo.modelo;
    document.getElementById("anio").value = vehiculo.anio;
    document.getElementById("color").value = vehiculo.color;
    document.getElementById("kilometros").value = vehiculo.kilometros;
    document.getElementById("combustible").value = vehiculo.combustible;

    /* Guarda el id para que guardarVehiculo() sepa que tiene que actualizar
       y cambia el texto del botón para que se vea que se está editando */
    vehiculoEditando = id;
    document.getElementById("anadir-vehiculo").value = "Guardar cambios";
}

function guardarVehiculo() {
    // Elige la acción: "insertar" si se está añadiendo y "actualizar" si se está editando
    let accion;
    if (vehiculoEditando === null) {
        accion = "insertar";
    } else {
        accion = "actualizar";
    }

    // Prepara la petición con los datos del formulario y el id del vehículo que se edita
    let request = {
        action: accion,
        auth_key: localStorage.getItem("auth_key"),
        data: {
            id: vehiculoEditando,
            matricula: document.getElementById("matricula").value,
            marca: document.getElementById("marca").value,
            modelo: document.getElementById("modelo").value,
            anio: document.getElementById("anio").value,
            color: document.getElementById("color").value,
            kilometros: document.getElementById("kilometros").value,
            combustible: document.getElementById("combustible").value,
        },
    };

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
        // Muestra el mensaje del servidor en verde si ha ido bien o en rojo si no
        const mensaje = document.getElementById("mensaje-vehiculo");
        mensaje.textContent = response.msg;
        mensaje.className = response.success ? "exito" : "error";

        if (response.success) {
            /* Vacía el formulario, lo deja otra vez en modo añadir y vuelve a cargar
               la tabla para que se vean los cambios */
            document.getElementById("form-vehiculo").reset();
            vehiculoEditando = null;
            document.getElementById("anadir-vehiculo").value = "Añadir vehículo";
            cargarVehiculos();
        }
    })
    // Si la petición falla (por ejemplo, el servidor no responde) muestra un error
    .catch((error) => {
        console.error("Error:", error);
        const mensaje = document.getElementById("mensaje-vehiculo");
        mensaje.textContent = "No se ha podido conectar con el servidor";
        mensaje.className = "error";
    });
}
