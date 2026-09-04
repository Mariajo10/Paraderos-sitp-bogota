<?php

// ============================================================
// CONFIGURACIÓN DEL MÓDULO PYTHON
// ============================================================

// PHP se comunica con Python mediante este endpoint Flask.
$urlPython = "http://127.0.0.1:5000/api/paraderos";


// ============================================================
// VARIABLES
// ============================================================

$resultados = [];

$mensaje = "";

$consultaRealizada = false;


// ============================================================
// LISTA DE LOCALIDADES
// ============================================================

$localidades = [
    "Antonio Nariño",
    "Barrios Unidos",
    "Bosa",
    "Chapinero",
    "Ciudad Bolívar",
    "Engativá",
    "Fontibón",
    "Kennedy",
    "La Candelaria",
    "Los Mártires",
    "Puente Aranda",
    "Rafael Uribe Uribe",
    "San Cristóbal",
    "Santa Fe",
    "Suba",
    "Teusaquillo",
    "Tunjuelito",
    "Usaquén",
    "Usme"
];


// ============================================================
// DATOS DEL FORMULARIO
// ============================================================

$localidad = isset($_GET["localidad"])
    ? trim($_GET["localidad"])
    : "";

$nombreParadero = isset($_GET["nombre"])
    ? trim($_GET["nombre"])
    : "";


// ============================================================
// PROCESAMIENTO DEL FORMULARIO
// ============================================================

if ($_SERVER["REQUEST_METHOD"] === "GET" && isset($_GET["localidad"])) {

    $consultaRealizada = true;


    // ========================================================
    // VALIDACIÓN DE LA LOCALIDAD
    // ========================================================

    if ($localidad === "") {

        $mensaje = "Por favor selecciona una localidad.";

    } elseif (!in_array($localidad, $localidades, true)) {

        $mensaje = "La localidad seleccionada no es válida.";

    } else {


        // ====================================================
        // PREPARAR DATOS PARA PYTHON
        // ====================================================

        /*
         * PHP envía a Python:
         *
         * localidad
         * nombre (opcional)
         *
         * Python será el encargado de consultar IDECA.
         */

        $parametrosPython = [
            "localidad" => $localidad
        ];


        // Si existe un filtro de nombre,
        // también se envía a Python.
        if ($nombreParadero !== "") {

            $parametrosPython["nombre"] = $nombreParadero;
        }


        // ====================================================
        // CONSTRUIR URL DE PYTHON
        // ====================================================

        $urlConsulta =
            $urlPython . "?" . http_build_query($parametrosPython);


        // ====================================================
        // CONSULTAR PYTHON
        // ====================================================

        /*
         * PHP realiza una petición HTTP al módulo
         * desarrollado con Python + Flask.
         */

        $respuesta = @file_get_contents($urlConsulta);


        // ====================================================
        // VERIFICAR RESPUESTA
        // ====================================================

        if ($respuesta === false) {

            $mensaje =
                "No fue posible conectarse con el módulo Python. "
                . "Verifica que Flask esté ejecutándose en el puerto 5000.";

        } else {


            // =================================================
            // CONVERTIR JSON DE PYTHON A ARRAY PHP
            // =================================================

            $datos = json_decode($respuesta, true);


            // Verificar que el JSON sea válido.
            if ($datos === null) {

                $mensaje =
                    "El módulo Python devolvió una respuesta no válida.";

            } elseif (isset($datos["error"])) {

                $mensaje =
                    "Python informó un error: "
                    . ($datos["error"] ?? "Error desconocido.");

            } elseif (!isset($datos["paraderos"])) {

                $mensaje =
                    "El módulo Python no devolvió el listado de paraderos.";

            } else {


                // =============================================
                // GUARDAR LOS RESULTADOS
                // =============================================

                /*
                 * Python ya procesó la información de IDECA
                 * y entrega directamente el arreglo "paraderos".
                 */

                $resultados = $datos["paraderos"];


                // Verificar si hubo resultados.
                if (count($resultados) === 0) {

                    $mensaje =
                        "No se encontraron paraderos con los criterios seleccionados.";
                }
            }
        }
    }
}

?>

