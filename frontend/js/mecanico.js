/**
 * Controlador de la vista mecanico.html (panel de mecánico).
 *
 * Gestiona la hoja de trabajo del mecánico: el listado de las citas que tiene asignadas,
 * el cambio de su estado, la imputación de horas de trabajo y el repuesto usado.
 *
 * @author Hilario Sánchez Pérez
 */

// Dirección del servicio web de las citas (sube de frontend/html/ a backend/sw/)
const urlCita = "../../backend/sw/cita_sw.php";

// Citas asignadas al mecánico que se han cargado en la tabla
let citas = [];

// Id de la cita en la que trabaja el mecánico, o null si no ha elegido ninguna
let citaElegida = null;

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

/**
 * Elige la cita en la que va a trabajar el mecánico.
 *
 * Se llama desde el botón "Elegir" de cada fila de la tabla "Mis citas". Guarda su id en
 * citaElegida, pone en el desplegable el siguiente estado de la cita (en reparación si está
 * confirmada y finalizada si está en reparación) e indica qué cita se ha elegido.
 * No envía nada al servidor.
 *
 * @param {number} id Id de la cita elegida
 */
function elegirCita(id) {
    // Busca en la lista cargada la cita con ese id
    let cita = null;
    for (let i = 0; i < citas.length; i++) {
        if (citas[i].id === id) {
            cita = citas[i];
        }
    }

    // Pone en el desplegable el estado al que normalmente pasará la cita
    if (cita.estado === "confirmada") {
        document.getElementById("estado-cita").value = "en_reparacion";
    } else if (cita.estado === "en_reparacion") {
        document.getElementById("estado-cita").value = "finalizada";
    }

    // Guarda el id para que las demás funciones sepan en qué cita se trabaja y lo indica
    citaElegida = id;
    const mensaje = document.getElementById("mensaje-cita");
    mensaje.textContent = "Trabajando en la cita del " + cita.fecha_cita + " (" + cita.matricula + ")";
    mensaje.className = "";
}

/**
 * Guarda el estado elegido para la cita en la que trabaja el mecánico.
 *
 * Se llama desde el botón "Guardar" del formulario "Cambiar estado" con onClick="guardarEstado()".
 * Si no se ha elegido ninguna cita, avisa. Si no, envía a cita_sw.php la acción "actualizar"
 * con el id de la cita y el estado, y pone el mensaje del servidor en verde o en rojo.
 * Si va bien, vuelve a cargar la tabla.
 */
function guardarEstado() {
    const mensaje = document.getElementById("mensaje-cita");

    // Si no se ha pulsado "Elegir" en ninguna cita, avisa y no envía nada
    if (citaElegida === null) {
        mensaje.textContent = "Pulsa \"Elegir\" en una cita de la tabla";
        mensaje.className = "error";
        return;
    }

    // Prepara la petición con el id de la cita y el estado elegido
    let request = {
        action: "actualizar",
        auth_key: localStorage.getItem("auth_key"),
        data: {
            id: citaElegida,
            estado: document.getElementById("estado-cita").value,
        },
    };

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
        // Pone el mensaje del servidor en verde si ha ido bien o en rojo si no
        mensaje.textContent = response.msg;

        if (response.success) {
            mensaje.className = "exito";

            /* Vuelve a cargar la tabla para que se vea el estado nuevo. La cita sigue
               elegida para poder imputarle horas y repuestos */
            cargarCitas();
        } else {
            mensaje.className = "error";
        }
    })
    // Si la petición falla (por ejemplo, el servidor no responde) muestra un error
    .catch((error) => {
        console.error("Error:", error);
        mensaje.textContent = "No se ha podido conectar con el servidor";
        mensaje.className = "error";
    });
}

/**
 * Imputa las horas de trabajo a la cita en la que trabaja el mecánico.
 *
 * Se llama desde el botón "Imputar horas" con onClick="imputarHoras()". Si no se ha elegido
 * ninguna cita, avisa. Si no, envía a cita_sw.php la acción "imputar_horas" con el id de la
 * cita, la descripción y las horas, y pone el mensaje del servidor en verde o en rojo.
 * Si va bien, vacía el formulario.
 */
function imputarHoras() {
    const mensaje = document.getElementById("mensaje-horas");

    // Si no se ha pulsado "Elegir" en ninguna cita, avisa y no envía nada
    if (citaElegida === null) {
        mensaje.textContent = "Pulsa \"Elegir\" en una cita de la tabla";
        mensaje.className = "error";
        return;
    }

    // Prepara la petición con el id de la cita, la descripción y las horas del formulario
    let request = {
        action: "imputar_horas",
        auth_key: localStorage.getItem("auth_key"),
        data: {
            id: citaElegida,
            descripcion: document.getElementById("descripcion").value,
            horas: document.getElementById("horas-trabajo").value,
        },
    };

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
        // Pone el mensaje del servidor en verde si ha ido bien o en rojo si no
        mensaje.textContent = response.msg;

        if (response.success) {
            mensaje.className = "exito";

            // Vacía el formulario para poder imputar otro trabajo
            document.getElementById("form-horas").reset();
        } else {
            mensaje.className = "error";
        }
    })
    // Si la petición falla (por ejemplo, el servidor no responde) muestra un error
    .catch((error) => {
        console.error("Error:", error);
        mensaje.textContent = "No se ha podido conectar con el servidor";
        mensaje.className = "error";
    });
}

/**
 * Añade el repuesto elegido a la cita en la que trabaja el mecánico.
 *
 * Se llama desde el botón "Añadir repuesto" con onClick="anadirRepuesto()". Si no se ha elegido
 * ninguna cita, avisa. Si no, envía a cita_sw.php la acción "anadir_repuesto" con el id de la
 * cita y el del repuesto, y pone el mensaje del servidor en verde o en rojo.
 * Si va bien, vacía el formulario.
 */
function anadirRepuesto() {
    const mensaje = document.getElementById("mensaje-repuesto");

    // Si no se ha pulsado "Elegir" en ninguna cita, avisa y no envía nada
    if (citaElegida === null) {
        mensaje.textContent = "Pulsa \"Elegir\" en una cita de la tabla";
        mensaje.className = "error";
        return;
    }

    // Prepara la petición con el id de la cita y el del repuesto elegido en el desplegable
    let request = {
        action: "anadir_repuesto",
        auth_key: localStorage.getItem("auth_key"),
        data: {
            id: citaElegida,
            repuesto_id: document.getElementById("repuesto").value,
        },
    };

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
        // Pone el mensaje del servidor en verde si ha ido bien o en rojo si no
        mensaje.textContent = response.msg;

        if (response.success) {
            mensaje.className = "exito";

            // Deja el desplegable otra vez en "--seleccionar--"
            document.getElementById("form-repuesto").reset();
        } else {
            mensaje.className = "error";
        }
    })
    // Si la petición falla (por ejemplo, el servidor no responde) muestra un error
    .catch((error) => {
        console.error("Error:", error);
        mensaje.textContent = "No se ha podido conectar con el servidor";
        mensaje.className = "error";
    });
}
