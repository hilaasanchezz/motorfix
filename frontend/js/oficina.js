/**
 * Controlador de la vista oficina.html (panel de oficina).
 *
 * Gestiona el listado, el alta, la edición y el borrado de los clientes del taller,
 * el listado, el alta, la edición y el borrado de los vehículos de todos los clientes,
 * y el listado de todas las citas y el cambio de su estado.
 *
 * @author Hilario Sánchez Pérez
 */

// Dirección del servicio web de los clientes (sube de frontend/html/ a backend/sw/)
const urlCliente = "../../backend/sw/cliente_sw.php";

// Dirección del servicio web de los vehículos
const urlVehiculo = "../../backend/sw/vehiculo_sw.php";

// Dirección del servicio web de las citas
const urlCita = "../../backend/sw/cita_sw.php";

// Id del cliente que se está editando, o null si el formulario está en modo añadir
let clienteEditando = null;

// Clientes que se han cargado en la tabla
let clientes = [];

// Id del vehículo que se está editando, o null si el formulario está en modo añadir
let vehiculoEditando = null;

// Vehículos que se han cargado en la tabla
let vehiculos = [];

// Id de la cita que se está gestionando, o null si no se ha elegido ninguna
let citaGestionando = null;

// Citas que se han cargado en la tabla
let citas = [];

// Mecánicos del taller, para el desplegable de "Gestionar cita" y la columna "Mecánico"
let mecanicos = [];

/**
 * Carga todos los clientes en la tabla "Clientes".
 *
 * Se llama al abrir oficina.html con onLoad="cargarClientes()" en el body. Pide a
 * cliente_sw.php la acción "listar", guarda los clientes en la variable clientes, rellena
 * el desplegable de "Añadir vehículo", carga los vehículos y crea una fila por cada cliente
 * con sus botones "Editar" y "Eliminar". Si falla, pone el mensaje en rojo.
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

        // Pone los mismos clientes en el desplegable del formulario "Añadir vehículo"
        rellenarClientesVehiculo();

        /* Carga los vehículos después de tener los clientes, porque la tabla de vehículos
           necesita los clientes para poner el nombre de cada dueño. Así también se actualiza
           cuando se edita un cliente o se elimina con sus vehículos */
        cargarVehiculos();

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

            /* Crea la última celda con los botones "Editar" y "Eliminar". Cada botón tiene un
               addEventListener que, al hacer clic, llama a su función con el id de este cliente,
               por ejemplo editarCliente(1), para que sepa qué cliente tiene que editar o eliminar */
            const acciones = document.createElement("td");

            const editar = document.createElement("input");
            editar.type = "button";
            editar.value = "Editar";
            editar.addEventListener("click", function () {
                editarCliente(cliente.id);
            });
            acciones.appendChild(editar);

            const eliminar = document.createElement("input");
            eliminar.type = "button";
            eliminar.value = "Eliminar";
            eliminar.addEventListener("click", function () {
                eliminarCliente(cliente.id);
            });
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

/**
 * Elimina un cliente después de pedir confirmación.
 *
 * Se llama desde el botón "Eliminar" de cada fila con onClick="eliminarCliente(id)".
 * Si el usuario acepta, envía a cliente_sw.php la acción "eliminar" y pone el mensaje
 * del servidor en verde o en rojo. Si va bien, vuelve a cargar la tabla y, si se estaba
 * editando ese cliente, deja el formulario en modo añadir.
 *
 * @param {number} id Id del cliente que se va a eliminar
 */
