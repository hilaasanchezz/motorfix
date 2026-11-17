/**
 * Controlador de la vista oficina.html (panel de oficina).
 *
 * Gestiona el listado y el alta de los clientes del taller.
 *
 * @author Hilario Sánchez Pérez
 */

// Dirección del servicio web de los clientes (sube de frontend/html/ a backend/sw/)
const urlCliente = "../../backend/sw/cliente_sw.php";

/**
 * Carga todos los clientes en la tabla "Clientes".
 *
 * Se llama al abrir oficina.html con onLoad="cargarClientes()" en el body. Pide a
 * cliente_sw.php la acción "listar" y crea una fila por cada cliente con sus botones
 * "Editar" y "Eliminar". Si falla, pone el mensaje en rojo.
 */
function cargarClientes() {
    // Prepara la petición para listar los clientes con el token guardado en localStorage
    let request = { action: "listar", auth_key: localStorage.getItem("auth_key") };

    // Envía la petición por POST a cliente_sw.php
    fetch(urlCliente, {
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
            // Si el servidor no devuelve los clientes pone su mensaje en rojo
            const mensaje = document.getElementById("mensaje-cliente");
            mensaje.textContent = response.msg;
            mensaje.className = "error";
            return;
        }

        // Vacía la tabla antes de rellenarla para que no se repitan las filas
        const lista = document.getElementById("lista-clientes");
        lista.textContent = "";

        // Crea una fila por cada cliente con sus datos en el mismo orden que la cabecera
        for (let i = 0; i < response.data.length; i++) {
            const cliente = response.data[i];
            const fila = document.createElement("tr");

            const datos = [cliente.dni, cliente.nombre, cliente.apellidos, cliente.telefono,
                           cliente.email, cliente.direccion];
            for (let j = 0; j < datos.length; j++) {
                const celda = document.createElement("td");
                celda.textContent = datos[j];
                fila.appendChild(celda);
            }

            /* Crea la última celda con los botones "Editar" y "Eliminar". Cada botón llama
               a su función con el id de este cliente, por ejemplo editarCliente(1),
               para que sepa qué cliente tiene que editar o eliminar */
            const acciones = document.createElement("td");

            const editar = document.createElement("input");
            editar.type = "button";
            editar.value = "Editar";
            editar.setAttribute("onClick", "editarCliente(" + cliente.id + ")");
            acciones.appendChild(editar);

            const eliminar = document.createElement("input");
            eliminar.type = "button";
            eliminar.value = "Eliminar";
            eliminar.setAttribute("onClick", "eliminarCliente(" + cliente.id + ")");
            acciones.appendChild(eliminar);

            fila.appendChild(acciones);
            lista.appendChild(fila);
        }
    })
    // Si la petición falla (por ejemplo, el servidor no responde) muestra un error
    .catch((error) => {
        console.error("Error:", error);
        const mensaje = document.getElementById("mensaje-cliente");
        mensaje.textContent = "No se ha podido conectar con el servidor";
        mensaje.className = "error";
    });
}

/**
 * Añade el cliente del formulario.
 *
 * Se llama desde el botón "Añadir cliente" con onClick="guardarCliente()". Envía a
 * cliente_sw.php la acción "insertar" con los datos del formulario y pone el mensaje
 * del servidor en verde o en rojo. Si va bien, vacía el formulario y vuelve a cargar la tabla.
 */
function guardarCliente() {
    // Prepara la petición con los datos del formulario
    let request = {
        action: "insertar",
        auth_key: localStorage.getItem("auth_key"),
        data: {
            dni: document.getElementById("dni").value,
            nombre: document.getElementById("nombre").value,
            apellidos: document.getElementById("apellidos").value,
            telefono: document.getElementById("telefono").value,
            email: document.getElementById("email").value,
            direccion: document.getElementById("direccion").value,
        },
    };

    // Envía la petición por POST a cliente_sw.php
    fetch(urlCliente, {
        method: "POST",
        body: JSON.stringify(request),
        headers: {
            "Content-Type": "application/json",
        },
    })
    // Convierte el texto que devuelve el servidor en un objeto de JavaScript
    .then((res) => res.json())
    .then((response) => {
        // Pone el mensaje del servidor en verde si ha ido bien o en rojo si no
        const mensaje = document.getElementById("mensaje-cliente");
        mensaje.textContent = response.msg;

        if (response.success) {
            mensaje.className = "exito";

            // Vacía el formulario y vuelve a cargar la tabla para que se vea el cliente nuevo
            document.getElementById("form-cliente").reset();
            cargarClientes();
        } else {
            mensaje.className = "error";
        }
    })
    // Si la petición falla (por ejemplo, el servidor no responde) muestra un error
    .catch((error) => {
        console.error("Error:", error);
        const mensaje = document.getElementById("mensaje-cliente");
        mensaje.textContent = "No se ha podido conectar con el servidor";
        mensaje.className = "error";
    });
}
