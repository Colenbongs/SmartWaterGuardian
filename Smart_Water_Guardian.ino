// ============================================================
// Smart Water Guardian - ESP32 Water Monitor (FIXED)
// BOARD: ESP32 Dev Module
// Measures: Flow Rate, Total Volume, Pressure
// WiFi: Treviso Block A
// NTP time sync - History page compatible
//
// BUG FIXED: hourlyAccumulator now captured BEFORE hour change,
// so no volume is lost when the hour rolls over.
// ============================================================

#include <WiFi.h>
#include <FirebaseESP32.h>
#include <time.h>

// ============================================================
// WIFI CREDENTIALS
// ============================================================
#define WIFI_SSID "Treviso Block A"
#define WIFI_PASSWORD "TrevisoA#2022#"

// ============================================================
// FIREBASE CONFIGURATION
// ============================================================
#define FIREBASE_HOST "https://smartwaterguardian-default-rtdb.firebaseio.com"
#define FIREBASE_AUTH "AIzaSyCatcC7yo-a7E7dLAfAWh0iv1BCSoYxUP8"

// ============================================================
// HARDWARE PIN DEFINITIONS
// ============================================================
#define FLOW_SENSOR_PIN 34
#define PRESSURE_SENSOR_PIN 35
#define LED_PIN 2

// ============================================================
// METER CONFIGURATION
// ============================================================
#define METER_ID "MTR-1786023830550"
#define PULSES_PER_LITER 450
#define CALIBRATION_FACTOR 4.5

// ============================================================
// SENSOR RANGES
// ============================================================
#define PRESSURE_MIN_VOLTAGE 0.5
#define PRESSURE_MAX_VOLTAGE 4.5
#define PRESSURE_MIN_KPA 0.0
#define PRESSURE_MAX_KPA 100.0

// ============================================================
// NTP TIME CONFIGURATION
// ============================================================
const char* ntpServer = "pool.ntp.org";
const long  gmtOffset_sec = 2 * 3600;
const int   daylightOffset_sec = 0;

// ============================================================
// GLOBAL VARIABLES
// ============================================================
FirebaseData firebaseData;
FirebaseConfig firebaseConfig;
FirebaseAuth firebaseAuth;

volatile int pulseCount = 0;
float flowRate = 0.0;
float totalVolume = 0.0;
float pressure = 0.0;
unsigned long lastTime = 0;
unsigned long lastSendTime = 0;
unsigned long lastPulseTime = 0;

// Volume accumulator - tracks volume since last successful save
float hourlyAccumulator = 0.0;
int lastSavedHour = -1;
String lastSavedDate = "";

const unsigned long SEND_INTERVAL = 5000;
const unsigned long HISTORY_INTERVAL = 60000;

// ============================================================
// INTERRUPT SERVICE ROUTINE
// ============================================================
void IRAM_ATTR pulseCounter() {
    pulseCount++;
    lastPulseTime = millis();
}

// ============================================================
// GET DATE STRING (YYYY-MM-DD)
// ============================================================
String getDateString() {
    struct tm timeinfo;
    if (!getLocalTime(&timeinfo)) return "1970-01-01";
    char dateStr[11];
    strftime(dateStr, sizeof(dateStr), "%Y-%m-%d", &timeinfo);
    return String(dateStr);
}

// ============================================================
// GET CURRENT HOUR (0-23)
// ============================================================
int getHour() {
    struct tm timeinfo;
    if (!getLocalTime(&timeinfo)) return 0;
    return timeinfo.tm_hour;
}

// ============================================================
// GET TIMESTAMP STRING (ISO 8601)
// ============================================================
String getTimestamp() {
    struct tm timeinfo;
    if (!getLocalTime(&timeinfo)) return "1970-01-01T00:00:00Z";
    char timeStr[25];
    strftime(timeStr, sizeof(timeStr), "%Y-%m-%dT%H:%M:%SZ", &timeinfo);
    return String(timeStr);
}