function eliminarCliente(id) {
    // Pide confirmación antes de borrar. Si el usuario cancela, no hace nada
    if (!confirm("¿Seguro que quieres eliminar este cliente? También se borrarán sus vehículos y sus citas.")) {
        return;
    }

    // Prepara la petición con el token y el id del cliente que se va a eliminar
    let request = {
        action: "eliminar",
        auth_key: localStorage.getItem("auth_key"),
        data: { id: id },
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
        // Pone el mensaje del servidor
        const mensaje = document.getElementById("mensaje-cliente");
        mensaje.textContent = response.msg;

        if (response.success) {
            // Si ha ido bien pone el mensaje en verde
            mensaje.className = "exito";

            /* Si se estaba editando el cliente eliminado, vacía el formulario
               y lo deja otra vez en modo añadir */
            if (clienteEditando === id) {
                document.getElementById("form-cliente").reset();
                clienteEditando = null;
                document.getElementById("anadir-cliente").value = "Añadir cliente";
            }

            // Vuelve a cargar la tabla para que desaparezca el cliente eliminado
            cargarClientes();
        } else {
            // Si no se ha podido eliminar pone el mensaje en rojo
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

/**
 * Carga todos los vehículos en la tabla "Vehículos".
 *
 * Se llama desde cargarClientes(), cuando ya están cargados los clientes. Pide a
 * vehiculo_sw.php la acción "listar", guarda los vehículos en la variable vehiculos, carga
 * los mecánicos y las citas, y crea una fila por cada vehículo con el nombre de su cliente,
 * sus datos y sus botones "Editar" y "Eliminar". Si falla, pone el mensaje en rojo.
 */
function cargarVehiculos() {
    // Prepara la petición para listar los vehículos con el token guardado en localStorage
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
        if (!response.success) {
            // Si el servidor no devuelve los vehículos pone su mensaje en rojo
            const mensaje = document.getElementById("mensaje-vehiculo");
            mensaje.textContent = response.msg;
            mensaje.className = "error";
            return;
        }

        // Guarda los vehículos para poder rellenar el formulario al pulsar "Editar"
        vehiculos = response.data;

        /* Carga los mecánicos y después las citas, porque la tabla de citas necesita los
           vehículos y los mecánicos para poner la matrícula, el cliente y el mecánico de cada cita */
        cargarMecanicos();

        // Vacía la tabla antes de rellenarla para que no se repitan las filas
        const lista = document.getElementById("lista-vehiculos");
        lista.textContent = "";

        // Crea una fila por cada vehículo con sus datos en el mismo orden que la cabecera
        for (let i = 0; i < response.data.length; i++) {
            const vehiculo = response.data[i];
            const fila = document.createElement("tr");

            // Busca en los clientes cargados el dueño del vehículo para poner su nombre
            let nombreCliente = "";
            for (let j = 0; j < clientes.length; j++) {
                if (clientes[j].id === vehiculo.cliente_id) {
                    nombreCliente = clientes[j].nombre + " " + clientes[j].apellidos;
                }
            }

            const datos = [nombreCliente, vehiculo.matricula, vehiculo.marca, vehiculo.modelo,
                           vehiculo.anio, vehiculo.color, vehiculo.kilometros, vehiculo.combustible];
            for (let j = 0; j < datos.length; j++) {
                const celda = document.createElement("td");
                celda.textContent = datos[j];
                fila.appendChild(celda);
            }

            /* Crea la última celda con los botones "Editar" y "Eliminar". Cada botón tiene un
               addEventListener que, al hacer clic, llama a su función con el id de este vehículo,
               por ejemplo editarVehiculo(1), para que sepa qué vehículo tiene que editar o eliminar */
            const acciones = document.createElement("td");

            const editar = document.createElement("input");
            editar.type = "button";
            editar.value = "Editar";
            editar.addEventListener("click", function () {
                editarVehiculo(vehiculo.id);
            });
            acciones.appendChild(editar);

            const eliminar = document.createElement("input");
            eliminar.type = "button";
            eliminar.value = "Eliminar";
            eliminar.addEventListener("click", function () {
                eliminarVehiculo(vehiculo.id);
            });
            acciones.appendChild(eliminar);

            fila.appendChild(acciones);
            lista.appendChild(fila);
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
 * Rellena el desplegable de clientes del formulario "Añadir vehículo".
 *
 * Se llama desde cargarClientes(), así se actualiza solo cada vez que se añade, edita
 * o elimina un cliente. Deja una primera opción "--seleccionar--" y una opción por cada
 * cliente, con su id como valor y el DNI, el nombre y los apellidos como texto.
 */
function rellenarClientesVehiculo() {
    // Vacía el desplegable antes de rellenarlo para que no se repitan las opciones
    const select = document.getElementById("cliente-vehiculo");
    select.textContent = "";

    // Añade una primera opción vacía para que oficina tenga que elegir un cliente
    const primera = document.createElement("option");
    primera.value = "";
    primera.textContent = "--seleccionar--";
    select.appendChild(primera);

    /* Crea una opción por cada cliente cargado en la tabla. Su value es el id del
       cliente, que es lo que se enviará al añadir el vehículo, y su texto es el DNI,
       el nombre y los apellidos */
    for (let i = 0; i < clientes.length; i++) {
        const opt = document.createElement("option");
        opt.value = clientes[i].id;
        opt.textContent = clientes[i].dni + " - " + clientes[i].nombre + " " + clientes[i].apellidos;
        select.appendChild(opt);
    }
}

/**
 * Prepara el formulario para editar un vehículo.
 *
 * Se llama desde el botón "Editar" de cada fila de la tabla "Vehículos". Rellena el
 * formulario con los datos del vehículo (también su cliente en el desplegable), guarda su
 * id en vehiculoEditando y cambia el texto del botón a "Guardar cambios". No envía nada al servidor.
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
    document.getElementById("cliente-vehiculo").value = vehiculo.cliente_id;
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
 * vehiculo_sw.php la acción "insertar" o "actualizar" según vehiculoEditando, con el
 * cliente elegido en el desplegable, y pone el mensaje del servidor en verde o en rojo.
 * Si va bien, vacía el formulario, lo deja en modo añadir y vuelve a cargar la tabla.
 */
function guardarVehiculo() {
    // Elige la acción: "insertar" si se está añadiendo y "actualizar" si se está editando
    let accion;
    if (vehiculoEditando === null) {
        accion = "insertar";
    } else {
        accion = "actualizar";
    }

    // Prepara la petición con el id del vehículo que se edita, el cliente elegido y los datos del formulario
    let request = {
        action: accion,
        auth_key: localStorage.getItem("auth_key"),
        data: {
            id: vehiculoEditando,
            cliente_id: document.getElementById("cliente-vehiculo").value,
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
        // Pone el mensaje del servidor en verde si ha ido bien o en rojo si no
        const mensaje = document.getElementById("mensaje-vehiculo");
        mensaje.textContent = response.msg;

        if (response.success) {
            mensaje.className = "exito";

            /* Vacía el formulario, lo deja otra vez en modo añadir y vuelve a cargar
               la tabla para que se vean los cambios */
            document.getElementById("form-vehiculo").reset();
            vehiculoEditando = null;
            document.getElementById("anadir-vehiculo").value = "Añadir vehículo";
            cargarVehiculos();
        } else {
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

/**
 * Elimina un vehículo después de pedir confirmación.
 *
 * Se llama desde el botón "Eliminar" de cada fila de la tabla "Vehículos". Si el usuario
 * acepta, envía a vehiculo_sw.php la acción "eliminar" y pone el mensaje del servidor en
 * verde o en rojo. Si va bien, vuelve a cargar la tabla y, si se estaba editando ese
 * vehículo, deja el formulario en modo añadir.
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
        // Pone el mensaje del servidor
        const mensaje = document.getElementById("mensaje-vehiculo");
        mensaje.textContent = response.msg;

        if (response.success) {
            // Si ha ido bien pone el mensaje en verde
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
            // Si no se ha podido eliminar pone el mensaje en rojo
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

/**
 * Carga todas las citas en la tabla "Citas".
 *
 * Se llama desde cargarMecanicos(), cuando ya están cargados los clientes, los vehículos y los
 * mecánicos, y otra vez después de guardar una cita.
 * Pide a cita_sw.php la acción "listar", guarda las citas en la variable citas y crea una
 * fila por cada una con la fecha, la matrícula, el cliente, el motivo, el estado, el mecánico
 * y su botón "Gestionar".
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

        // Guarda las citas para poder rellenar el formulario al pulsar "Gestionar"
        citas = response.data;

        // Vacía la tabla antes de rellenarla para que no se repitan las filas
        const lista = document.getElementById("lista-citas");
        lista.textContent = "";

        // Crea una fila por cada cita con sus datos en el mismo orden que la cabecera
        for (let i = 0; i < response.data.length; i++) {
            const cita = response.data[i];
            const fila = document.createElement("tr");

            /* Busca en los vehículos cargados el vehículo de la cita para poner su matrícula,
               y en los clientes cargados el dueño de ese vehículo para poner su nombre */
            let matricula = "";
            let nombreCliente = "";
            for (let j = 0; j < vehiculos.length; j++) {
                if (vehiculos[j].id === cita.vehiculo_id) {
                    matricula = vehiculos[j].matricula;

                    for (let k = 0; k < clientes.length; k++) {
                        if (clientes[k].id === vehiculos[j].cliente_id) {
                            nombreCliente = clientes[k].nombre + " " + clientes[k].apellidos;
                        }
                    }
                }
            }

            /* Si la cita todavía no tiene mecánico pone "Sin asignar"; si lo tiene,
               lo busca en los mecánicos cargados para poner su nombre */
            let mecanico = "";
            if (cita.mecanico_id === null) {
                mecanico = "Sin asignar";
            } else {
                for (let j = 0; j < mecanicos.length; j++) {
                    if (mecanicos[j].id === cita.mecanico_id) {
                        mecanico = mecanicos[j].nombre;
                    }
                }
            }

            const datos = [cita.fecha_cita, matricula, nombreCliente, cita.motivo, cita.estado, mecanico];
            for (let j = 0; j < datos.length; j++) {
                const celda = document.createElement("td");
                celda.textContent = datos[j];
                fila.appendChild(celda);
            }

            /* Crea la última celda con el botón "Gestionar". Tiene un addEventListener que,
               al hacer clic, llama a gestionarCita() con el id de esta cita, por ejemplo
               gestionarCita(1), para que sepa qué cita tiene que gestionar */
            const acciones = document.createElement("td");

            const gestionar = document.createElement("input");
            gestionar.type = "button";
            gestionar.value = "Gestionar";
            gestionar.addEventListener("click", function () {
                gestionarCita(cita.id);
            });
            acciones.appendChild(gestionar);

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
 * Prepara el formulario "Gestionar cita" con la cita elegida.
 *
 * Se llama desde el botón "Gestionar" de cada fila de la tabla "Citas". Si la cita está
 * en reparación o finalizada, avisa de que no se puede cambiar. Si no, pone su estado en
 * el desplegable, guarda su id en citaGestionando e indica qué cita se está gestionando.
 * No envía nada al servidor.
 *
 * @param {number} id Id de la cita que se va a gestionar
 */
function gestionarCita(id) {
    // Busca en la lista cargada la cita con ese id
    let cita = null;
    for (let i = 0; i < citas.length; i++) {
        if (citas[i].id === id) {
            cita = citas[i];
        }
    }

    const mensaje = document.getElementById("mensaje-cita");

    if (cita.estado === "en_reparacion" || cita.estado === "finalizada") {
        // Las citas en reparación o finalizadas las lleva el mecánico, así que no se pueden gestionar
        citaGestionando = null;
        mensaje.textContent = "No se puede cambiar una cita en reparación o finalizada";
        mensaje.className = "error";
    } else {
        // Pone el estado actual de la cita en el desplegable para poder cambiarlo
        document.getElementById("estado-cita").value = cita.estado;

        // Guarda el id para que guardarCita() sepa qué cita tiene que cambiar y lo indica
        citaGestionando = id;
        mensaje.textContent = "Gestionando la cita del " + cita.fecha_cita;
        mensaje.className = "";
    }
}

/**
 * Guarda el estado elegido para la cita que se está gestionando.
 *
 * Se llama desde el botón "Guardar" del formulario con onClick="guardarCita()". Si no se
 * ha elegido ninguna cita, avisa. Si no, envía a cita_sw.php la acción "actualizar" con el
 * id de la cita y el estado elegido, y pone el mensaje del servidor en verde o en rojo.
 * Si va bien, vacía el formulario y vuelve a cargar la tabla.
 */
function guardarCita() {
    const mensaje = document.getElementById("mensaje-cita");

    // Si no se ha pulsado "Gestionar" en ninguna cita, avisa y no envía nada
    if (citaGestionando === null) {
        mensaje.textContent = "Pulsa \"Gestionar\" en una cita de la tabla";
        mensaje.className = "error";
        return;
    }

    // Prepara la petición con el id de la cita y el estado elegido
    let request = {
        action: "actualizar",
        auth_key: localStorage.getItem("auth_key"),
        data: {
            id: citaGestionando,
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

            /* Vacía el formulario, olvida la cita que se estaba gestionando y vuelve
               a cargar la tabla para que se vea el estado nuevo */
            document.getElementById("form-cita").reset();
            citaGestionando = null;
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
 * Carga los mecánicos del taller y después las citas.
 *
 * Se llama desde cargarVehiculos(). Pide a cita_sw.php la acción "mecanicos", guarda los
 * mecánicos en la variable mecanicos, rellena el desplegable de "Gestionar cita" y carga
 * las citas, que necesitan los mecánicos para poner su nombre. Si falla, pone el mensaje en rojo.
 */
function cargarMecanicos() {
    // Prepara la petición para listar los mecánicos con el token guardado en localStorage
    let request = { action: "mecanicos", auth_key: localStorage.getItem("auth_key") };

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
            // Si el servidor no devuelve los mecánicos pone su mensaje en rojo
            const mensaje = document.getElementById("mensaje-cita");
            mensaje.textContent = response.msg;
            mensaje.className = "error";
            return;
        }

        // Guarda los mecánicos y los pone en el desplegable de "Gestionar cita"
        mecanicos = response.data;
        rellenarMecanicosCita();

        // Carga las citas ahora que ya están los mecánicos
        cargarCitas();
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
 * Rellena el desplegable de mecánicos del formulario "Gestionar cita".
 *
 * Se llama desde cargarMecanicos(). Deja una primera opción "Sin asignar" con el valor vacío
 * y una opción por cada mecánico, con su id como valor y su nombre como texto.
 */
function rellenarMecanicosCita() {
    // Vacía el desplegable antes de rellenarlo para que no se repitan las opciones
    const select = document.getElementById("mecanico-cita");
    select.textContent = "";

    // Añade una primera opción vacía para las citas que todavía no tienen mecánico
    const primera = document.createElement("option");
    primera.value = "";
    primera.textContent = "Sin asignar";
    select.appendChild(primera);

    /* Crea una opción por cada mecánico. Su value es el id del mecánico, que es lo
       que se enviará al asignarlo, y su texto es su nombre */
    for (let i = 0; i < mecanicos.length; i++) {
        const opt = document.createElement("option");
        opt.value = mecanicos[i].id;
        opt.textContent = mecanicos[i].nombre;
        select.appendChild(opt);
    }
}
