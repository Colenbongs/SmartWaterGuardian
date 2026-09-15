// ============================================================
// Smart Water Guardian - ESP32 Water Monitor
// BOARD: ESP32 Dev Module
// Measures: Flow Rate, Total Volume, Pressure
// WiFi: Treviso Block A
// Real NTP time sync - History page compatible
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

// METER 1 - Default
#define METER_ID "MTR-1786023830550"
#define PULSES_PER_LITER 450
#define CALIBRATION_FACTOR 4.5

// METER 2 - Uncomment to use
// #define METER_ID "MTR-2026-0002"

// METER 3 - Uncomment to use
// #define METER_ID "MTR-2026-0003"

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
const long  gmtOffset_sec = 2 * 3600;   // South Africa = UTC+2
const int   daylightOffset_sec = 0;

// ============================================================
// GLOBAL VARIABLES
// ============================================================
FirebaseData firebaseData;
FirebaseConfig firebaseConfig;
FirebaseAuth firebaseAuth;

volatile int pulseCount = 0;
float flowRate = 0.0;
float totalVolume = 0.0;          // Cumulative volume since boot
float pressure = 0.0;
unsigned long lastTime = 0;
unsigned long lastSendTime = 0;
unsigned long lastPulseTime = 0;

// Accumulators for hourly tracking
float hourlyAccumulator = 0.0;      // Volume since last hourly save
float dailyAccumulator = 0.0;       // Volume since last daily save
int lastSavedHour = -1;             // Track hour changes
String lastSavedDate = "";          // Track date changes

const unsigned long SEND_INTERVAL = 5000;      // Send lastReading every 5s
const unsigned long HISTORY_INTERVAL = 60000;  // Save history every 60s

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
    if (!getLocalTime(&timeinfo)) {
        Serial.println("Failed to obtain date");
        return "1970-01-01";
    }
    char dateStr[11];
    strftime(dateStr, sizeof(dateStr), "%Y-%m-%d", &timeinfo);
    return String(dateStr);
}

// ============================================================
// GET CURRENT HOUR (0-23)
// ============================================================
int getHour() {
    struct tm timeinfo;
    if (!getLocalTime(&timeinfo)) {
        Serial.println("Failed to obtain hour");
        return 0;
    }
    return timeinfo.tm_hour;
}

// ============================================================
// GET TIMESTAMP STRING (ISO 8601)
// ============================================================
String getTimestamp() {
    struct tm timeinfo;
    if (!getLocalTime(&timeinfo)) {
        return "1970-01-01T00:00:00Z";
    }
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
    Serial.println("Board: ESP32 Dev Module");
    Serial.println("Meter ID: " + String(METER_ID));
    Serial.println("Network: " + String(WIFI_SSID));
    Serial.println("==========================================");
    Serial.println("");

    pinMode(FLOW_SENSOR_PIN, INPUT_PULLUP);
    pinMode(PRESSURE_SENSOR_PIN, INPUT);
    pinMode(LED_PIN, OUTPUT);

    attachInterrupt(digitalPinToInterrupt(FLOW_SENSOR_PIN), pulseCounter, FALLING);

    // ---------- WiFi ----------
    connectToWiFi();

    // ---------- NTP TIME SYNC ----------
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
        Serial.print("Date: ");
        Serial.println(getDateString());
        Serial.print("Hour: ");
        Serial.println(getHour());
        lastSavedDate = getDateString();
        lastSavedHour = getHour();
    } else {
        Serial.println("");
        Serial.println("WARNING: Time sync failed - dates will be wrong!");
    }

    // ---------- Firebase ----------
    connectToFirebase();
    registerDevice();

    Serial.println("System initialized successfully.");
    Serial.println("Waiting for water flow data...");
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
    Serial.print("Connecting to WiFi: ");
    Serial.println(WIFI_SSID);

    WiFi.begin(WIFI_SSID, WIFI_PASSWORD);

    int attempts = 0;
    while (WiFi.status() != WL_CONNECTED && attempts < 30) {
        delay(500);
        Serial.print(".");
        attempts++;
    }

    if (WiFi.status() == WL_CONNECTED) {
        Serial.println("");
        Serial.println("WiFi connected successfully.");
        Serial.print("IP Address: ");
        Serial.println(WiFi.localIP().toString());
        Serial.print("Signal Strength: ");
        Serial.print(WiFi.RSSI());
        Serial.println(" dBm");
    } else {
        Serial.println("");
        Serial.println("WiFi connection failed.");
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

    if (Firebase.ready()) {
        Serial.println(" Connected.");
    } else {
        Serial.println(" Connection failed.");
    }
}

// ============================================================
// REGISTER DEVICE IN FIREBASE
// ============================================================
void registerDevice() {
    String path = "meters/" + String(METER_ID);

    // Check if already registered
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
    json.set("firmwareVersion", "2.3.0");
    json.set("registeredAt", getTimestamp());
    json.set("lastSeen", getTimestamp());
    json.set("status", "online");

    if (Firebase.setJSON(firebaseData, path, json)) {
        Serial.println("Device registered in Firebase.");

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
        Serial.print("Device registration failed: ");
        Serial.println(firebaseData.errorReason());
    }
}

