<?php
include 'datos.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
<nav>
  <div>
    <button class="nav-link" id="nav-contact-tab" type="button" onclick="window.location.href = 'index.php'">Inicio</button>
    <button class="nav-link" id="nav-disabled-tab" type="button" onclick="window.location.href = 'precios.html'">Precios</button>
  </div>
</nav>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>StreamWeb</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th, td { border: 1px solid #ccc; padding: 10px; text-align: left; }
        th { background-color: #f4f4f4; }
        form { max-width: 500px; margin: 20px auto; }
        label { display: block; margin: 10px 0 5px; }
        input, select, button { width: 100%; padding: 10px; margin-bottom: 10px; }
        button { background-color: #007BFF; color: #fff;}
        button:hover { background-color: #0056b3; }
    </style>
</head>
<body>
    <h1>Gestión de Usuarios - StreamWeb</h1>

    <!-- Formulario de registro -->
    <form method="POST" action="">
        <input type="hidden" name="accion" value="registrar">
        <label for="nombre">Nombre:</label>
        <input type="text" name="nombre" id="nombre" required>
        <label for="apellidos">Apellidos:</label>
        <input type="text" name="apellidos" id="apellidos" required>
        <label for="correo">Correo Electrónico:</label>
        <input type="email" name="correo" id="correo" required>
        <label for="edad">Edad:</label>
        <input type="number" name="edad" id="edad" min="1" required>
        <label for="plan_base">Plan Base:</label>
        <select name="plan_base" id="plan_base" required>
            <option value="Plan Básico">Plan Básico</option>
            <option value="Plan Estándar">Plan Estándar</option>
            <option value="Plan Premium">Plan Premium</option>
        </select>
        <label for="paquete">Paquete Adicional:</label>
        <select name="paquete" id="paquete">
            <option value="Ninguno">Ninguno</option>
            <option value="Deporte">Deporte</option>
            <option value="Cine">Cine</option>
            <option value="Infantil">Infantil</option>
        </select>
        <label for="duracion">Duración de la Suscripción:</label>
        <select name="duracion" id="duracion" required>
            <option value="mensual">Mensual</option>
            <option value="anual">Anual</option>
        </select>
        <button type="submit">Registrar Usuario</button>
    </form>

    <!-- Formulario de modificación-->
    <?php if (isset($usuario)): ?>
        <h2>Modificar Usuario</h2>
        <form method="POST" action="">
            <input type="hidden" name="accion" value="actualizar">
            <input type="hidden" name="id_usuario" value="<?= $usuario['id_usuario'] ?>">

            <label for="nombre">Nombre:</label>
            <input type="text" name="nombre" id="nombre" value="<?= $usuario['nombre'] ?>" required>

            <label for="apellidos">Apellidos:</label>
            <input type="text" name="apellidos" id="apellidos" value="<?= $usuario['apellidos'] ?>" required>

            <label for="correo">Correo Electrónico:</label>
            <input type="email" name="correo" id="correo" value="<?= $usuario['correo'] ?>" required>

            <label for="edad">Edad:</label>
            <input type="number" name="edad" id="edad" value="<?= $usuario['edad'] ?>" min="1" required>

            <label for="plan_base">Plan Base:</label>
            <select name="plan_base" id="plan_base" required>
                <option value="Plan Básico" <?= $usuario['nombre_plan'] === 'Plan Básico' ? 'selected' : '' ?>>Plan Básico</option>
                <option value="Plan Estándar" <?= $usuario['nombre_plan'] === 'Plan Estándar' ? 'selected' : '' ?>>Plan Estándar</option>
                <option value="Plan Premium" <?= $usuario['nombre_plan'] === 'Plan Premium' ? 'selected' : '' ?>>Plan Premium</option>
            </select>

            <label for="paquete">Paquete Adicional:</label>
            <select name="paquete" id="paquete">
                <option value="Ninguno" <?= $usuario['paquete'] === 'Ninguno' ? 'selected' : '' ?>>Ninguno</option>
                <option value="Deporte" <?= $usuario['paquete'] === 'Deporte' ? 'selected' : '' ?>>Deporte</option>
                <option value="Cine" <?= $usuario['paquete'] === 'Cine' ? 'selected' : '' ?>>Cine</option>
                <option value="Infantil" <?= $usuario['paquete'] === 'Infantil' ? 'selected' : '' ?>>Infantil</option>
            </select>

            <label for="duracion">Duración de la Suscripción:</label>
            <select name="duracion" id="duracion" required>
                <option value="mensual" <?= $usuario['duracion_suscripcion'] === 'mensual' ? 'selected' : '' ?>>Mensual</option>
                <option value="anual" <?= $usuario['duracion_suscripcion'] === 'anual' ? 'selected' : '' ?>>Anual</option>
            </select>

            <button type="submit">Actualizar Usuario</button>
        </form>
    <?php endif; ?>

    <!-- Mostrar usuarios registrados -->
    <h2>Usuarios Registrados</h2>
    <table>
        <thead>
            <tr>
                <th>Nombre</th>
                <th>Correo</th>
                <th>Edad</th>
                <th>Plan Base</th>
                <th>Paquete</th>
                <th>Duración</th>
                <th>Precio Mensual</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($usuarios as $usuario): 
            $precios_planes = [
                "Plan Básico" => 9.99,
                "Plan Estándar" => 13.99,
                "Plan Premium" => 17.99
            ];

            $precios_paquetes = [
                "Deporte" => 6.99,
                "Cine" => 7.99,
                "Infantil" => 4.99,
                "Ninguno" => 0
            ];
            
            $precio_plan = $precios_planes[$usuario['nombre_plan']];
            $precio_paquete = $precios_paquetes[$usuario['paquete']];
            $precio_total = $precios_planes[$usuario['nombre_plan']];
            if ($usuario['paquete'] !== "Ninguno") {
                $precio_total += $precios_paquetes[$usuario['paquete']];
            }
            ?>
                <tr>
                    <td><?= $usuario['nombre'] ?></td>
                    <td><?= $usuario['correo'] ?></td>
                    <td><?= $usuario['edad'] ?></td>
                    <td><?= $usuario['nombre_plan'] ?></td>
                    <td><?= $usuario['paquete'] ?></td>
                    <td><?= $usuario['duracion_suscripcion'] ?></td>
                    <td>Plan:<?= $precio_plan . " €" ?> <br>Paquete: <?= $precio_paquete . " €" ?><br>Total: <?= $precio_total . " €" ?> </td>
                    <td>
                        <form method="GET">
                            <input type="hidden" name="accion" value="modificar">
                            <input type="hidden" name="id_usuario" value="<?= $usuario['id_usuario'] ?>">
                            <button type="submit">Modificar</button>
                        </form>
                        <form method="POST">
                            <input type="hidden" name="accion" value="eliminar">
                            <input type="hidden" name="id_usuario" value="<?= $usuario['id_usuario'] ?>">
                            <button type="submit" onclick="return confirm('¿Estás seguro de que deseas eliminar este usuario?')">Eliminar</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</body>
</html>
