#include <WiFi.h>
#include <HTTPClient.h>
#include <ArduinoJson.h>
#include <Preferences.h>
#include <time.h>
#include <DHT.h>
#include <stdlib.h>
#include <string.h>
#include <WiFiClient.h>
#include <WiFiClientSecure.h>

#include "secrets.h"

String URL_SYNC     = String(BASE_URL) + "/api/v1/esp32/sync";
String URL_LECTURAS = String(BASE_URL) + "/api/v1/lecturas";
String URL_ACK      = String(BASE_URL) + "/api/v1/commands/ack";

// ====================== NTP ======================
const char* NTP1 = "pool.ntp.org";
const char* NTP2 = "time.google.com";
const long GMT_OFFSET_SEC = -4 * 3600;
const int  DAYLIGHT_OFFSET_SEC = 0;
bool timeReady = false;
bool pinMapWarningLogged = false;

// ====================== PINES FIJOS NIVEL ======================
// Pines físicos actuales del sensor escalonado de nivel. Deben ser distintos
// de sensores y relés configurados en Laravel; validarHardwarePinMap() desactiva
// los canales en conflicto hasta corregir el cableado/configuración.
#define PIN_L25   23
#define PIN_L50   27
#define PIN_L75   34
#define PIN_L100  35

// ====================== LIMITES ======================
#define MAX_DEVS       12
#define MAX_RULES      16
#define MAX_SENSORS    16

// ====================== MODELOS ======================
struct Device {
  String codigo;
  int gpio;
  bool invertido;
  int desired;              // 0/1 desde Laravel
  int current;              // -1 desconocido, 0 apagado, 1 encendido
  unsigned long lastChangeMs;
};

struct Rule {
  int id;
  String sensorCodigo;
  String deviceCodigo;
  float onTh;
  float offTh;
  int minOnS;
  int minOffS;
  bool hasSchedule;
  int startMin;
  int endMin;
  bool days[7];
  int prioridad;
};

struct SensorDef {
  String codigo;
  String tipo;
  int gpio;
  bool activo;
};

// ====================== ESTADO GLOBAL ======================
String modoGlobal = "automatico";
unsigned long configVersion = 0;
String pendingCommandsJson;

Device devs[MAX_DEVS];
int devCount = 0;

Rule rules[MAX_RULES];
int ruleCount = 0;

SensorDef sensors[MAX_SENSORS];
int sensorCount = 0;

Preferences prefs;

// ====================== SENSORES VALORES ======================
float v_temp   = NAN;
float v_hr     = NAN;
float v_soil   = NAN;
float v_luz    = NAN;
float v_nivel  = NAN;

float lastTemp = NAN;
float lastHr   = NAN;

// ====================== CONFIG DINAMICA SENSORES ======================
int PIN_DHT   = -1;
int PIN_SOIL  = -1;
int PIN_LDR   = -1;

bool hasTemp  = false;
bool hasHr    = false;
bool hasSoil  = false;
bool hasLdr   = false;
bool hasNivel = false;

DHT* dht = nullptr;
int lastDhtPin = -999;
bool dhtStarted = false;

// ====================== TIMERS ======================
unsigned long lastSyncMs = 0;
unsigned long lastPostMs = 0;
unsigned long lastWifiTryMs = 0;

const unsigned long SYNC_INTERVAL_MS = 2000;
const unsigned long POST_INTERVAL_MS = 10000;
const unsigned long WIFI_RETRY_MS    = 5000;

// ====================== CALIBRACION ADC ======================
int SOIL_DRY = 2620;
int SOIL_WET = 1500;

int LDR_DARK = 3500;
int LDR_BRIGHT = 800;

// ====================== HELPERS ======================
float clampf(float x, float a, float b) {
  return x < a ? a : (x > b ? b : x);
}

unsigned long secToMs(int s) {
  return (unsigned long)s * 1000UL;
}

int timeToMin(const char* hhmmss) {
  if (!hhmmss) return -1;
  if (strlen(hhmmss) < 5) return -1;
  int hh = (hhmmss[0] - '0') * 10 + (hhmmss[1] - '0');
  int mm = (hhmmss[3] - '0') * 10 + (hhmmss[4] - '0');
  return hh * 60 + mm;
}

float jsonToFloat(JsonVariant v, float def = 0.0f) {
  if (v.isNull()) return def;

  if (v.is<float>() || v.is<double>() || v.is<int>() || v.is<long>()) {
    return v.as<float>();
  }

  if (v.is<const char*>()) {
    const char* s = v.as<const char*>();
    if (!s) return def;
    return atof(s);
  }

  String tmp = v.as<String>();
  if (tmp.length() == 0) return def;
  return tmp.toFloat();
}

