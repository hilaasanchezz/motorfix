/**
 * Controlador de la vista oficina.html (panel de oficina).
 *
 * Gestiona el listado, el alta y la edición de los clientes del taller.
 *
 * @author Hilario Sánchez Pérez
 */

// Dirección del servicio web de los clientes (sube de frontend/html/ a backend/sw/)
const urlCliente = "../../backend/sw/cliente_sw.php";

// Id del cliente que se está editando, o null si el formulario está en modo añadir
let clienteEditando = null;

// Clientes que se han cargado en la tabla
let clientes = [];

/**
 * Carga todos los clientes en la tabla "Clientes".
 *
 * Se llama al abrir oficina.html con onLoad="cargarClientes()" en el body. Pide a
 * cliente_sw.php la acción "listar", guarda los clientes en la variable clientes y crea
 * una fila por cada uno con sus botones "Editar" y "Eliminar". Si falla, pone el mensaje en rojo.
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

        // Guarda los clientes para poder rellenar el formulario al pulsar "Editar"
        clientes = response.data;

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
 * Prepara el formulario para editar un cliente.
 *
 * Se llama desde el botón "Editar" de cada fila con onClick="editarCliente(id)".
 * Rellena el formulario con los datos del cliente, guarda su id en clienteEditando
 * y cambia el texto del botón a "Guardar cambios". No envía nada al servidor.
 *
 * @param {number} id Id del cliente que se va a editar
 */
function editarCliente(id) {
    // Busca en la lista cargada el cliente con ese id
    let cliente = null;
    for (let i = 0; i < clientes.length; i++) {
        if (clientes[i].id === id) {
            cliente = clientes[i];
        }
    }

    // Rellena el formulario con sus datos para poder cambiarlos
    document.getElementById("dni").value = cliente.dni;
    document.getElementById("nombre").value = cliente.nombre;
    document.getElementById("apellidos").value = cliente.apellidos;
    document.getElementById("telefono").value = cliente.telefono;
    document.getElementById("email").value = cliente.email;
    document.getElementById("direccion").value = cliente.direccion;

    /* Guarda el id para que guardarCliente() sepa que tiene que actualizar
       y cambia el texto del botón para que se vea que se está editando */
    clienteEditando = id;
    document.getElementById("anadir-cliente").value = "Guardar cambios";
}

/**
 * Guarda el cliente del formulario: lo añade si es nuevo o lo actualiza si se está editando.
 *
 * Se llama desde el botón del formulario con onClick="guardarCliente()". Envía a
 * cliente_sw.php la acción "insertar" o "actualizar" según clienteEditando y pone el
 * mensaje del servidor en verde o en rojo. Si va bien, vacía el formulario, lo deja
 * en modo añadir y vuelve a cargar la tabla.
 */
function guardarCliente() {
    // Elige la acción: "insertar" si se está añadiendo y "actualizar" si se está editando
    let accion;
    if (clienteEditando === null) {
        accion = "insertar";
    } else {
        accion = "actualizar";
    }

    // Prepara la petición con los datos del formulario y el id del cliente que se edita
    let request = {
        action: accion,
        auth_key: localStorage.getItem("auth_key"),
        data: {
            id: clienteEditando,
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

            /* Vacía el formulario, lo deja otra vez en modo añadir y vuelve a cargar
               la tabla para que se vean los cambios */
            document.getElementById("form-cliente").reset();
            clienteEditando = null;
            document.getElementById("anadir-cliente").value = "Añadir cliente";
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