<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Paraderos SITP de Bogotá</title>

    <style>

        /* VARIABLES DE DISEÑO */

        :root {
            --azul-principal: #0756a6;
            --azul-oscuro: #063f7c;
            --azul-claro: #eaf4ff;
            --azul-borde: #cfe4fa;
            --blanco: #ffffff;
            --gris-fondo: #f4f8fc;
            --gris-texto: #425466;
            --gris-borde: #d8e1ea;

        }

        /* CONFIGURACIÓN GENERAL */

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family:
                Arial,
                Helvetica,
                sans-serif;
            background:
                linear-gradient(
                    180deg,
                    #eef5fb 0%,
                    #f8fbfe 100%
                );
            color: #172b4d;

        }

        /* ENCABEZADO */

        .encabezado {
            background:
                linear-gradient(
                    120deg,
                    #06468d,
                    #075fb5
                );
            color: white;
            padding: 28px 4%;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 30px;
            box-shadow:
                0 4px 15px rgba(0,0,0,0.12);

        }

        .marca {
            display: flex;
            align-items: center;
            gap: 20px;

        }

        /* Icono de bus */

        .logo-bus {
            width: 72px;
            height: 72px;
            border: 3px solid white;
            border-radius: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 42px;
            background:
                rgba(255,255,255,0.08);

        }


        .titulo {
            margin: 0;
            font-size: 38px;
            font-weight: 700;
            letter-spacing: -1px;
        }


        .subtitulo {
            margin: 8px 0 0;
            font-size: 17px;
            opacity: 0.95;

        }

        /* MARCAS SITP Y BOGOTÁ */

        .marcas {
            display: flex;
            align-items: center;
            gap: 25px;
            white-space: nowrap;
        }


        .marca-sitp {
            font-size: 38px;
            font-weight: bold;
            letter-spacing: -2px;

        }


        .separador {
            width: 2px;
            height: 55px;
            background:
                rgba(255,255,255,0.55);

        }


        .marca-bogota {
            font-size: 27px;
            font-weight: bold;
            letter-spacing: 1px;

        }


        .estrella {
            color: #ffd21c;
            font-size: 25px;

        }

        /* CONTENEDOR PRINCIPAL */

        .contenedor {
            width: 94%;
            max-width: 1500px;
            margin: 28px auto 60px;
        }

        /* BARRA DE INFORMACIÓN */

        .informacion {
            background:
                #e9f5ff;
            border:
                1px solid #b9dcfa;
            border-radius: 14px;
            padding: 16px 22px;
            margin-bottom: 26px;
            display: flex;
            align-items: center;
            gap: 15px;
            color: #143b6b;
            font-size: 16px;
        }

        .icono-info {
            width: 34px;
            height: 34px;
            border-radius: 50%;
            background:
                #1477df;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 20px;
        }

        .informacion strong {
            color:
                #0756a6;

        }

        /* TARJETAS */

        .tarjeta {
            background: white;
            border:
                1px solid #e1eaf2;
            border-radius: 18px;
            padding: 28px;
            margin-bottom: 28px;
            box-shadow:
                0 5px 20px
                rgba(30,70,110,0.08);

        }


        /* TÍTULOS DE SECCIÓN */

        .titulo-seccion {
            display: flex;
            align-items: center;
            gap: 12px;
            color:
                #083e7c;
            margin: 0 0 25px;
            font-size: 30px;

        }


        .icono-seccion {
            font-size: 36px;

        }

        /* FORMULARIO */

        .formulario-grid {
            display: grid;
            grid-template-columns:
                1fr 1.25fr auto;
            gap: 25px;
            align-items: end;
        }


        .campo label {
            display: block;
            font-weight: bold;
            color:
                #123e78;
            margin-bottom: 9px;
            font-size: 16px;

        }


        .campo input,
        .campo select {
            width: 100%;
            height: 50px;
            padding: 0 16px;
            border:
                2px solid #cbd9e8;
            border-radius: 10px;
            font-size: 16px;
            color: #27364a;
            background: white;
            outline: none;
            transition:
                border 0.2s,
                box-shadow 0.2s;

        }


        .campo input:focus,
        .campo select:focus {
            border-color:
                #1477df;
            box-shadow:
                0 0 0 3px
                rgba(20,119,223,0.12);

        }

        /* BOTÓN */

        .boton {
            height: 50px;
            padding: 0 30px;
            border: none;
            border-radius: 10px;
            background:
                linear-gradient(
                    135deg,
                    #075bb3,
                    #064b9a
                );
            color: white;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            box-shadow:
                0 4px 10px
                rgba(0,83,170,0.25);
            transition:
                transform 0.2s,
                box-shadow 0.2s;

        }


        .boton:hover {
            transform:
                translateY(-2px);
            box-shadow:
                0 7px 15px
                rgba(0,83,170,0.30);

        }


        /* RESULTADOS */

        .encabezado-resultados {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;

        }


        .titulo-resultados {
            margin: 0;
            font-size: 31px;
            color:
                #073e7e;

        }


        .contador {
            display: inline-flex;
            align-items: center;
            gap: 12px;
            margin-top: 8px;
            padding: 10px 15px;
            background:
                #edf6ff;
            border-radius: 10px;
            color:
                #34506d;
            font-size: 17px;

        }


        .contador strong {
            color:
                #0756a6;

        }


        .numero {
            background:
                #dcecff;
            color:
                #0756a6;
            padding: 5px 12px;
            border-radius: 8px;
            font-weight: bold;

        }


        .filtro-activo {
            background:
                #e5f1ff;
            color:
                #0756a6;
            border-radius: 10px;
            padding: 12px 18px;
            font-size: 14px;

        }

        /* TABLA */

        .tabla-contenedor {
            margin-top: 20px;
            overflow-x: auto;
            border-radius: 12px;
            border:
                1px solid #d8e5f1;

        }


        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 850px;
        }


        th {

            background:
                linear-gradient(
                    90deg,
                    #0756a6,
                    #06468c
                );

            color: white;
            padding: 15px 18px;
            text-align: left;
            font-size: 16px;

        }


        td {
            padding: 14px 18px;
            border-bottom:
                1px solid #e1e9f1;
            font-size: 15px;
        }


        tbody tr:nth-child(even) {
            background:
                #f3f8fd;
        }


        tbody tr:hover {
            background:
                #e5f2ff;
        }


        .nombre-paradero {
            font-weight: bold;
            color:
                #172b4d;

        }

        .icono-bus-tabla {
            margin-right: 8px;
            color:
                #0756a6;

        }


        .coordenada {

            font-family:
                Consolas,
                monospace;
            color:
                #3c536b;

        }

        /* MENSAJES */

        .mensaje {
            background:
                #fff6df;
            border:
                1px solid #f1d48a;
            color:
                #705300;
            border-radius: 12px;
            padding: 17px 20px;
            margin-bottom: 25px;

        }

        /* PIE DE TABLA */

        .nota {
            background:
                #eaf5ff;
            border-top:
                1px solid #c8e1f8;
            padding: 13px 18px;
            color:
                #0756a6;
            font-size: 14px;

        }

        /* CUANDO NO HAY RESULTADOS */

        .sin-resultados {
            text-align: center;
            padding: 40px 20px;
            color:
                #64748b;

        }

        .sin-resultados-icono {
            font-size: 45px;
            margin-bottom: 10px;

        }

        /* RESPONSIVE */

        @media (max-width: 1000px) {

            .encabezado {
                flex-direction: column;
                align-items: flex-start;

            }

            .marcas {
                align-self: flex-end;

            }

            .formulario-grid {
                grid-template-columns: 1fr;

            }

            .boton {
                width: 100%;

            }

        }


        @media (max-width: 650px) {

            .encabezado {
                padding: 22px;

            }

            .titulo {
                font-size: 28px;

            }

            .subtitulo {
                font-size: 14px;

            }

            .logo-bus {
                width: 58px;
                height: 58px;
                font-size: 32px;

            }

            .marca-sitp {
                font-size: 28px;

            }

            .marca-bogota {
                font-size: 21px;

            }

            .tarjeta {
                padding: 20px;

            }

            .titulo-seccion,
            .titulo-resultados {
                font-size: 25px;

            }

        }

    </style>