float adcToPct(int adc, int a, int b) {
  if (a == b) return 0;
  float pct = 100.0f * (float)(a - adc) / (float)(a - b);
  return clampf(pct, 0, 100);
}

void initNTP() {
  configTime(GMT_OFFSET_SEC, DAYLIGHT_OFFSET_SEC, NTP1, NTP2);
  struct tm tinfo;
  for (int i = 0; i < 20; i++) {
    if (getLocalTime(&tinfo, 200)) {
      timeReady = true;
      return;
    }
    delay(200);
  }
  timeReady = false;
}

int nowMinutesOfDay() {
  struct tm tinfo;
  if (!getLocalTime(&tinfo, 50)) return -1;
  return tinfo.tm_hour * 60 + tinfo.tm_min;
}

int nowDayIndexMon0() {
  struct tm tinfo;
  if (!getLocalTime(&tinfo, 50)) return -1;
  int w = tinfo.tm_wday;         // 0=Sun..6=Sat
  return (w == 0) ? 6 : (w - 1); // 0=Mon..6=Sun
}

bool inScheduleEx(const Rule& r) {
  if (!r.hasSchedule) return true;
  if (!timeReady) return true;

  int m = nowMinutesOfDay();
  int d = nowDayIndexMon0();
  if (m < 0 || d < 0) return true;
  if (!r.days[d]) return false;

  if (r.startMin <= r.endMin) {
    return (m >= r.startMin && m <= r.endMin);
  } else {
    return (m >= r.startMin || m <= r.endMin);
  }
}

Device* findDev(const String& codigo) {
  for (int i = 0; i < devCount; i++) {
    if (devs[i].codigo == codigo) return &devs[i];
  }
  return nullptr;
}

SensorDef* findSensor(const String& codigo) {
  for (int i = 0; i < sensorCount; i++) {
    if (sensors[i].codigo == codigo) return &sensors[i];
  }
  return nullptr;
}

float getSensorValue(const String& codigo) {
  if (codigo == "S_TEMP")   return v_temp;
  if (codigo == "S_HR")     return v_hr;
  if (codigo == "S_HSUELO") return v_soil;
  if (codigo == "S_LUZ")    return v_luz;
  if (codigo == "S_NIVEL")  return v_nivel;
  return NAN;
}

// ====================== DIRECCION DE CONTROL ======================
// true  => enciende cuando el valor SUBE
// false => enciende cuando el valor BAJA
bool ruleIsHighOn(const Rule& r) {
  if (r.deviceCodigo == "D_FAN")   return true;   // ventilador por alta temperatura
  if (r.deviceCodigo == "D_CALEF") return false;  // calefacción por baja temperatura
  if (r.deviceCodigo == "D_LUZ")   return false;  // luz por baja iluminación
  if (r.deviceCodigo == "D_RIEGO") return false;  // riego por baja humedad de suelo

  if (r.sensorCodigo == "S_NIVEL") return true;
  return false;
}

bool wantsOn(const Rule& r, float val, int currentState) {
  if (isnan(val)) return false;

  bool highOn = ruleIsHighOn(r);

  if (highOn) {
    if (currentState <= 0) return val >= r.onTh;
    return val > r.offTh;
  } else {
    if (currentState <= 0) return val <= r.onTh;
    return val < r.offTh;
  }
}

void applyRelay(Device& d, int logicalState) {
  int out = d.invertido ? !logicalState : logicalState;

  Serial.print("applyRelay -> ");
  Serial.print(d.codigo);
  Serial.print(" | gpio=");
  Serial.print(d.gpio);
  Serial.print(" | logicalState=");
  Serial.print(logicalState);
  Serial.print(" | invertido=");
  Serial.print(d.invertido);
  Serial.print(" | write=");
  Serial.println(out);

  digitalWrite(d.gpio, out);
  d.current = logicalState;
  d.lastChangeMs = millis();
}

bool canSwitchTo(Device& d, int target, int minOnS, int minOffS) {
  if (d.current == -1) return true;

  unsigned long elapsed = millis() - d.lastChangeMs;
  if (target == 1) return elapsed >= secToMs(minOffS);
  return elapsed >= secToMs(minOnS);
}

// ====================== NIVEL DE AGUA ======================
float readWaterLevelPctRaw() {
  int l100 = digitalRead(PIN_L100);
  int l75  = digitalRead(PIN_L75);
  int l50  = digitalRead(PIN_L50);
  int l25  = digitalRead(PIN_L25);

  if (l100) return 100;
  if (l75)  return 75;
  if (l50)  return 50;
  if (l25)  return 25;
  return 0;
}

