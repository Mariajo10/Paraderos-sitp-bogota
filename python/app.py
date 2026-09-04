from flask import Flask, request, jsonify
import requests

app = Flask(__name__)

# API oficial de IDECA - Paraderos Zonales del SITP
API_URL = "https://serviciosgis.catastrobogota.gov.co/arcgis/rest/services/Mapa_Referencia/Mapa_Referencia/MapServer/8/query"


@app.route("/api/paraderos", methods=["GET"])
def consultar_paraderos():

    # Recibir parámetros enviados por PHP
    localidad = request.args.get("localidad", "").strip()
    nombre = request.args.get("nombre", "").strip()

    # Validar que se haya seleccionado una localidad
    if not localidad:
        return jsonify({
            "error": "La localidad es obligatoria"
        }), 400

    # Evitar problemas con comillas en la consulta
    localidad_segura = localidad.replace("'", "''")

    # Construir consulta para IDECA
    where = f"LOCALIDAD = '{localidad_segura}'"

    # Filtro opcional por nombre o dirección
    if nombre:
        nombre_seguro = nombre.replace("'", "''")

        where += (
            f" AND (NOMBRE LIKE '%{nombre_seguro}%' "
            f"OR DIRECCION_ LIKE '%{nombre_seguro}%')"
        )

    # Parámetros enviados a la API de IDECA
    parametros = {
        "where": where,
        "outFields": "CENEFA,NOMBRE,DIRECCION_,LOCALIDAD,LATITUD,LONGITUD",
        "returnGeometry": "false",
        "f": "geojson"
    }

    try:

        # Consultar la API de IDECA
        respuesta = requests.get(
            API_URL,
            params=parametros,
            timeout=15
        )

        respuesta.raise_for_status()

        # Convertir respuesta JSON
        datos = respuesta.json()

        # Obtener los paraderos
        features = datos.get("features", [])

        paraderos = []

        # Procesar cada paradero
        for feature in features:

            propiedades = feature.get("properties", {})

            paraderos.append({
                "nombre": propiedades.get("NOMBRE"),
                "direccion": propiedades.get("DIRECCION_"),
                "localidad": propiedades.get("LOCALIDAD"),
                "latitud": propiedades.get("LATITUD"),
                "longitud": propiedades.get("LONGITUD")
            })

        # Respuesta que recibirá PHP
        return jsonify({
            "localidad": localidad,
            "filtro": nombre,
            "total": len(paraderos),
            "paraderos": paraderos
        })

    except requests.exceptions.RequestException as error:

        return jsonify({
            "error": "No fue posible consultar la API de IDECA",
            "detalle": str(error)
        }), 500

    except ValueError:

        return jsonify({
            "error": "La API devolvió una respuesta que no es JSON válido"
        }), 500


# Iniciar servidor Python
if __name__ == "__main__":
    app.run(
        host="127.0.0.1",
        port=5000,
        debug=True
    )