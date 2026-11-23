/**
 * Controlador de la vista mecanico.html (panel de mecánico).
 *
 * Gestiona la hoja de trabajo del mecánico: el listado de las citas que tiene asignadas.
 *
 * @author Hilario Sánchez Pérez
 */

// Dirección del servicio web de las citas (sube de frontend/html/ a backend/sw/)
const urlCita = "../../backend/sw/cita_sw.php";

// Citas asignadas al mecánico que se han cargado en la tabla
let citas = [];

/**
 * Carga las citas asignadas al mecánico en la tabla "Mis citas".
 *
 * Se llama al abrir mecanico.html con onLoad="cargarCitas()" en el body.
 * Pide a cita_sw.php la acción "listar", guarda las citas en la variable citas y crea una
 * fila por cada una con la fecha, la matrícula, la marca, el modelo, el motivo, el estado
 * y su botón "Elegir".
 * Si falla, pone el mensaje en rojo.
 */
function cargarCitas() {
    // Prepara la petición para listar las citas con el token guardado en localStorage
    let request = { action: "listar", auth_key: localStorage.getItem("auth_key") };

    // Envía la petición por POST a cita_sw.php
    fetch(urlCita, {
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
            // Si el servidor no devuelve las citas pone su mensaje en rojo
            const mensaje = document.getElementById("mensaje-cita");
            mensaje.textContent = response.msg;
            mensaje.className = "error";
            return;
        }

        // Guarda las citas para saber cuál se elige al pulsar "Elegir"
        citas = response.data;

        // Vacía la tabla antes de rellenarla para que no se repitan las filas
        const lista = document.getElementById("lista-citas");
        lista.textContent = "";

        // Crea una fila por cada cita con sus datos en el mismo orden que la cabecera
        for (let i = 0; i < response.data.length; i++) {
            const cita = response.data[i];
            const fila = document.createElement("tr");

            const datos = [cita.fecha_cita, cita.matricula, cita.marca, cita.modelo, cita.motivo, cita.estado];
            for (let j = 0; j < datos.length; j++) {
                const celda = document.createElement("td");
                celda.textContent = datos[j];
                fila.appendChild(celda);
            }

            /* Crea la última celda con el botón "Elegir". Tiene un addEventListener que,
               al hacer clic, llama a elegirCita() con el id de esta cita, por ejemplo
               elegirCita(1), para que sepa en qué cita va a trabajar el mecánico */
            const acciones = document.createElement("td");

            const elegir = document.createElement("input");
            elegir.type = "button";
            elegir.value = "Elegir";
            elegir.addEventListener("click", function () {
                elegirCita(cita.id);
            });
            acciones.appendChild(elegir);

            fila.appendChild(acciones);
            lista.appendChild(fila);
        }
    })
    // Si la petición falla (por ejemplo, el servidor no responde) muestra un error
    .catch((error) => {
        console.error("Error:", error);
        const mensaje = document.getElementById("mensaje-cita");
        mensaje.textContent = "No se ha podido conectar con el servidor";
        mensaje.className = "error";
    });
}