float readWaterLevelDebounced() {
  int count[5] = {0, 0, 0, 0, 0};

  for (int i = 0; i < 15; i++) {
    float lv = readWaterLevelPctRaw();
    int idx = (lv == 100) ? 4 : (lv == 75) ? 3 : (lv == 50) ? 2 : (lv == 25) ? 1 : 0;
    count[idx]++;
    delay(10);
  }

  int best = 0;
  for (int i = 1; i < 5; i++) {
    if (count[i] > count[best]) best = i;
  }

  return (best == 4) ? 100 : (best == 3) ? 75 : (best == 2) ? 50 : (best == 1) ? 25 : 0;
}

// ====================== WIFI ======================
void ensureWiFi() {
  if (WiFi.status() == WL_CONNECTED) return;

  unsigned long nowMs = millis();
  if (nowMs - lastWifiTryMs < WIFI_RETRY_MS) return;
  lastWifiTryMs = nowMs;

  Serial.println("Reintentando WiFi...");
  WiFi.disconnect();
  WiFi.begin(WIFI_SSID, WIFI_PASS);
}

// ====================== HTTP ======================
bool beginHttp(HTTPClient& http, const String& url, WiFiClient& plainClient, WiFiClientSecure& secureClient) {
  if (url.startsWith("https://")) {
    if (ROOT_CA_CERT == nullptr || strlen(ROOT_CA_CERT) == 0) {
      Serial.println("HTTPS rechazado: falta configurar ROOT_CA_CERT en secrets.h.");
      return false;
    }
    secureClient.setCACert(ROOT_CA_CERT);
    return http.begin(secureClient, url);
  }

  return http.begin(plainClient, url);
}

bool isWaterLevelPin(int pin) {
  return pin == PIN_L25 || pin == PIN_L50 || pin == PIN_L75 || pin == PIN_L100;
}

bool validateHardwarePinMap() {
  bool tankPathConflict = false;
  const bool shouldLogConflicts = !pinMapWarningLogged;

  for (int i = 0; i < sensorCount; i++) {
    if (sensors[i].activo && sensors[i].gpio >= 0 && isWaterLevelPin(sensors[i].gpio)) {
      if (shouldLogConflicts) {
        Serial.print("ERROR PINOUT: sensor ");
        Serial.print(sensors[i].codigo);
        Serial.print(" comparte GPIO ");
        Serial.print(sensors[i].gpio);
        Serial.println(" con una entrada fija del nivel del tanque; sensor deshabilitado.");
      }
      sensors[i].activo = false;
      tankPathConflict = true;
    }
  }

  for (int i = 0; i < devCount; i++) {
    if (devs[i].gpio >= 0 && isWaterLevelPin(devs[i].gpio)) {
      if (shouldLogConflicts) {
        Serial.print("ERROR PINOUT: actuador ");
        Serial.print(devs[i].codigo);
        Serial.print(" comparte GPIO ");
        Serial.print(devs[i].gpio);
        Serial.println(" con una entrada fija del nivel; actuador deshabilitado.");
      }
      devs[i].gpio = -1;
      devs[i].current = -1;
      tankPathConflict = true;
    }
  }

  if (tankPathConflict && shouldLogConflicts) {
    Serial.println("Modo seguro de pinout: riego bloqueado hasta corregir los GPIO compartidos.");
  }
  if (tankPathConflict) pinMapWarningLogged = true;

  return tankPathConflict;
}

bool httpGET(const String& url, String& out) {
  if (WiFi.status() != WL_CONNECTED) return false;

  HTTPClient http;
  WiFiClient plainClient;
  WiFiClientSecure secureClient;
  if (!beginHttp(http, url, plainClient, secureClient)) return false;
  http.addHeader("Accept", "application/json");
  http.addHeader("X-API-KEY", API_KEY);

  int code = http.GET();
  if (code > 0) out = http.getString();

  Serial.print("GET ");
  Serial.print(url);
  Serial.print(" -> ");
  Serial.println(code);

  if (code > 0) {
    Serial.println(out);
  }

  http.end();
  return (code == 200);
}

bool httpPOSTJson(const String& url, const String& body, String& responseOut) {
  if (WiFi.status() != WL_CONNECTED) return false;

  HTTPClient http;
  WiFiClient plainClient;
  WiFiClientSecure secureClient;
  if (!beginHttp(http, url, plainClient, secureClient)) return false;
  http.addHeader("Content-Type", "application/json");
  http.addHeader("Accept", "application/json");
  http.addHeader("X-API-KEY", API_KEY);

  int code = http.POST(body);
  if (code > 0) responseOut = http.getString();

  Serial.print("POST ");
  Serial.print(url);
  Serial.print(" -> ");
  Serial.println(code);

  if (code > 0) {
    Serial.println(responseOut);
  }

  http.end();
  return (code >= 200 && code < 300);
}

