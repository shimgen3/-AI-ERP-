
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Aplicación Web con Procesamiento de Texto</title>
    <link rel="stylesheet" href="appcss.css">
</head>
<body>
    <header>
        <h1>Aplicación de Procesamiento de Texto</h1>
    </header>
    <main>
        <div class="container">
            <img src="appimgcerebro.png" alt="Imagen">
            <p>Esta aplicaciòn clasifica la peticion de un usuario en tres categorias para el manejo de base de datos<Br></p>
            <form method="post">
                <textarea name="inputText" id="inputText" placeholder="Ingresa tu texto aquí"></textarea>
                <button type="submit" id="processButton" name="processButton">Procesar Texto</button>
            </form>
            <div id="resultBox">
            <?php
            if (isset($_POST["processButton"])) {
                $input = $_POST["inputText"];
                $api_key = 'YOUR_API_KEY'; // Reemplaza esto con tu clave de API de OpenAI
                $endpoint = 'https://api.openai.com/v1/engines/text-davinci-003/completions';
               
                $data = array(
                    'prompt' => "Eres un asistente automatizado encargado de clasificar las solicitudes de los usuarios en tres categorías distintas:\n\nCAMBIO: Esta categoría se aplica cuando el usuario desea realizar modificaciones en la información almacenada en la base de datos actual.\n\nCONSUMO: Si el cliente busca obtener información acerca del historial de consumo de un producto o insumo presente en la base de datos, como indicadores clave de rendimiento u otros parámetros derivados de los datos históricos.\n\nDATOS: En situaciones donde el cliente necesita acceder a información detallada almacenada en la base de datos, en forma de una representación tabular u otra forma similar.\n\nTomemos como ejemplo una base de datos que contiene información sobre productos e insumos utilizados en una cocina:\n\nUsuario: Agrega un huevo.\nBot: CAMBIO\nUsuario: ¿Cuántos huevos se han consumido en las últimas dos semanas?\nBot: CONSUMO\nUsuario: Muéstrame el inventario completo de hoy.\nBot: DATOS\n\nEstos ejemplos pueden ser extrapolados y adaptados para cualquier contexto específico. \nUsuario: ". $input . "\nBot:",
                    'max_tokens' => 150,
                    'temperature' => 1
                );

                $ch = curl_init($endpoint);

                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                    'Content-Type: application/json',
                    'Authorization: Bearer ' . $api_key
                ));
                
                $response = curl_exec($ch);
                curl_close($ch);
                
                $fecha = date('Y-m-d H:i:s');

                if ($response) {
                    $decoded_response = json_decode($response, true);
                    if ($decoded_response && isset($decoded_response['choices'][0]['text'])) {
                        $completion = $decoded_response['choices'][0]['text'];
                        
                        if ($completion === " CONSUMO") {
                            //funcion para personalizar la solicitud
                            $data = array(
                                'prompt' => "Eres un asistente automatizado especializado en la creación de sentencias SQL que satisfacen las necesidades de los usuarios al gestionar el inventario de productos. En este caso, se requiere informacion del consumo historico del stock de un producto en una tabla llamada 'stock'. Esta tabla está compuesta por las columnas (item, cantidad). \n\nTen en cuenta que los elementos que se encuentran entre corchetes ([pan]) representan la lista de los productos ya existentes en la base de datos.\n\nUsuario: Cuantos panes me comi esta semana?\n\nBot: SELECT SUM(cantidad) FROM stock WHERE item IN ([pan]) AND fecha >= DATE_SUB(CURDATE(), INTERVAL 7 DAY);\n\nUsuario: $input \n\nBot:",
                                'max_tokens' => 150,
                                'temperature' => 1
                            );
            
                            $ch = curl_init($endpoint);
            
                            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
                            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
                            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                                'Content-Type: application/json',
                                'Authorization: Bearer ' . $api_key
                            ));
            
                            $response = curl_exec($ch);
                            curl_close($ch);
                            $decoded_response = json_decode($response, true);
                            if ($decoded_response && isset($decoded_response['choices'][0]['text'])) {
                                $completion = $decoded_response['choices'][0]['text'];
                                try {
                                    $dsn = "mysql:host=YOU_HOST;dbname=YOUR_DATA_BASE_NAME";
                                    $usuario = "USER";
                                    $contrasena = "PASSWORD";
                                
                                    $conexion = new PDO($dsn, $usuario, $contrasena);
                                
                                    // Configurar PDO para manejar excepciones en caso de errores
                                    $conexion->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                                
                                    $sentencia = $completion; // Tu sentencia SQL completa
                                
                                    $resultados = $conexion->query($sentencia);
                                
                                    // Obtener resultados si es una consulta SELECT
                                    $resultados = $resultados->fetchAll(PDO::FETCH_ASSOC);
                                
                                    // Hacer algo con los resultados...
                                
                                } catch (PDOException $e) {
                                    echo "Error: " . $e->getMessage();
                                }
                                echo $sentencia;
                            }
                            

                            //
                        } elseif ($completion === " CAMBIO") {
                            //funcion para personalizar la solicitud
                            $data = array(
                                'prompt' => "Eres un asistente automatizado especializado en la creación de sentencias SQL que satisfacen las necesidades de los usuarios al gestionar el inventario de productos. En este caso, se requiere la edición del stock de un producto en una tabla llamada 'stock'. Esta tabla está compuesta por las columnas (item, cantidad). Además, es necesario generar una sentencia para registrar la transacción en la tabla 'flujo', la cual posee las columnas (item, q, fecha, entrada). En esta tabla, el item se refiere al nombre del producto, q representa la cantidad involucrada en la transacción, fecha debe ser sustituido por el valor ".'$fecha'." presente en el programa donde se utilizará la sentencia y, finalmente, entrada es un valor booleano que indica si el producto entró o salió del inventario.\n\nTen en cuenta que los elementos que se encuentran entre corchetes ([pan]) representan la lista de los productos ya existentes en la base de datos.\n\nUsuario: Agrega un pan.\n\nBot: INSERT INTO stock (item, cantidad) VALUES ('pan', 1); INSERT INTO flujo (item, q, fecha, entrada) VALUES ('pan', 1, ".'$fecha'.", True);\n\nUsuario:  $input\n\nBot:",
                                'max_tokens' => 150,
                                'temperature' => 1
                            );
            
                            $ch = curl_init($endpoint);
            
                            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
                            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
                            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                                'Content-Type: application/json',
                                'Authorization: Bearer ' . $api_key
                            ));
            
                            $response = curl_exec($ch);
                            curl_close($ch);
                            $decoded_response = json_decode($response, true);
                            if ($decoded_response && isset($decoded_response['choices'][0]['text'])) {
                                $completion = $decoded_response['choices'][0]['text'];
                                try {
                                    $dsn = "mysql:host=YOU_HOST;dbname=YOUR_DATA_BASE_NAME";
                                    $usuario = "USER";
                                    $contrasena = "PASSWORD";
                                
                                    $conexion = new PDO($dsn, $usuario, $contrasena);
                                
                                    // Configurar PDO para manejar excepciones en caso de errores
                                    $conexion->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                                
                                    $sentencia = $completion; // Tu sentencia SQL completa
                                
                                    $resultados = $conexion->query($sentencia);
                                
                                    // Obtener resultados si es una consulta SELECT
                                    $resultados = $resultados->fetchAll(PDO::FETCH_ASSOC);
                                
                                    // Hacer algo con los resultados...
                                
                                } catch (PDOException $e) {
                                    echo "Error: " . $e->getMessage();
                                }
                                echo $sentencia;
                            }

                            //
                        } elseif ($completion === " DATOS") {
                            //funcion para personalizar la solicitud
                            $data = array(
                                'prompt' => "Eres un asistente automatizado especializado en la creación de sentencias SQL que satisfacen las necesidades de los usuarios al gestionar el inventario de productos. En este caso, se requiere la extracción de datos del stock de un producto en una tabla llamada 'stock'. Esta tabla está compuesta por las columnas (item, cantidad, codigo). Además, hay una tabla 'flujo', la cual posee las columnas (item, q, fecha, entrada). En esta tabla, el item se refiere al nombre del producto, q representa la cantidad involucrada en la transacción, fecha debe ser sustituido por el valor ".'$fecha'." presente en el programa donde se utilizará la sentencia y, finalmente, entrada es un valor booleano que indica si el producto entró o salió del inventario.\n\nTen en cuenta que los elementos que se encuentran entre corchetes ([pan]) representan la lista de los productos ya existentes en la base de datos.\n\nUsuario: dame una tabla con todos los flujos de esta semana \n\nbot: SELECT * FROM flujo WHERE fecha >= ".'$fecha'.";\nUsuario: $input\nBot:",
                                'max_tokens' => 150,
                                'temperature' => 1
                            );
            
                            $ch = curl_init($endpoint);
            
                            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
                            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
                            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                                'Content-Type: application/json',
                                'Authorization: Bearer ' . $api_key
                            ));
            
                            $response = curl_exec($ch);
                            curl_close($ch);
                            $decoded_response = json_decode($response, true);
                            if ($decoded_response && isset($decoded_response['choices'][0]['text'])) {
                                $completion = $decoded_response['choices'][0]['text'];
                                try {
                                    $dsn = "mysql:host=YOU_HOST;dbname=YOUR_DATA_BASE_NAME";
                                    $usuario = "USER";
                                    $contrasena = "PASSWORD";
                                
                                    $conexion = new PDO($dsn, $usuario, $contrasena);
                                
                                    // Configurar PDO para manejar excepciones en caso de errores
                                    $conexion->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                                
                                    $sentencia = $completion; // Tu sentencia SQL completa
                                
                                    $resultados = $conexion->query($sentencia);
                                
                                    // Obtener resultados si es una consulta SELECT
                                    $resultados = $resultados->fetchAll(PDO::FETCH_ASSOC);
                                
                                    echo $resultados;
                                
                                } catch (PDOException $e) {
                                    echo "Error: " . $e->getMessage();
                                }
                                echo $sentencia;
                            }

                            //
                        } else {
                            echo "Mensaje no reconocido"; // Si no coincide con ninguna categoría
                        }
                    } else {
                        echo 'No se pudo obtener una respuesta de la API.';
                    }
                } else {
                    echo 'Hubo un error al realizar la solicitud a la API.';
                }
            }
            ?>

            </div>
        </div>
    </main>
</body>
</html>
