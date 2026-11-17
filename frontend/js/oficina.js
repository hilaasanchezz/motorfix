/**
 * Controlador de la vista oficina.html (panel de oficina).
 *
 * Gestiona el listado de los clientes del taller.
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