// ====================== NVS ======================
bool saveSyncJson(const String& json) {
  prefs.begin("claveles", false);
  bool ok = prefs.putString("sync_json", json) > 0;
  prefs.end();
  return ok;
}

String loadSyncJson() {
  prefs.begin("claveles", true);
  String s = prefs.getString("sync_json", "");
  prefs.end();
  return s;
}

// ====================== CONFIG SENSORES ======================
void rebuildSensorConfig() {
  hasTemp = false;
  hasHr = false;
  hasSoil = false;
  hasLdr = false;
  hasNivel = false;

  PIN_DHT = -1;
  PIN_SOIL = -1;
  PIN_LDR = -1;

  SensorDef* sTemp  = findSensor("S_TEMP");
  SensorDef* sHr    = findSensor("S_HR");
  SensorDef* sSoil  = findSensor("S_HSUELO");
  SensorDef* sLdr   = findSensor("S_LUZ");
  SensorDef* sNivel = findSensor("S_NIVEL");

  // DHT11: temperatura y humedad comparten pin
  if (sTemp && sTemp->activo && sTemp->gpio >= 0) {
    hasTemp = true;
    PIN_DHT = sTemp->gpio;
  }

  if (sHr && sHr->activo && sHr->gpio >= 0) {
    hasHr = true;
    if (PIN_DHT < 0) {
      PIN_DHT = sHr->gpio;
    }
  }

  if (hasTemp && hasHr && sTemp && sHr && sTemp->gpio != sHr->gpio) {
    Serial.println("ADVERTENCIA: S_TEMP y S_HR tienen distinto GPIO. Se usara el GPIO de S_TEMP.");
  }

  if (sSoil && sSoil->activo && sSoil->gpio >= 0) {
    hasSoil = true;
    PIN_SOIL = sSoil->gpio;
  }

  if (sLdr && sLdr->activo && sLdr->gpio >= 0) {
    hasLdr = true;
    PIN_LDR = sLdr->gpio;
  }

  if (sNivel && sNivel->activo) {
    hasNivel = true;
  }

  if ((hasTemp || hasHr) && PIN_DHT >= 0) {
    if (dht == nullptr || lastDhtPin != PIN_DHT || !dhtStarted) {
      if (dht != nullptr) {
        delete dht;
        dht = nullptr;
      }

      dht = new DHT(PIN_DHT, DHT11);
      dht->begin();
      lastDhtPin = PIN_DHT;
      dhtStarted = true;

      delay(1200);
      Serial.println("DHT inicializado correctamente.");
    }
  } else {
    if (dht != nullptr) {
      delete dht;
      dht = nullptr;
    }

    lastDhtPin = -999;
    dhtStarted = false;
  }

  Serial.println("=== CONFIG SENSORES ===");
  Serial.print("PIN_DHT: ");   Serial.println(PIN_DHT);
  Serial.print("PIN_SOIL: ");  Serial.println(PIN_SOIL);
  Serial.print("PIN_LDR: ");   Serial.println(PIN_LDR);
  Serial.print("hasTemp: ");   Serial.println(hasTemp);
  Serial.print("hasHr: ");     Serial.println(hasHr);
  Serial.print("hasSoil: ");   Serial.println(hasSoil);
  Serial.print("hasLdr: ");    Serial.println(hasLdr);
  Serial.print("hasNivel: ");  Serial.println(hasNivel);
}

