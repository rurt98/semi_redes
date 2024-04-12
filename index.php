<!-- 
    Rafael Uriel Rodriguez Torres / 214761519
-->

<?php
    function parsearPrefijoIPv6($prefijo) {
        // Dividir en dirección y longitud de prefijo
        list($direccion_dada_str, $longitud_prefijo) = explode('/', $prefijo);
        return array('direccion' => $direccion_dada_str, 'longitud' => $longitud_prefijo);
    }
    
    function calcularRangoHexadecimal($direccion_dada_str, $longitud_prefijo) {
        // Analizar la dirección en una cadena binaria
        $direccion_dada_bin = inet_pton($direccion_dada_str);

        // Convertir la cadena binaria a una cadena con caracteres hexadecimales
        $direccion_dada_hex = bin2hex($direccion_dada_bin);
        
        // Sobrescribir la primera cadena de dirección para asegurar que la notación sea óptima
        $direccion_hex_primera = $direccion_dada_hex;
        $direccion_hex_ultima = $direccion_dada_hex;

        // Calcular el número de bits 'flexibles'
        $bits_flexibles = 128 - $longitud_prefijo;

        // Comenzamos al final de la cadena (que siempre tiene 32 caracteres de longitud)
        $pos = 31;
        while ($bits_flexibles > 0) {
            // Obtener los caracteres en esta posición
            $original_primero = substr($direccion_hex_primera, $pos, 1);
            $original_ultimo = substr($direccion_hex_ultima, $pos, 1);

            // Convertirlos a un entero
            $valor_original_primero = hexdec($original_primero);
            $valor_original_ultimo = hexdec($original_ultimo);

            // Primera dirección: calcular la máscara de subred. min() previene que la comparación sea negativa
            $mascara = 0xf << (min(4, $bits_flexibles));

            // Y el original contra su máscara
            $nuevo_valor_primero = $valor_original_primero & $mascara;

            // Última dirección: O con (2^bits_flexibles)-1, con bits_flexibles limitados a 4 a la vez
            $nuevo_valor_ultimo = $valor_original_ultimo | (pow(2, min(4, $bits_flexibles)) - 1);

            // Convertirlos de nuevo a caracteres hexadecimales
            $nuevo_primero = dechex($nuevo_valor_primero);
            $nuevo_ultimo = dechex($nuevo_valor_ultimo);

             // Y poner esos caracteres de vuelta en sus cadenas
            $direccion_hex_primera = substr_replace($direccion_hex_primera, $nuevo_primero, $pos, 1);
            $direccion_hex_ultima = substr_replace($direccion_hex_ultima, $nuevo_ultimo, $pos, 1);

            $bits_flexibles -= 4;
            $pos -= 1;
        }

        return array('primera' => $direccion_hex_primera, 'ultima' => $direccion_hex_ultima);
    }
    
    function convertirHexadecimalADireccion($direccion_hex_primera, $direccion_hex_ultima) {
        // Convertir las cadenas hexadecimales a una cadena binaria
        $direccion_bin_primera = hex2bin($direccion_hex_primera);
        $direccion_bin_ultima = hex2bin($direccion_hex_ultima);

        // Y crear una dirección IPv6 a partir de la cadena binaria
        $direccion_str_primera = inet_ntop($direccion_bin_primera);
        $direccion_str_ultima = inet_ntop($direccion_bin_ultima);

        return array('primera' => $direccion_str_primera, 'ultima' => $direccion_str_ultima);
    }
    
    function calcularRangoIPv6($prefijo) {
        $parsed_prefijo = parsearPrefijoIPv6($prefijo);
        $rangos_hex = calcularRangoHexadecimal($parsed_prefijo['direccion'], $parsed_prefijo['longitud']);
        $rangos_direccion = convertirHexadecimalADireccion($rangos_hex['primera'], $rangos_hex['ultima']);
        return "Prefijo: $prefijo\nPrimera: " . $rangos_direccion['primera'] . "\nÚltima: " . $rangos_direccion['ultima'];
    }

    function validarInputCIDR($inputCIDR) {
        // Verificar si $inputCIDR está configurado y si coincide con el formato esperado de una dirección IPv6 CIDR
        if (isset($inputCIDR) && preg_match('/^([0-9a-fA-F:.]+)\/([0-9]+)$/', $_POST['inputCIDR'], $matches)) {
            // Extraer la longitud del prefijo del CIDR
            $longitud_prefijo = intval($matches[2]);

            // Verificar si la longitud del prefijo es menor o igual a 128
            if ($longitud_prefijo <= 128) {
               return true;
            }

            echo "<script>alert('La longitud del prefijo debe ser menor o igual a 128.');</script>";
            return false;
        } 

        echo "<script>alert('Formato de dirección IP o máscara incorrecto.');</script>";
        return false;
    }

    if (isset($_POST['calcular'])) {
        $ipCIDR = $_POST['inputCIDR'];
        // Validamos la entrada
        $inputCorrecto = validarInputCIDR($ipCIDR);

        if (!$inputCorrecto) {
            $resultado = "";
        } else {
            $resultado = calcularRangoIPv6($ipCIDR);
        }
    }

    ?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Calculador de Rango de IP</title>
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
</head>

<body>

    <div class="container mt-5">
        <h1 class="mb-4">Calculador de Rango de IPv6</h1>

        <form method="post">
            <div class="form-group">
                <label for="inputCIDR">Ingrese la dirección IP:</label>
                <div class="input-group">
                    <input type="text" class="form-control" name="inputCIDR" id="inputCIDR" placeholder="Ejemplo: 2000:1210:70:5013::80/26"
                        required>
                    <div class="input-group-append">
                        <button type="submit" class="btn btn-primary" name="calcular">Calcular Rango</button>
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label for="resultado">Resultado:</label>
                <textarea class="form-control" id="resultado" name="resultado" rows="4" readonly><?php echo isset($resultado) ? $resultado : ''; ?></textarea>
            </div>
        </form>
    </div>


</body>

</html>
