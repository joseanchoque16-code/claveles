# Protocolo IoT Claveles

Este contrato implementa en Laravel el flujo de lecturas, sincronización y
órdenes con confirmación previsto en la documentación del invernadero. El ESP32
mantiene el control local y consulta la API para sincronizarse; no debe conectar
directamente con MySQL.

## Autenticación

Las rutas aceptan `X-DEVICE-KEY`; si `IOT_MODULE_UID` está configurado, este
cliente también debe enviar `X-MODULO-UID` con ese identificador. Para firmware
ya instalado, `X-API-KEY` continúa aceptándose como mecanismo transitorio de
compatibilidad. Configura `IOT_DEVICE_KEY` (o conserva `IOT_API_KEY`) en el
entorno del servidor; no guardes secretos en el repositorio.

## Rutas canónicas

Todas usan el prefijo `/api/iot/v1` y esperan JSON:

| Método y ruta | Función |
| --- | --- |
| `POST /lecturas` | Registrar lecturas y estados confirmados de actuadores |
| `GET /sync` | Obtener parámetros, sensores, actuadores, reglas y órdenes pendientes |
| `POST /ack` | Confirmar ejecución, rechazo o fallo de una orden |

Se conservan `/api/v1/lecturas` y `/api/v1/esp32/sync` para clientes antiguos.
También se conserva `/api/v1/commands/ack` para actualizar firmware por etapas.

El firmware incluido usa estas rutas compatibles y conserva el header
`X-API-KEY`. Para usar una URL HTTPS debe configurarse el certificado raíz
`ROOT_CA_CERT` en el archivo local `clavel/secrets.h`; el cliente falla cerrado
si se configura HTTPS sin un certificado de confianza.

### Envío de lecturas

```json
{
  "ts": "2026-09-26T15:30:00-04:00",
  "lecturas": [
    {"codigo": "S_TEMP", "valor": 19.4},
    {"codigo": "S_HSUELO", "valor": 62.0}
  ],
  "actuadores": [
    {"codigo": "D_RIEGO", "estado": 0, "origen": "automatico"}
  ]
}
```

Cada código de sensor debe coincidir con un sensor activo registrado. Cada
actuador puede incluir su código, estado real lógico (`0`/`1`) y origen
(`automatico`/`manual`). Laravel guarda una actuación solo cuando el estado
reportado cambia. Una lectura o un actuador no reconocido/inactivo se devuelve
en `ignoradas`.

### Órdenes pendientes

La respuesta de `GET /sync` incorpora `commands`. Cada orden incluye un UUID
estable `command_id`, el código del actuador, la acción (`on`/`off`), el estado
lógico esperado y su expiración. El ESP32 debe validar las interconexiones de
seguridad localmente antes de actuar. Si recibe otra vez el mismo UUID, debe
tratarlo como una retransmisión idempotente, no como una orden nueva.

### Confirmación ACK

Después de ejecutar, rechazar o detectar un fallo, el ESP32 envía:

```json
{
  "command_id": "UUID-recibido-en-sync",
  "resultado": "executed",
  "estado_actual": 1,
  "mensaje": "Salida aplicada"
}
```

`resultado` admite `executed`, `rejected` o `failed`; `estado_actual` es
obligatorio para `executed`. El servidor actualiza el
estado confirmado del actuador y crea una actuación histórica solo al recibir
`executed`. Repetir un ACK devuelve el resultado ya guardado sin duplicar la
actuación. Las órdenes vencen a los cinco minutos y una orden nueva reemplaza
cualquier orden anterior pendiente para ese mismo actuador.

## Límites de esta etapa

Laravel y el firmware incluido implementan la cola, la aplicación idempotente de
órdenes y el ACK. Aún se requiere probarlos juntos en la placa y verificar la
autenticación, conectividad y respuesta física. La tesis describe cuatro zonas de
humedad de suelo, temperaturas de ida/retorno y actuadores hidráulicos adicionales;
los códigos y pines de esos equipos se deben acordar antes de agregarlos a la
configuración activa. No se deben sembrar sensores de hardware que aún no existan
físicamente.

El mapa actual de pines tiene cruces con las entradas fijas de nivel de tanque;
consulta `docs/PINOUT_AUDIT.md` antes de energizar la etapa de potencia.