// ====================== PARSEO SYNC ======================
bool parseSyncJson(const String& resp, bool allowCommands) {
  DynamicJsonDocument doc(16384);
  DeserializationError err = deserializeJson(doc, resp);
  if (err) {
    Serial.print("Error parseSyncJson: ");
    Serial.println(err.c_str());
    return false;
  }

  JsonObject cfg = doc["config"];
  modoGlobal = cfg["modo_global"] | "automatico";
  configVersion = (unsigned long)(cfg["config_version"] | 1);

  Device oldDevs[MAX_DEVS];
  int oldCount = devCount;
  for (int i = 0; i < oldCount; i++) {
    oldDevs[i] = devs[i];
  }

  devCount = 0;
  if (doc["dispositivos"].is<JsonArray>()) {
    for (JsonObject d : doc["dispositivos"].as<JsonArray>()) {
      if (devCount >= MAX_DEVS) break;

      String codigo = String((const char*)d["codigo"]);
      int newGpio = d["gpio_pin"] | -1;
      bool newInvertido = d["invertido"] | false;

      int prevCurrent = -1;
      unsigned long prevLastChange = 0;
      bool prevFound = false;
      int prevGpio = -1;
      bool prevInvertido = false;

      for (int j = 0; j < oldCount; j++) {
        if (oldDevs[j].codigo == codigo) {
          prevCurrent = oldDevs[j].current;
          prevLastChange = oldDevs[j].lastChangeMs;
          prevGpio = oldDevs[j].gpio;
          prevInvertido = oldDevs[j].invertido;
          prevFound = true;
          break;
        }
      }

      devs[devCount].codigo = codigo;
      devs[devCount].gpio = newGpio;
      devs[devCount].invertido = newInvertido;
      devs[devCount].desired = d["estado"] | 0;

      if (prevFound && prevGpio == newGpio && prevInvertido == newInvertido) {
        devs[devCount].current = prevCurrent;
        devs[devCount].lastChangeMs = prevLastChange;
      } else {
        devs[devCount].current = -1;
        devs[devCount].lastChangeMs = 0;
      }

      devCount++;
    }
  }

  sensorCount = 0;
  if (doc["sensores"].is<JsonArray>()) {
    for (JsonObject s : doc["sensores"].as<JsonArray>()) {
      if (sensorCount >= MAX_SENSORS) break;

      sensors[sensorCount].codigo = String((const char*)s["codigo"]);
      sensors[sensorCount].tipo   = String((const char*)s["tipo"]);
      sensors[sensorCount].gpio   = s["gpio_pin"] | -1;
      sensors[sensorCount].activo = s["activo"] | false;
      sensorCount++;
    }
  }

  ruleCount = 0;
  if (doc["control_reglas"].is<JsonArray>()) {
    for (JsonObject r : doc["control_reglas"].as<JsonArray>()) {
      if (ruleCount >= MAX_RULES) break;

      Rule &R = rules[ruleCount];
      R.id = r["id"] | 0;
      R.sensorCodigo = String((const char*)r["sensor_codigo"]);
      R.deviceCodigo = String((const char*)r["dispositivo_codigo"]);

      R.onTh = jsonToFloat(r["umbral_on"], 0.0f);
      R.offTh = jsonToFloat(r["umbral_off"], 0.0f);

      R.minOnS = r["min_on_s"] | 0;
      R.minOffS = r["min_off_s"] | 0;
      R.prioridad = r["prioridad"] | 10;

      const char* hi = r["hora_inicio"];
      const char* hf = r["hora_fin"];
      if (hi && hf) {
        R.hasSchedule = true;
        R.startMin = timeToMin(hi);
        R.endMin = timeToMin(hf);
      } else {
        R.hasSchedule = false;
        R.startMin = -1;
        R.endMin = -1;
      }

      for (int i = 0; i < 7; i++) R.days[i] = true;

      if (r["dias_semana"].is<JsonArray>()) {
        for (int i = 0; i < 7; i++) R.days[i] = false;

        for (JsonVariant dayVar : r["dias_semana"].as<JsonArray>()) {
          int day = 0;

          if (dayVar.is<int>()) {
            day = dayVar.as<int>();
          } else if (dayVar.is<const char*>()) {
            day = atoi(dayVar.as<const char*>());
          } else {
            day = String(dayVar.as<String>()).toInt();
          }

          if (day >= 1 && day <= 7) R.days[day - 1] = true;
        }
      }

      Serial.print("Parse regla ");
      Serial.print(R.id);
      Serial.print(" | sensor=");
      Serial.print(R.sensorCodigo);
      Serial.print(" | device=");
      Serial.print(R.deviceCodigo);
      Serial.print(" | onTh=");
      Serial.print(R.onTh, 2);
      Serial.print(" | offTh=");
      Serial.print(R.offTh, 2);
      Serial.print(" | minOn=");
      Serial.print(R.minOnS);
      Serial.print(" | minOff=");
      Serial.println(R.minOffS);

      ruleCount++;
    }
  }

  bool pinoutConflict = validateHardwarePinMap();
  rebuildSensorConfig();
  if (pinoutConflict) {
    hasNivel = false;
  }

  for (int i = 0; i < devCount; i++) {
    if (devs[i].gpio >= 0) pinMode(devs[i].gpio, OUTPUT);
  }

  // Nunca ejecutar órdenes guardadas en NVS: solo órdenes recibidas en una
  // sincronización HTTP reciente se procesan en el ciclo activo.
  if (allowCommands && doc["commands"].is<JsonArray>()) {
    pendingCommandsJson = "";
    serializeJson(doc["commands"], pendingCommandsJson);
  } else if (allowCommands) {
    pendingCommandsJson = "[]";
  }

  return true;
}