// ============================================================
// SETUP
// ============================================================
void setup() {
    Serial.begin(115200);
    Serial.println("");
    Serial.println("==========================================");
    Serial.println("Smart Water Guardian - ESP32");
    Serial.println("Meter ID: " + String(METER_ID));
    Serial.println("==========================================");
    Serial.println("");

    pinMode(FLOW_SENSOR_PIN, INPUT_PULLUP);
    pinMode(PRESSURE_SENSOR_PIN, INPUT);
    pinMode(LED_PIN, OUTPUT);

    attachInterrupt(digitalPinToInterrupt(FLOW_SENSOR_PIN), pulseCounter, FALLING);

    connectToWiFi();

    Serial.print("Syncing time with NTP");
    configTime(gmtOffset_sec, daylightOffset_sec, ntpServer);

    struct tm timeinfo;
    int retries = 0;
    while (!getLocalTime(&timeinfo) && retries < 15) {
        Serial.print(".");
        delay(1000);
        retries++;
    }

    if (retries < 15) {
        Serial.println("");
        Serial.print("Time synced: ");
        Serial.println(getTimestamp());
        lastSavedDate = getDateString();
        lastSavedHour = getHour();
    } else {
        Serial.println("WARNING: Time sync failed!");
    }

    connectToFirebase();
    registerDevice();

    Serial.println("System initialized. Waiting for data...");
    Serial.println("");

    for (int i = 0; i < 3; i++) {
        digitalWrite(LED_PIN, HIGH);
        delay(200);
        digitalWrite(LED_PIN, LOW);
        delay(200);
    }
}

// ============================================================
// CONNECT TO WIFI
// ============================================================
void connectToWiFi() {
    Serial.print("Connecting to WiFi...");
    WiFi.begin(WIFI_SSID, WIFI_PASSWORD);

    int attempts = 0;
    while (WiFi.status() != WL_CONNECTED && attempts < 30) {
        delay(500);
        Serial.print(".");
        attempts++;
    }

    if (WiFi.status() == WL_CONNECTED) {
        Serial.println(" Connected.");
        Serial.print("IP: ");
        Serial.println(WiFi.localIP().toString());
    } else {
        Serial.println(" Failed. Restarting...");
        delay(5000);
        ESP.restart();
    }
}

// ============================================================
// CONNECT TO FIREBASE
// ============================================================
void connectToFirebase() {
    Serial.print("Connecting to Firebase...");
    firebaseConfig.database_url = FIREBASE_HOST;
    firebaseConfig.signer.tokens.legacy_token = FIREBASE_AUTH;
    Firebase.begin(&firebaseConfig, &firebaseAuth);
    Firebase.reconnectWiFi(true);
    if (Firebase.ready()) Serial.println(" Connected.");
    else Serial.println(" Failed.");
}

// ============================================================
// REGISTER DEVICE
// ============================================================
void registerDevice() {
    String path = "meters/" + String(METER_ID);

    if (Firebase.get(firebaseData, path + "/meterId")) {
        if (firebaseData.dataType() == "string") {
            Serial.println("Device already registered.");
            Firebase.setString(firebaseData, path + "/status", "online");
            Firebase.setString(firebaseData, path + "/lastSeen", getTimestamp());
            return;
        }
    }

    FirebaseJson json;
    json.set("meterId", METER_ID);
    json.set("model", "ESP32-YF-S201");
    json.set("firmwareVersion", "2.4.0");
    json.set("registeredAt", getTimestamp());
    json.set("lastSeen", getTimestamp());
    json.set("status", "online");

    if (Firebase.setJSON(firebaseData, path, json)) {
        Serial.println("Device registered.");
        FirebaseJson reading;
        reading.set("flow", 0.0);
        reading.set("flowRate", 0.0);
        reading.set("volume", 0.0);
        reading.set("totalVolume", 0.0);
        reading.set("pressure", 0.0);
        reading.set("battery", 100);
        reading.set("status", "online");
        reading.set("timestamp", getTimestamp());
        reading.set("lastUpdated", getTimestamp());
        Firebase.setJSON(firebaseData, path + "/lastReading", reading);
    } else {
        Serial.print("Registration failed: ");
        Serial.println(firebaseData.errorReason());
    }
}