// ============================================================
// READ FLOW RATE
// ============================================================
float readFlowRate() {
    unsigned long currentTime = millis();
    float timeInSeconds = (currentTime - lastTime) / 1000.0;

    if (timeInSeconds < 0.5) {
        return flowRate;
    }

    float flow = (pulseCount / (float)PULSES_PER_LITER) * 60.0 / timeInSeconds;
    flow = flow * CALIBRATION_FACTOR;

    pulseCount = 0;
    lastTime = currentTime;

    // Accumulate volume (L/min * min = L)
    float volumeThisInterval = flow * (timeInSeconds / 60.0);
    totalVolume += volumeThisInterval;
    hourlyAccumulator += volumeThisInterval;
    dailyAccumulator += volumeThisInterval;

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
    // Both formats for compatibility
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
// ============================================================
void saveHistory(float flow, float press) {
    String date = getDateString();
    int hour = getHour();

    // ============================================================
    // DETECT HOUR / DATE CHANGE
    // ============================================================
    bool hourChanged = (hour != lastSavedHour);
    bool dateChanged = (date != lastSavedDate);

    if (dateChanged) {
        Serial.println("=== NEW DAY DETECTED ===");
        Serial.print("Old date: ");
        Serial.println(lastSavedDate);
        Serial.print("New date: ");
        Serial.println(date);
        lastSavedDate = date;
        dailyAccumulator = 0.0;
    }

    if (hourChanged) {
        Serial.println("--- Hour changed ---");
        Serial.print("Old hour: ");
        Serial.print(lastSavedHour);
        Serial.print(" -> New hour: ");
        Serial.println(hour);
        lastSavedHour = hour;
        hourlyAccumulator = 0.0;
    }

    // ============================================================
    // PATH: meters/{METER_ID}/history/{YYYY-MM-DD}/hourly/{hour}
    // Write an OBJECT with volume + flow + pressure + timestamp
    // ============================================================
    String hourlyPath = "meters/" + String(METER_ID) + 
                        "/history/" + date + 
                        "/hourly/" + String(hour);

    // Get existing hourly data to accumulate volume
    float existingVolume = 0.0;
    float existingFlow = flow;
    int existingReadings = 0;

    if (Firebase.getJSON(firebaseData, hourlyPath)) {
        FirebaseJson *jsonPtr = firebaseData.jsonObjectPtr();
        if (jsonPtr) {
            FirebaseJsonData jsonData;
            if (jsonPtr->get(jsonData, "volume")) {
                existingVolume = jsonData.floatValue;
            }
            if (jsonPtr->get(jsonData, "flow")) {
                existingFlow = jsonData.floatValue;
            }
            if (jsonPtr->get(jsonData, "readings")) {
                existingReadings = jsonData.intValue;
            }
        }
    }

    float newVolume = existingVolume + hourlyAccumulator;
    int newReadings = existingReadings + 1;

    FirebaseJson hourlyJson;
    hourlyJson.set("volume", newVolume);
    hourlyJson.set("flow", flow);
    hourlyJson.set("flowRate", flow);           // Compatibility
    hourlyJson.set("pressure", press);
    hourlyJson.set("readings", newReadings);
    hourlyJson.set("timestamp", getTimestamp());
    hourlyJson.set("lastUpdate", getTimestamp());

    if (Firebase.setJSON(firebaseData, hourlyPath, hourlyJson)) {
        Serial.print("Hourly saved: ");
        Serial.print(date);
        Serial.print(" hour ");
        Serial.print(hour);
        Serial.print(" | volume=");
        Serial.print(newVolume);
        Serial.print("L readings=");
        Serial.println(newReadings);
    } else {
        Serial.print("Hourly save failed: ");
        Serial.println(firebaseData.errorReason());
    }

    // ============================================================
    // UPDATE DAILY TOTAL
    // Path: meters/{METER_ID}/history/{YYYY-MM-DD}/total
    // ============================================================
    String totalPath = "meters/" + String(METER_ID) + 
                       "/history/" + date + "/total";

    float todayTotal = 0.0;
    if (Firebase.getFloat(firebaseData, totalPath)) {
        todayTotal = firebaseData.floatValue;
    }
    
    float newTodayTotal = todayTotal + hourlyAccumulator;
    
    if (Firebase.setFloat(firebaseData, totalPath, newTodayTotal)) {
        Serial.print("Daily total updated: ");
        Serial.print(newTodayTotal);
        Serial.println(" L");
    }

    // ============================================================
    // UPDATE DAILY SUMMARY (optional - for quick access)
    // ============================================================
    String dailyPath = "meters/" + String(METER_ID) + 
                       "/history/" + date;

    FirebaseJson dailyJson;
    dailyJson.set("total", newTodayTotal);
    dailyJson.set("lastUpdate", getTimestamp());
    
    Firebase.updateNode(firebaseData, dailyPath, dailyJson);

    // Reset hourly accumulator
    hourlyAccumulator = 0.0;
}

// ============================================================
// MAIN LOOP
// ============================================================
void loop() {
    float flow = readFlowRate();
    float press = readPressure();

    // Send real-time data every 5 seconds
    if (millis() - lastSendTime >= SEND_INTERVAL) {
        sendDataToFirebase(flow, totalVolume, press);
        lastSendTime = millis();
    }

    // Save history every 60 seconds
    static unsigned long lastHistoryTime = 0;
    if (millis() - lastHistoryTime >= HISTORY_INTERVAL) {
        saveHistory(flow, press);
        lastHistoryTime = millis();
    }

    // Reconnect WiFi if dropped
    if (WiFi.status() != WL_CONNECTED) {
        Serial.println("WiFi disconnected. Reconnecting...");
        connectToWiFi();
    }

    // Re-sync time periodically (once per hour)
    static unsigned long lastTimeSync = 0;
    if (millis() - lastTimeSync > 3600000UL) {
        configTime(gmt_offset_sec, daylightOffset_sec, ntpServer);
        lastTimeSync = millis();
    }

    delay(100);
}