// ====================== SYNC ======================
void syncFromServer() {
  String resp;
  if (!httpGET(URL_SYNC, resp)) return;

  if (parseSyncJson(resp, true)) {
    saveSyncJson(resp);
  }
}

bool sendCommandAck(const String& commandId, const char* result, const String& message, int actualState) {
  DynamicJsonDocument doc(512);
  doc["command_id"] = commandId;
  doc["resultado"] = result;
  doc["mensaje"] = message;
  if (actualState >= 0) doc["estado_actual"] = actualState;

  String body;
  serializeJson(doc, body);
  String response;
  return httpPOSTJson(URL_ACK, body, response);
}

void processPendingCommands() {
  if (pendingCommandsJson.length() == 0) return;

  String commandsJson = pendingCommandsJson;
  pendingCommandsJson = "";

  DynamicJsonDocument doc(4096);
  if (deserializeJson(doc, commandsJson) || !doc.is<JsonArray>()) {
    Serial.println("Lista de comandos inválida; se descartó esta respuesta de sync.");
    return;
  }

  for (JsonObject command : doc.as<JsonArray>()) {
    String commandId = command["command_id"] | "";
    String deviceCode = command["dispositivo_codigo"] | "";
    String action = command["accion"] | "";
    int target = command["estado"] | -1;

    if (target < 0 || target > 1) {
      if (action == "on") target = 1;
      else if (action == "off") target = 0;
    }

    if (commandId.length() == 0) {
      Serial.println("Orden inválida: falta UUID; no se puede confirmar al servidor.");
      continue;
    }

    if (target < 0 || target > 1) {
      sendCommandAck(commandId, "rejected", "Orden inválida: estado lógico debe ser 0 o 1.", -1);
      continue;
    }

    if (modoGlobal != "manual") {
      sendCommandAck(commandId, "rejected", "El sistema no está en modo manual.", -1);
      continue;
    }

    Device* device = findDev(deviceCode);
    if (!device || device->gpio < 0) {
      sendCommandAck(commandId, "rejected", "Actuador no configurado en el ESP32.", -1);
      continue;
    }

    if (target == 1 && deviceCode == "D_RIEGO" && (isnan(v_nivel) || v_nivel < 25.0f)) {
      sendCommandAck(commandId, "rejected", "Riego bloqueado: lectura de tanque inválida o inferior a 25%.", device->current);
      continue;
    }

    if (target == 1 && (deviceCode == "D_FAN" || deviceCode == "D_CALEF")) {
      String oppositeCode = deviceCode == "D_FAN" ? "D_CALEF" : "D_FAN";
      Device* opposite = findDev(oppositeCode);
      if (opposite && (opposite->current == 1 || opposite->desired == 1)) {
        sendCommandAck(commandId, "rejected", "Interbloqueo: calefacción y ventilación no pueden operar juntas.", device->current);
        continue;
      }
    }

    // Las órdenes ON/OFF son idempotentes. Si ya está en el estado solicitado,
    // no se conmuta el relé; se vuelve a enviar el ACK de estado confirmado.
    if (device->current != target) {
      applyRelay(*device, target);
    }
    device->desired = target;
    sendCommandAck(commandId, "executed", "Orden aplicada por el ESP32.", device->current);
  }
}

// ====================== LECTURA SENSORES ======================
void readDHTSafe() {
  if (dht == nullptr) return;

  float t = dht->readTemperature();
  float h = dht->readHumidity();

  if (!isnan(t) && t > -10 && t < 60) {
    lastTemp = t;
  }

  if (!isnan(h) && h >= 0 && h <= 100) {
    lastHr = h;
  }

  if (hasTemp) v_temp = lastTemp;
  if (hasHr)   v_hr = lastHr;
}

void readSensors() {
  if (hasTemp || hasHr) {
    readDHTSafe();
  } else {
    v_temp = NAN;
    v_hr = NAN;
  }

  if (hasSoil && PIN_SOIL >= 0) {
    int adcSoil = analogRead(PIN_SOIL);
    v_soil = adcToPct(adcSoil, SOIL_DRY, SOIL_WET);

    Serial.print("SOIL ADC: ");
    Serial.print(adcSoil);
    Serial.print(" -> ");
    Serial.print(v_soil);
    Serial.println("%");
  } else {
    v_soil = NAN;
  }

  if (hasLdr && PIN_LDR >= 0) {
    int adcLdr = analogRead(PIN_LDR);
    v_luz = adcToPct(adcLdr, LDR_DARK, LDR_BRIGHT);

    Serial.print("LDR ADC: ");
    Serial.print(adcLdr);
    Serial.print(" -> ");
    Serial.print(v_luz);
    Serial.println("%");
  } else {
    v_luz = NAN;
  }

  if (hasNivel) {
    v_nivel = readWaterLevelDebounced();
  } else {
    v_nivel = NAN;
  }
}