// ============================================================
// READ FLOW RATE
// ============================================================
float readFlowRate() {
    unsigned long currentTime = millis();
    float timeInSeconds = (currentTime - lastTime) / 1000.0;

    if (timeInSeconds < 0.5) return flowRate;

    float flow = (pulseCount / (float)PULSES_PER_LITER) * 60.0 / timeInSeconds;
    flow = flow * CALIBRATION_FACTOR;

    pulseCount = 0;
    lastTime = currentTime;

    float volumeThisInterval = flow * (timeInSeconds / 60.0);
    totalVolume += volumeThisInterval;
    hourlyAccumulator += volumeThisInterval;

    if (flow > 100.0) flow = 100.0;
    if (flow < 0) flow = 0;

    return flow;
}

// ============================================================
// READ PRESSURE
// ============================================================
float readPressure() {
    int samples = 10;
    int rawValue = 0;
    for (int i = 0; i < samples; i++) {
        rawValue += analogRead(PRESSURE_SENSOR_PIN);
        delay(2);
    }
    rawValue = rawValue / samples;

    float voltage = (rawValue / 4095.0) * 3.3;

    float pressureKpa = ((voltage - PRESSURE_MIN_VOLTAGE) /
                         (PRESSURE_MAX_VOLTAGE - PRESSURE_MIN_VOLTAGE)) *
                         (PRESSURE_MAX_KPA - PRESSURE_MIN_KPA) + PRESSURE_MIN_KPA;

    if (pressureKpa < 0) pressureKpa = 0;
    if (pressureKpa > 150) pressureKpa = 150;

    return pressureKpa;
}

// ============================================================
// SEND REAL-TIME DATA (lastReading)
// ============================================================
void sendDataToFirebase(float flow, float volume, float press) {
    String path = "meters/" + String(METER_ID);
    int batteryLevel = 100;
    String ts = getTimestamp();

    FirebaseJson json;
    json.set("flow", flow);
    json.set("flowRate", flow);
    json.set("volume", volume);
    json.set("totalVolume", volume);
    json.set("pressure", press);
    json.set("battery", batteryLevel);
    json.set("status", "online");
    json.set("timestamp", ts);
    json.set("lastUpdated", ts);

    if (Firebase.updateNode(firebaseData, path + "/lastReading", json)) {
        Serial.print("Data sent: Flow=");
        Serial.print(flow);
        Serial.print(" L/min, Volume=");
        Serial.print(volume);
        Serial.print(" L, Pressure=");
        Serial.print(press);
        Serial.println(" kPa");

        digitalWrite(LED_PIN, HIGH);
        delay(50);
        digitalWrite(LED_PIN, LOW);
    } else {
        Serial.print("Send failed: ");
        Serial.println(firebaseData.errorReason());
    }

    Firebase.setString(firebaseData, path + "/lastSeen", ts);
}

