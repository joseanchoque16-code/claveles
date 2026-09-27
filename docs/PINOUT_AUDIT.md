# Revisión del pinout ESP32

El firmware define entradas escalonadas de tanque en GPIO 23, 27, 34 y 35. El
seeder actual asigna esos mismos GPIO a otros canales:

| GPIO | Entrada fija de tanque | Asignación actual que colisiona |
| --- | --- | --- |
| 23 | Nivel 25 % | DHT11 (`S_TEMP` y `S_HR`) |
| 27 | Nivel 50 % | Relé del extractor (`D_FAN`) |
| 34 | Nivel 75 % | Humedad del suelo (`S_HSUELO`) |
| 35 | Nivel 100 % | LDR (`S_LUZ`) |

Así el firmware no puede medir simultáneamente el nivel, el DHT11, el suelo, la
luz y el extractor de forma fiable. El código ahora detecta estos cruces al
sincronizarse, deshabilita los canales afectados y bloquea el riego si el nivel
no está disponible. Esto es un modo seguro temporal; no resuelve el cableado.

Antes de operar la placa, verificar físicamente cada cable y decidir un mapa
único de GPIO para el hardware instalado. Después actualizar tanto los pines de
`PIN_L25`/`PIN_L50`/`PIN_L75`/`PIN_L100` en `clavel.ino` como los `gpio_pin` de
`sensores` y `dispositivos` en Laravel. No cambiar el pinout en producción hasta
que coincida con el cableado real.