// ====================== TIMESTAMP ======================
String nowIso() {
  if (!timeReady) return "";
  struct tm tinfo;
  if (!getLocalTime(&tinfo, 50)) return "";
  char buf[32];
  strftime(buf, sizeof(buf), "%Y-%m-%d %H:%M:%S", &tinfo);
  return String(buf);
}

// ====================== POST LECTURAS ======================
void postLecturas() {
  DynamicJsonDocument doc(1536);
  String ts = nowIso();
  if (ts.length() > 0) doc["ts"] = ts;

  JsonArray arr = doc.createNestedArray("lecturas");

  auto add = [&](const char* codigo, float v) {
    if (isnan(v)) return;
    JsonObject o = arr.createNestedObject();
    o["codigo"] = codigo;
    o["valor"] = v;
  };

  if (hasTemp)  add("S_TEMP", v_temp);
  if (hasHr)    add("S_HR", v_hr);
  if (hasSoil)  add("S_HSUELO", v_soil);
  if (hasLdr)   add("S_LUZ", v_luz);
  if (hasNivel) add("S_NIVEL", v_nivel);

  JsonArray actuatorArray = doc.createNestedArray("actuadores");
  for (int i = 0; i < devCount; i++) {
    if (devs[i].gpio < 0 || devs[i].current < 0) continue;
    JsonObject actuator = actuatorArray.createNestedObject();
    actuator["codigo"] = devs[i].codigo;
    actuator["estado"] = devs[i].current;
    actuator["origen"] = modoGlobal == "manual" ? "manual" : "automatico";
  }

  if (arr.size() == 0 && actuatorArray.size() == 0) return;

  String body;
  serializeJson(doc, body);

  Serial.println("JSON lecturas:");
  Serial.println(body);

  String response;
  httpPOSTJson(URL_LECTURAS, body, response);
}

