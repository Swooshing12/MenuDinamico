<?php
if (isset($_GET['cedula'])) {
    $cedula = $_GET['cedula'];
    $url = "https://sifae.agrocalidad.gob.ec/SIFAEBack/index.php?ruta=datos_demograficos/$cedula";

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

    $response = curl_exec($ch);
    curl_close($ch);

    echo $response;
}
?>
