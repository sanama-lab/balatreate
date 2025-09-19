<?php
// Configuración de la conexión a la base de datos
$servername = "localhost";
$username = "tu_usuario"; // Reemplaza con tu usuario de MySQL
$password = "tu_contraseña"; // Reemplaza con tu contraseña
$dbname = "balatro_db";

// Crear conexión
$conn = new mysqli($servername, $username, $password, $dbname);

// Verificar la conexión
if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}

// Inicializar variables para mostrar en el HTML
$comodin_nombre = "";
$comodin_descripcion = "";
$comodin_rareza = "";
$comodin_precio = "";
$comodin_imagen = "";
$comodin_alt_text = "";
$comodines_disponibles = [];

// Obtener la lista de todos los comodines para el menú desplegable
$sql_lista = "SELECT id, nombre FROM comodines ORDER BY nombre ASC";
$result_lista = $conn->query($sql_lista);
if ($result_lista->num_rows > 0) {
    while($row = $result_lista->fetch_assoc()) {
        $comodines_disponibles[] = $row;
    }
}

// Procesar el formulario cuando se selecciona un comodín
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['comodin_id'])) {
    $comodin_id = $_POST['comodin_id'];

    // Consulta para obtener la información completa del comodín seleccionado
    $sql_comodin = "SELECT c.nombre, c.descripcion, c.precio, c.rareza, i.ruta_imagen, i.alt_text
                    FROM comodines c
                    JOIN imagenes_comodines i ON c.id = i.comodin_id
                    WHERE c.id = ?";
    
    $stmt = $conn->prepare($sql_comodin);
    $stmt->bind_param("i", $comodin_id);
    $stmt->execute();
    $result_comodin = $stmt->get_result();
    
    if ($result_comodin->num_rows > 0) {
        $row = $result_comodin->fetch_assoc();
        $comodin_nombre = htmlspecialchars($row['nombre']);
        $comodin_descripcion = htmlspecialchars($row['descripcion']);
        $comodin_rareza = htmlspecialchars($row['rareza']);
        $comodin_precio = htmlspecialchars($row['precio']);
        $comodin_imagen = htmlspecialchars($row['ruta_imagen']);
        $comodin_alt_text = htmlspecialchars($row['alt_text']);
    }
    $stmt->close();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Selecciona un Comodín</title>
    <style>
        body { font-family: sans-serif; }
        .container { max-width: 800px; margin: auto; padding: 20px; }
        .comodin-selector { text-align: center; margin-bottom: 20px; }
        .comodin-info { text-align: center; border: 1px solid #ccc; padding: 20px; border-radius: 8px; }
        .comodin-info img { max-width: 100%; height: auto; display: block; margin: 20px auto; }
        .espacios { display: flex; justify-content: space-around; margin: 20px 0; }
        .espacio { width: 50px; height: 50px; border: 1px solid #ccc; display: flex; justify-content: center; align-items: center; }
        .comments-section { margin-top: 40px; }
    </style>
</head>
<body>

<div class="container">
    <div class="comodin-selector">
        <h1>Selecciona un Comodín</h1>
        <form method="POST" action="">
            <select name="comodin_id" onchange="this.form.submit()">
                <option value="">-- Elige un comodín --</option>
                <?php foreach ($comodines_disponibles as $comodin): ?>
                    <option value="<?php echo $comodin['id']; ?>" <?php echo (isset($_POST['comodin_id']) && $_POST['comodin_id'] == $comodin['id']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($comodin['nombre']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </form>
    </div>

    <?php if ($comodin_nombre): ?>
    <div class="comodin-info">
        <h2><?php echo $comodin_nombre; ?></h2>
        <p><strong>Descripción:</strong> <?php echo $comodin_descripcion; ?></p>
        <p><strong>Rareza:</strong> <?php echo $comodin_rareza; ?></p>
        <p><strong>Precio:</strong> <?php echo $comodin_precio; ?></p>
        
        <img src="<?php echo $comodin_imagen; ?>" alt="<?php echo $comodin_alt_text; ?>">
    </div>
    <?php endif; ?>

    <hr>

    <div class="espacios">
        <div class="espacio">1</div>
        <div class="espacio">2</div>
        <div class="espacio">3</div>
        <div class="espacio">4</div>
        <div class="espacio">5</div>
    </div>

    <div class="comments-section">
        <h3>Deja tu comentario</h3>
        <textarea style="width: 100%;" rows="5" placeholder="Escribe tu mensaje aquí..."></textarea>
    </div>
</div>

</body>
</html>