</head>

<body>

<!--  ENCABEZADO -->

<header class="encabezado">

    <div class="marca">

        <div class="logo-bus">
            🚌
        </div>

        <div>

            <h1 class="titulo">
                Paraderos SITP de Bogotá
            </h1>

            <p class="subtitulo">
                Consulta los paraderos zonales del Sistema Integrado
                de Transporte Público (SITP)
            </p>

        </div>

    </div>


    <div class="marcas">

        <div class="marca-sitp">
            SITP
        </div>

        <div class="separador"></div>

        <div class="marca-bogota">
            <span class="estrella">★</span>
            BOGOTÁ
        </div>

    </div>

</header>

<!--  CONTENIDO PRINCIPAL -->

<main class="contenedor">


    <!-- INFORMACIÓN -->

    <div class="informacion">

        <div class="icono-info">
            i
        </div>

        <div>

            <strong>Información:</strong>

            Los datos provienen del portal
            Datos Abiertos Bogotá / IDECA y se muestran
            con fines informativos.

        </div>

    </div>

    <!-- FORMULARIO -->

    <section class="tarjeta">

        <h2 class="titulo-seccion">

            <span class="icono-seccion">
                📍
            </span>

            Selecciona una localidad

        </h2>

        <form method="GET" action="">

            <div class="formulario-grid">

                <!-- LOCALIDAD -->

                <div class="campo">

                    <label for="localidad">
                        📍 Localidad:
                    </label>

                    <select
                        name="localidad"
                        id="localidad"
                        required
                    >

                        <option value="">
                            Selecciona una localidad
                        </option>


                        <?php foreach ($localidades as $nombreLocalidad): ?>

                            <option
                                value="<?= htmlspecialchars($nombreLocalidad) ?>"
                                <?= ($localidad === $nombreLocalidad)
                                    ? "selected"
                                    : "" ?>
                            >

                                <?= htmlspecialchars($nombreLocalidad) ?>

                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>

                <!-- FILTRO POR NOMBRE -->

                <div class="campo">

                    <label for="nombre">

                        🔎 Filtrar por nombre del paradero:

                    </label>

                    <input
                        type="text"
                        name="nombre"
                        id="nombre"
                        value="<?= htmlspecialchars($nombreParadero) ?>"
                        placeholder="Ejemplo: Avenida, Calle 63..."
                    >

                </div>

                <!-- BOTÓN -->

                <button
                    type="submit"
                    class="boton"
                >

                    🔍
                    Consultar paraderos

                </button>

            </div>

        </form>

    </section>

    <!--  MENSAJE -->

    <?php if ($mensaje !== ""): ?>

        <div class="mensaje">

            ⚠️

            <?= htmlspecialchars($mensaje) ?>

        </div>

    <?php endif; ?>



    <!--  RESULTADOS -->

    <?php if (count($resultados) > 0): ?>


        <section class="tarjeta">


            <div class="encabezado-resultados">


                <div>

                    <h2 class="titulo-resultados">

                        🚌 Paraderos encontrados

                    </h2>


                    <div class="contador">

                        📍 Localidad:

                        <strong>
                            <?= htmlspecialchars($localidad) ?>
                        </strong>

                        <span>|</span>

                        Resultados:

                        <span class="numero">
                            <?= count($resultados) ?>
                        </span>

                    </div>

                </div>


                <div class="filtro-activo">

                    🔎

                    <?php if ($nombreParadero !== ""): ?>

                        Filtro:
                        <strong>
                            <?= htmlspecialchars($nombreParadero) ?>
                        </strong>

                    <?php else: ?>

                        Mostrando todos los resultados

                    <?php endif; ?>

                </div>


            </div>



            <!-- TABLA -->

            <div class="tabla-contenedor">

                <table>

                    <thead>

                        <tr>

                            <th>
                                Nombre del paradero
                            </th>

                            <th>
                                Dirección / Ubicación
                            </th>

                            <th>
                                Localidad
                            </th>

                            <th>
                                Latitud
                            </th>

                            <th>
                                Longitud
                            </th>

                        </tr>

                    </thead>


                    <tbody>


                        <?php foreach ($resultados as $paradero): ?>


                            <?php
                             // Python ya entrega los datos procesados directamente.
                                $atributos = $paradero;

                            ?>


                            <tr>


                                <td class="nombre-paradero">

                                    <span class="icono-bus-tabla">
                                        🚌
                                    </span>

                                    <?= htmlspecialchars(
                                        $atributos["nombre"]
                                        ?? "Sin información"
                                    ) ?>

                                </td>


                                <td>

                                    <?= htmlspecialchars(
                                        $atributos["direccion"]
                                        ?? "Sin información"
                                    ) ?>

                                </td>


                                <td>

                                    <?= htmlspecialchars(
                                        $atributos["localidad"]
                                        ?? "Sin información"
                                    ) ?>

                                </td>


                                <td class="coordenada">

                                    <?= htmlspecialchars(
                                        $atributos["latitud"]
                                        ?? "Sin información"
                                    ) ?>

                                </td>


                                <td class="coordenada">

                                    <?= htmlspecialchars(
                                        $atributos["longitud"]
                                        ?? "Sin información"
                                    ) ?>

                                </td>


                            </tr>


                        <?php endforeach; ?>


                    </tbody>

                </table>


                <div class="nota">

                    ℹ️

                    Los datos mostrados corresponden a los
                    paraderos zonales del SITP en la localidad
                    seleccionada.

                </div>

            </div>

        </section>


    <?php elseif ($consultaRealizada && $mensaje === ""): ?>


        <!-- SIN RESULTADOS -->

        <section class="tarjeta">

            <div class="sin-resultados">

                <div class="sin-resultados-icono">
                    🔎
                </div>

                <h2>
                    No encontramos paraderos
                </h2>

                <p>
                    No existen paraderos que coincidan con
                    los filtros seleccionados.
                </p>

            </div>

        </section>


    <?php endif; ?>


</main>


</body>

</html>