// ============================================================
// SAVE HOURLY + DAILY HISTORY (every 60s)
//
// CRITICAL FIX: capture hourlyAccumulator into a local var and
// reset it BEFORE saving. If the save fails, restore the volume
// so no data is lost.
// ============================================================
void saveHistory(float flow, float press) {
    String date = getDateString();
    int hour = getHour();

    // ============================================================
    // FIX 1: CAPTURE AND RESET ACCUMULATOR AT THE START
    // This is what prevents volume loss on hour changes.
    // ============================================================
    float volumeToSave = hourlyAccumulator;
    hourlyAccumulator = 0.0;

    bool hourChanged = (hour != lastSavedHour);
    bool dateChanged = (date != lastSavedDate);

    if (dateChanged) {
        Serial.println("=== NEW DAY DETECTED ===");
        Serial.print("Old date: ");
        Serial.print(lastSavedDate);
        Serial.print(" -> New: ");
        Serial.println(date);
        lastSavedDate = date;
    }

    if (hourChanged) {
        Serial.print("--- Hour changed: ");
        Serial.print(lastSavedHour);
        Serial.print(" -> ");
        Serial.print(hour);
        Serial.println(" ---");
        lastSavedHour = hour;
    }

    // ============================================================
    // PATH: meters/{METER_ID}/history/{YYYY-MM-DD}/hourly/{hour}
    // ============================================================
    String hourlyPath = "meters/" + String(METER_ID) + 
                        "/history/" + date + 
                        "/hourly/" + String(hour);

    // Read existing hourly data
    float existingVolume = 0.0;
    int existingReadings = 0;

    if (Firebase.getJSON(firebaseData, hourlyPath)) {
        FirebaseJson *jsonPtr = firebaseData.jsonObjectPtr();
        if (jsonPtr) {
            FirebaseJsonData jsonData;
            if (jsonPtr->get(jsonData, "volume")) {
                existingVolume = jsonData.floatValue;
            }
            if (jsonPtr->get(jsonData, "readings")) {
                existingReadings = jsonData.intValue;
            }
        }
    }

    float newVolume = existingVolume + volumeToSave;
    int newReadings = existingReadings + 1;

    FirebaseJson hourlyJson;
    hourlyJson.set("volume", newVolume);
    hourlyJson.set("flow", flow);
    hourlyJson.set("flowRate", flow);
    hourlyJson.set("pressure", press);
    hourlyJson.set("readings", newReadings);
    hourlyJson.set("timestamp", getTimestamp());
    hourlyJson.set("lastUpdate", getTimestamp());

    bool hourlySaved = Firebase.setJSON(firebaseData, hourlyPath, hourlyJson);

    if (hourlySaved) {
        Serial.print("Hourly saved: ");
        Serial.print(date);
        Serial.print(" h");
        Serial.print(hour);
        Serial.print(" | +");
        Serial.print(volumeToSave);
        Serial.print("L -> ");
        Serial.print(newVolume);
        Serial.print("L (");
        Serial.print(newReadings);
        Serial.println(" readings)");
    } else {
        // ============================================================
        // FIX 2: IF SAVE FAILED, RESTORE THE VOLUME
        // so it gets retried on the next save
        // ============================================================
        hourlyAccumulator += volumeToSave;
        Serial.print("Hourly save FAILED. Restored ");
        Serial.print(volumeToSave);
        Serial.print("L. Reason: ");
        Serial.println(firebaseData.errorReason());
    }

    // ============================================================
    // UPDATE DAILY TOTAL (only if hourly succeeded)
    // ============================================================
    if (hourlySaved) {
        String totalPath = "meters/" + String(METER_ID) + 
                           "/history/" + date + "/total";

        float todayTotal = 0.0;
        if (Firebase.getFloat(firebaseData, totalPath)) {
            todayTotal = firebaseData.floatValue;
        }
        
        float newTodayTotal = todayTotal + volumeToSave;
        
        if (Firebase.setFloat(firebaseData, totalPath, newTodayTotal)) {
            Serial.print("Daily total: ");
            Serial.print(newTodayTotal);
            Serial.println(" L");
        }

        // Update daily summary with lastUpdate
        String dailyPath = "meters/" + String(METER_ID) + "/history/" + date;
        FirebaseJson dailyJson;
        dailyJson.set("total", newTodayTotal);
        dailyJson.set("lastUpdate", getTimestamp());
        Firebase.updateNode(firebaseData, dailyPath, dailyJson);
    }
}

// ============================================================
// MAIN LOOP
// ============================================================
void loop() {
    float flow = readFlowRate();
    float press = readPressure();

    if (millis() - lastSendTime >= SEND_INTERVAL) {
        sendDataToFirebase(flow, totalVolume, press);
        lastSendTime = millis();
    }

    static unsigned long lastHistoryTime = 0;
    if (millis() - lastHistoryTime >= HISTORY_INTERVAL) {
        saveHistory(flow, press);
        lastHistoryTime = millis();
    }

    if (WiFi.status() != WL_CONNECTED) {
        Serial.println("WiFi disconnected. Reconnecting...");
        connectToWiFi();
    }

    static unsigned long lastTimeSync = 0;
    if (millis() - lastTimeSync > 3600000UL) {
        configTime(gmt_offset_sec, daylightOffset_sec, ntpServer);
        lastTimeSync = millis();
    }

    delay(100);
}
