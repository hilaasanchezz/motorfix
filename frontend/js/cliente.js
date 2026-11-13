/**
 * Controlador de la vista cliente.html (panel del cliente).
 *
 * De momento gestiona el cierre de sesión y el listado, el alta, la edición y el
 * borrado de vehículos. Las citas y las facturas se añadirán más adelante.
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

/**
 * Carga los vehículos del cliente en la tabla "Mis vehículos".
 *
 * Se llama al abrir cliente.html con onLoad="cargarVehiculos()" en el body, y otra vez
 * después de añadir, editar o eliminar un vehículo. Pide a vehiculo_sw.php la acción
 * "listar", guarda los vehículos en la variable vehiculos y crea una fila por cada uno
 * con sus botones "Editar" y "Eliminar". Si falla, muestra el mensaje en rojo.
 */
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

            /* Crea la última celda con los botones "Editar" y "Eliminar". Cada botón llama
               a su función con el id de este vehículo, por ejemplo editarVehiculo(1),
               para que sepa qué vehículo tiene que editar o eliminar */
            const acciones = document.createElement("td");

            const editar = document.createElement("input");
            editar.type = "button";
            editar.value = "Editar";
            editar.setAttribute("onClick", "editarVehiculo(" + vehiculo.id + ")");
            acciones.appendChild(editar);

            const eliminar = document.createElement("input");
            eliminar.type = "button";
            eliminar.value = "Eliminar";
            eliminar.setAttribute("onClick", "eliminarVehiculo(" + vehiculo.id + ")");
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

/**
 * Prepara el formulario para editar un vehículo.
 *
 * Se llama desde el botón "Editar" de cada fila con onClick="editarVehiculo(id)".
 * Rellena el formulario con los datos del vehículo, guarda su id en vehiculoEditando
 * y cambia el texto del botón a "Guardar cambios". No envía nada al servidor.
 *
 * @param {number} id Id del vehículo que se va a editar
 */
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

/**
 * Guarda el vehículo del formulario: lo añade si es nuevo o lo actualiza si se está editando.
 *
 * Se llama desde el botón del formulario con onClick="guardarVehiculo()". Envía a
 * vehiculo_sw.php la acción "insertar" o "actualizar" según vehiculoEditando y muestra
 * el mensaje del servidor en verde o en rojo. Si va bien, vacía el formulario, lo deja
 * en modo añadir y vuelve a cargar la tabla.
 */
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

/**
 * Elimina un vehículo del cliente después de pedir confirmación.
 *
 * Se llama desde el botón "Eliminar" de cada fila con onClick="eliminarVehiculo(id)".
 * Si el usuario acepta, envía a vehiculo_sw.php la acción "eliminar" y muestra el mensaje
 * del servidor en verde o en rojo. Si va bien, vuelve a cargar la tabla y, si se estaba
 * editando ese vehículo, deja el formulario en modo añadir.
 *
 * @param {number} id Id del vehículo que se va a eliminar
 */
function eliminarVehiculo(id) {
    // Pide confirmación antes de borrar. Si el usuario cancela, no hace nada
    if (!confirm("¿Seguro que quieres eliminar este vehículo? También se borrarán sus citas.")) {
        return;
    }

    // Prepara la petición con el token y el id del vehículo que se va a eliminar
    let request = {
        action: "eliminar",
        auth_key: localStorage.getItem("auth_key"),
        data: { id: id },
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
        // Muestra el mensaje del servidor
        const mensaje = document.getElementById("mensaje-vehiculo");
        mensaje.textContent = response.msg;

        if (response.success) {
            // Si ha ido bien pinta el mensaje en verde
            mensaje.className = "exito";

            /* Si se estaba editando el vehículo eliminado, vacía el formulario
               y lo deja otra vez en modo añadir */
            if (vehiculoEditando === id) {
                document.getElementById("form-vehiculo").reset();
                vehiculoEditando = null;
                document.getElementById("anadir-vehiculo").value = "Añadir vehículo";
            }

            // Vuelve a cargar la tabla para que desaparezca el vehículo eliminado
            cargarVehiculos();
        } else {
            // Si no se ha podido eliminar pinta el mensaje en rojo
            mensaje.className = "error";
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