// ====================== CONTROL LOCAL ======================
void controlLoop() {
  if (modoGlobal == "manual") {
    for (int i = 0; i < devCount; i++) {
      Device &d = devs[i];
      if (d.gpio < 0) continue;

      Serial.print("Manual -> ");
      Serial.print(d.codigo);
      Serial.print(" desired=");
      Serial.print(d.desired);
      Serial.print(" current=");
      Serial.println(d.current);

      if (d.current == -1 || d.desired != d.current) {
        applyRelay(d, d.desired);
      }
    }
    return;
  }

  int demandRiego = 0;
  int demandLuz   = 0;
  int demandFan   = 0;
  int demandCalef = 0;

  // Sin lectura válida del tanque se bloquea el riego (estado seguro).
  bool allowRiego = (!isnan(v_nivel) && v_nivel >= 25.0f);

  for (int i = 0; i < ruleCount; i++) {
    Rule &r = rules[i];
    if (!inScheduleEx(r)) continue;

    float val = getSensorValue(r.sensorCodigo);
    Device* d = findDev(r.deviceCodigo);
    if (!d) continue;

    bool w = wantsOn(r, val, d->current);

    Serial.print("Regla ");
    Serial.print(r.id);
    Serial.print(" sensor=");
    Serial.print(r.sensorCodigo);
    Serial.print(" val=");
    Serial.print(val, 2);
    Serial.print(" device=");
    Serial.print(r.deviceCodigo);
    Serial.print(" current=");
    Serial.print(d->current);
    Serial.print(" onTh=");
    Serial.print(r.onTh, 2);
    Serial.print(" offTh=");
    Serial.print(r.offTh, 2);
    Serial.print(" highOn=");
    Serial.print(ruleIsHighOn(r) ? "SI" : "NO");
    Serial.print(" wantsOn=");
    Serial.println(w ? "SI" : "NO");

    if (r.deviceCodigo == "D_RIEGO") {
      demandRiego = allowRiego ? (demandRiego || (w ? 1 : 0)) : 0;
    } else if (r.deviceCodigo == "D_LUZ") {
      demandLuz = demandLuz || (w ? 1 : 0);
    } else if (r.deviceCodigo == "D_FAN") {
      demandFan = demandFan || (w ? 1 : 0);
    } else if (r.deviceCodigo == "D_CALEF") {
      demandCalef = demandCalef || (w ? 1 : 0);
    }
  }

  if (!allowRiego) demandRiego = 0;

  // Interbloqueo físico: apagar el equipo opuesto antes de encender el pedido.
  // Si ambos algoritmos piden ON a la vez, se prioriza calefacción.
  Device* fanDevice = findDev("D_FAN");
  Device* heatDevice = findDev("D_CALEF");
  if (demandCalef) {
    if (demandFan) Serial.println("INTERBLOQUEO: solicitudes simultáneas; se prioriza calefacción.");
    demandFan = 0;
    if (fanDevice && fanDevice->current == 1) applyRelay(*fanDevice, 0);
  } else if (demandFan && heatDevice && heatDevice->current == 1) {
    Serial.println("INTERBLOQUEO: apagando calefacción antes de activar ventilación.");
    applyRelay(*heatDevice, 0);
  }

  auto applyDemand = [&](const String& cod, int target) {
    Device* d = findDev(cod);
    if (!d) return;

    int minOn = 0, minOff = 0;
    for (int i = 0; i < ruleCount; i++) {
      if (rules[i].deviceCodigo == cod) {
        if (rules[i].minOnS > minOn) minOn = rules[i].minOnS;
        if (rules[i].minOffS > minOff) minOff = rules[i].minOffS;
      }
    }

    Serial.print("AUTO -> ");
    Serial.print(cod);
    Serial.print(" target=");
    Serial.print(target);
    Serial.print(" current=");
    Serial.print(d->current);
    Serial.print(" minOn=");
    Serial.print(minOn);
    Serial.print(" minOff=");
    Serial.print(minOff);
    Serial.print(" elapsedMs=");
    Serial.println(millis() - d->lastChangeMs);

    if (d->current == -1) {
      applyRelay(*d, target);
      return;
    }

    if (target != d->current) {
      if (canSwitchTo(*d, target, minOn, minOff)) {
        applyRelay(*d, target);
      }
    }
  };

  Serial.print("Resumen AUTO | TEMP=");
  Serial.print(v_temp, 2);
  Serial.print(" | HR=");
  Serial.print(v_hr, 2);
  Serial.print(" | SUELO=");
  Serial.print(v_soil, 2);
  Serial.print(" | LUZ=");
  Serial.print(v_luz, 2);
  Serial.print(" | NIVEL=");
  Serial.print(v_nivel, 2);
  Serial.print(" | FAN=");
  Serial.print(demandFan);
  Serial.print(" | CALEF=");
  Serial.print(demandCalef);
  Serial.print(" | LUZ=");
  Serial.print(demandLuz);
  Serial.print(" | RIEGO=");
  Serial.println(demandRiego);

  applyDemand("D_RIEGO", demandRiego);
  applyDemand("D_LUZ", demandLuz);
  applyDemand("D_FAN", demandFan);
  applyDemand("D_CALEF", demandCalef);
}

// ====================== SETUP ======================
void setup() {
  Serial.begin(115200);
  delay(300);

  pinMode(PIN_L25, INPUT);
  pinMode(PIN_L50, INPUT);
  pinMode(PIN_L75, INPUT);
  pinMode(PIN_L100, INPUT);

  analogSetAttenuation(ADC_11db);

  WiFi.mode(WIFI_STA);
  WiFi.begin(WIFI_SSID, WIFI_PASS);

  unsigned long t0 = millis();
  while (WiFi.status() != WL_CONNECTED && millis() - t0 < 8000) {
    delay(200);
    Serial.print(".");
  }
  Serial.println();

  if (WiFi.status() == WL_CONNECTED) {
    Serial.println("WiFi OK");
    Serial.println(WiFi.localIP());
    initNTP();
  } else {
    Serial.println("WiFi NO (modo offline)");
  }

  String cached = loadSyncJson();
  if (cached.length() > 0) {
    parseSyncJson(cached, false);
    Serial.println("Config cargada desde cache NVS.");
  }

  syncFromServer();
}

// ====================== LOOP ======================
void loop() {
  ensureWiFi();

  if (WiFi.status() == WL_CONNECTED && !timeReady) {
    initNTP();
  }

  readSensors();

  unsigned long nowMs = millis();

  if (nowMs - lastSyncMs >= SYNC_INTERVAL_MS) {
    lastSyncMs = nowMs;
    syncFromServer();
  }

  processPendingCommands();
  controlLoop();

  if (nowMs - lastPostMs >= POST_INTERVAL_MS) {
    lastPostMs = nowMs;
    postLecturas();
  }

  delay(200);
}
