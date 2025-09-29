<?php
// 1. Configuración de la Base de Datos
$servername = "localhost"; // O la IP de tu servidor de base de datos
$username = "phpmyadmin"; // ¡Cambia esto por tu usuario de MySQL!
$password = "RedesInformaticas"; // ¡Cambia esto por tu contraseña de MySQL!
$dbname = "balatro_jokers_db";

// Crear conexión
$conn = new mysqli($servername, $username, $password, $dbname);

// Verificar conexión
if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}

// 2. Lógica para Subir un Nuevo Combo (manejo del formulario POST)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nombre_usuario = mysqli_real_escape_string($conn, $_POST['nombre_usuario']);
    $comentario = mysqli_real_escape_string($conn, $_POST['comentario']);
    $joker_ids = $_POST['joker_ids'] ?? []; // Array de IDs de los jokers seleccionados

    // Validar que se hayan seleccionado al menos un joker y que el nombre de usuario no esté vacío
    if (empty($nombre_usuario) || empty($joker_ids)) {
        echo "<p style='color: red;'>Error: Debes ingresar un nombre de usuario y seleccionar al menos un Joker.</p>";
    } else {
        // Iniciar transacción para asegurar que todo se guarde o nada se guarde
        $conn->begin_transaction();

        try {
            // Insertar el nuevo combo en la tabla 'combos'
            $sql_insert_combo = "INSERT INTO combos (nombre_usuario, comentario) VALUES (?, ?)";
            $stmt = $conn->prepare($sql_insert_combo);
            $stmt->bind_param("ss", $nombre_usuario, $comentario);
            $stmt->execute();

            $combo_id = $stmt->insert_id; // Obtener el ID del combo recién insertado
            $stmt->close();

            // Insertar las relaciones en la tabla 'combo_jokers'
            $sql_insert_combo_joker = "INSERT INTO combo_jokers (combo_id, joker_id) VALUES (?, ?)";
            $stmt_joker = $conn->prepare($sql_insert_combo_joker);
            $stmt_joker->bind_param("ii", $combo_id, $joker_id);

            foreach ($joker_ids as $joker_id) {
                // Asegurarse de que el joker_id sea un entero válido
                $joker_id = (int)$joker_id;
                $stmt_joker->execute();
            }
            $stmt_joker->close();

            $conn->commit(); // Confirmar la transacción
            echo "<p style='color: green;'>¡Combo subido con éxito!</p>";

        } catch (Exception $e) {
            $conn->rollback(); // Revertir la transacción si algo sale mal
            echo "<p style='color: red;'>Error al subir el combo: " . $e->getMessage() . "</p>";
        }
    }
}

// 3. Recuperar todos los Jokers para el formulario de selección
$all_jokers = [];
$sql_select_jokers = "SELECT ruta_imagen, alt_text FROM jokers";
$result_jokers = $conn->query($sql_select_jokers);
if ($result_jokers->num_rows > 0) {
    while($row = $result_jokers->fetch_assoc()) {
        $all_jokers[] = $row;
    }
}

// 4. Recuperar todos los Combos de la Comunidad para mostrarlos
$community_combos = [];
$sql_select_combos = "
    SELECT
        c.id AS combo_id,
        c.nombre_usuario,
        c.comentario,
        GROUP_CONCAT(j.ruta_imagen ) AS joker_rutas_imagen,
        GROUP_CONCAT(j.alt_text ) AS joker_alt_texts
    FROM
        combos AS c
    JOIN
        combo_jokers AS cj ON c.id = cj.combo_id
    JOIN
        jokers AS j ON cj.joker_id = j.id
    GROUP BY
        c.id
    ORDER BY
        c.fecha_creacion DESC;
";
$result_combos = $conn->query($sql_select_combos);

if ($result_combos->num_rows > 0) {
    while($row = $result_combos->fetch_assoc()) {
        $row['joker_rutas_imagen'] = explode(',', $row['joker_rutas_imagen']);
        $row['joker_alt_texts'] = explode(',', $row['joker_alt_texts']);
        $community_combos[] = $row;
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Combos de la Comunidad - Balatro</title>
    <link rel="stylesheet" href="estilos.css">
</head>
<body>
    <div class="container">
        <aside class="sidebar">
            <h2>Navegación</h2>
            <ul>
                <li><a href="Barajas.html">Barajas</a></li>
                <li><a href="Ciegas.html">Ciegas</a></li>
                <li><a href="based de datos.php">Combos de la comunidad</a></li>
                <li><a href="Comodines.html">Comodines</a></li>
                <li><a href="Desafíos.html">Desafíos</a></li>
                <li><a href="Ediciones.html">Ediciones</a></li>
                <li><a href="Espectrales.html">Espectrales</a></li>
                <li><a href="Etiqueta.html">Etiqueta</a></li>
                <li><a href="Fichas.html">Fichas</a></li>
                <li><a href="index.html">Menu</a></li>
                <li><a href="Mejoras.html">Mejoras</a></li>
                <li><a href="Packs y Tienda.html">Packs y Tienda</a></li>
                <li><a href="Planeta.html">Planeta</a></li>
                <li><a href="Sellos.html">Sellos</a></li>
                <li><a href="Stikers.html">Stikers</a></li>
                <li><a href="Tarot.html">Tarot</a></li>
                <li><a href="Tickets.html">Tickets</a></li>
            </ul>
        </aside>

        <div class="main-content"> <div class="seccion-subir-combo">
                <h1>SUBE TU COMBO</h1>
                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                    <div class="campo">
                        <label for="nombre-usuario">Tu Nombre:</label>
                        <input type="text" id="nombre-usuario" name="nombre_usuario" placeholder="Ej: JokerMastro99" required>
                    </div>

                    <div class="campo">
                        <p>Elige hasta 5 Jokers:</p>
                        <div class="seleccion-jokers">
                            <?php foreach ($all_jokers as $joker): ?>
                                <div class="joker-opcion">
                                    <input type="checkbox" id="joker_<?php echo $joker['id']; ?>" name="joker_ids[]" value="<?php echo $joker['id']; ?>">
                                    <label for="joker_<?php echo $joker['id']; ?>">
                                        <img src="<?php echo htmlspecialchars($joker['ruta_imagen']); ?>" alt="<?php echo htmlspecialchars($joker['alt_text']); ?>">
                                    </label>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="campo">
                        <label for="comentario">Comentario:</label>
                        <textarea id="comentario" name="comentario" placeholder="Explica tu estrategia..."></textarea>
                    </div>

                    <button type="submit">SUBIR COMBO</button>
                </form>
            </div>

            <div class="seccion-combos-comunidad">
                <h1>COMBOS DE LA COMUNIDAD</h1>

                <?php if (empty($community_combos)): ?>
                    <p>Aún no hay combos en la comunidad. ¡Sé el primero en subir uno!</p>
                <?php else: ?>
                    <?php foreach ($community_combos as $combo): ?>
                        <div class="combo-card">
                            <h2>COMBO DE: <?php echo htmlspecialchars($combo['nombre_usuario']); ?></h2>
                            <div class="jokers-combo">
                                <?php for ($i = 0; $i < count($combo['joker_rutas_imagen']); $i++): ?>
                                    <img src="<?php echo htmlspecialchars($combo['joker_rutas_imagen'][$i]); ?>" alt="<?php echo htmlspecialchars($combo['joker_alt_texts'][$i]); ?>">
                                <?php endfor; ?>
                            </div>
                            <p>Comentario: <?php echo htmlspecialchars($combo['comentario']); ?></p>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>

            </div>
        </div>
    </div>
</body>
</html>