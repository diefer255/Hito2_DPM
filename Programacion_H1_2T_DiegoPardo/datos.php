<?php
// Conexion a la bd
$host = 'localhost';
$dbname = 'streamweb';
$username = 'root';
$password = 'campusfp';

try {
    $mysqli = new mysqli($host, $username, $password, $dbname);
    if ($mysqli->connect_error) {
        throw new Exception("Error de conexión: " . $mysqli->connect_error);
    }
} catch (Exception $e) {
    die("Error al conectar con la base de datos: " . $e->getMessage());
}

// Mensaje de exito o error
function mostrarMensaje($mensaje, $tipo = 'error') {
    $color = $tipo === 'error' ? 'red' : 'green';
    echo "<div style='color: $color; margin-bottom: 10px;'>$mensaje</div>";
}

// Registrar un usuario
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'registrar') {
    $nombre = $_POST['nombre'] ?? '';
    $apellidos = $_POST['apellidos'] ?? '';
    $correo = $_POST['correo'] ?? '';
    $edad = intval($_POST['edad'] ?? 0);
    $plan_base = $_POST['plan_base'] ?? '';
    $paquete = $_POST['paquete'] ?? '';
    $duracion = $_POST['duracion'] ?? '';

    // Validar campos
    if (empty($nombre) || empty($apellidos) || empty($correo) || empty($plan_base) || empty($duracion)) {
        mostrarMensaje('Todos los campos son obligatorios.');
    } elseif (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        mostrarMensaje('El correo no tiene un formato válido.');
    } elseif ($edad < 18 && $paquete !== 'Infantil') {
        mostrarMensaje('Los usuarios menores de 18 años solo pueden contratar el Pack Infantil.');
    } elseif ($plan_base === 'Básico' && $paquete && $paquete !== 'Ninguno') {
        mostrarMensaje('El Plan Básico solo permite un paquete adicional.');
    } elseif ($paquete === 'Deporte' && $duracion !== 'anual') {
        mostrarMensaje('El Pack Deporte solo puede ser contratado si la duración de la suscripción es de 1 año.');
    } else {
        try {
            // Preparar la consulta para insertar usuario
            $stmt = $mysqli->prepare("INSERT INTO usuarios (nombre, apellidos, correo, edad, id_plan_base, duracion_suscripcion) 
                                    VALUES (?, ?, ?, ?, (SELECT id_plan FROM planes_base WHERE nombre_plan = ?), ?)");
            $stmt->bind_param("sssiss", $nombre, $apellidos, $correo, $edad, $plan_base, $duracion);
            $stmt->execute();

            if ($stmt->affected_rows > 0) {
                $usuarioId = $mysqli->insert_id;

                // Insertar paquete adicional si existe
                if ($paquete && $paquete !== 'Ninguno') {
                    $stmt = $mysqli->prepare("INSERT INTO suscripciones_paquetes (id_usuario, id_paquete) 
                                            VALUES (?, (SELECT id_paquete FROM paquetes_adicionales WHERE nombre_paquete = ?))");
                    $stmt->bind_param("is", $usuarioId, $paquete);
                    $stmt->execute();
                }

                mostrarMensaje('Usuario registrado correctamente.', 'success');
            } else {
                throw new Exception($mysqli->error);
            }
        } catch (Exception $e) {
            mostrarMensaje("Error al registrar el usuario: " . $e->getMessage());
        }
    }
}

// Eliminar un usuario
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'eliminar') {
    $usuarioId = $_POST['id_usuario'] ?? 0;

    try {
        // Eliminar paquetes
        $stmt = $mysqli->prepare("DELETE FROM suscripciones_paquetes WHERE id_usuario = ?");
        $stmt->bind_param("i", $usuarioId);
        $stmt->execute();

        // Eliminar usuario
        $stmt = $mysqli->prepare("DELETE FROM usuarios WHERE id_usuario = ?");
        $stmt->bind_param("i", $usuarioId);
        $stmt->execute();

        if ($stmt->affected_rows > 0) {
            mostrarMensaje('Usuario eliminado correctamente.', 'success');
        } else {
            throw new Exception("No se encontró el usuario");
        }
    } catch (Exception $e) {
        mostrarMensaje("Error al eliminar el usuario: " . $e->getMessage());
    }
}

// Mirar usuarios
$usuarios = [];
try {
    $query = "SELECT 
                u.id_usuario, u.nombre, u.apellidos, u.correo, u.edad, u.duracion_suscripcion, 
                p.nombre_plan, p.precio AS precio_plan,
                COALESCE(pa.nombre_paquete, 'Ninguno') AS paquete, COALESCE(pa.precio, 0) AS precio_paquete
            FROM usuarios u
            LEFT JOIN planes_base p ON u.id_plan_base = p.id_plan
            LEFT JOIN suscripciones_paquetes sp ON u.id_usuario = sp.id_usuario
            LEFT JOIN paquetes_adicionales pa ON sp.id_paquete = pa.id_paquete";
    
    $result = $mysqli->query($query);
    if ($result) {
        $usuarios = $result->fetch_all(MYSQLI_ASSOC);
        $result->free();
    } else {
        throw new Exception($mysqli->error);
    }
} catch (Exception $e) {
    mostrarMensaje("Error al consultar usuarios: " . $e->getMessage());
}

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['accion']) && $_GET['accion'] === 'modificar') {
    $usuarioId = $_GET['id_usuario'];

    try {
        $stmt = $mysqli->prepare("SELECT 
                                    u.id_usuario, u.nombre, u.apellidos, u.correo, u.edad, u.duracion_suscripcion, 
                                    p.nombre_plan, p.precio AS precio_plan,
                                    COALESCE(pa.nombre_paquete, 'Ninguno') AS paquete, COALESCE(pa.precio, 0) AS precio_paquete
                                FROM usuarios u
                                LEFT JOIN planes_base p ON u.id_plan_base = p.id_plan
                                LEFT JOIN suscripciones_paquetes sp ON u.id_usuario = sp.id_usuario
                                LEFT JOIN paquetes_adicionales pa ON sp.id_paquete = pa.id_paquete
                                WHERE u.id_usuario = ?");
        $stmt->bind_param("i", $usuarioId);
        $stmt->execute();
        $result = $stmt->get_result();
        $usuario = $result->fetch_assoc();
        $result->free();
    } catch (Exception $e) {
        mostrarMensaje("Error al consultar los datos del usuario: " . $e->getMessage());
    }
}

// Modificar usuario
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'actualizar') {
    $usuarioId = $_POST['id_usuario'];
    $nombre = $_POST['nombre'] ?? '';
    $apellidos = $_POST['apellidos'] ?? '';
    $correo = $_POST['correo'] ?? '';
    $edad = intval($_POST['edad'] ?? 0);
    $plan_base = $_POST['plan_base'] ?? '';
    $paquete = $_POST['paquete'] ?? '';
    $duracion = $_POST['duracion'] ?? '';

    // Validar campos
    if (empty($nombre) || empty($apellidos) || empty($correo) || empty($plan_base) || empty($duracion)) {
        mostrarMensaje('Todos los campos son obligatorios.');
    } elseif (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        mostrarMensaje('El correo no tiene un formato válido.');
    } elseif ($edad < 18 && $paquete !== 'Infantil') {
        mostrarMensaje('Los usuarios menores de 18 años solo pueden contratar el Pack Infantil.');
    } elseif ($plan_base === 'Básico' && $paquete && $paquete !== 'Ninguno') {
        mostrarMensaje('El Plan Básico solo permite un paquete adicional.');
    } elseif ($paquete === 'Deporte' && $duracion !== 'anual') {
        mostrarMensaje('El Pack Deporte solo puede ser contratado si la duración de la suscripción es de 1 año.');
    } else {
        try {
            // Modificar usuario
            $stmt = $mysqli->prepare("UPDATE usuarios SET 
                                        nombre = ?, 
                                        apellidos = ?, 
                                        correo = ?, 
                                        edad = ?, 
                                        id_plan_base = (SELECT id_plan FROM planes_base WHERE nombre_plan = ?), 
                                        duracion_suscripcion = ? 
                                    WHERE id_usuario = ?");
            $stmt->bind_param("sssissi", $nombre, $apellidos, $correo, $edad, $plan_base, $duracion, $usuarioId);
            $stmt->execute();

            // Modificar paquete
            if ($paquete && $paquete !== 'Ninguno') {
                // Eliminar paquetes existentes
                $stmt = $mysqli->prepare("DELETE FROM suscripciones_paquetes WHERE id_usuario = ?");
                $stmt->bind_param("i", $usuarioId);
                $stmt->execute();

                // Insertar nuevo paquete
                $stmt = $mysqli->prepare("INSERT INTO suscripciones_paquetes (id_usuario, id_paquete) 
                                        VALUES (?, (SELECT id_paquete FROM paquetes_adicionales WHERE nombre_paquete = ?))");
                $stmt->bind_param("is", $usuarioId, $paquete);
                $stmt->execute();
            }

            mostrarMensaje('Usuario actualizado correctamente.', 'success');
            header("Location: " . $_SERVER['PHP_SELF']);
        } catch (Exception $e) {
            mostrarMensaje("Error al actualizar el usuario: " . $e->getMessage());
        }
    }
}

// Cerrar conexion
$mysqli->close();
?>