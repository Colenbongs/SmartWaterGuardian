// ============================================================
// Smart Water Guardian - ESP32 Water Monitor
// BOARD: ESP32 Dev Module
// Measures: Flow Rate, Total Volume, Pressure
// WiFi: Treviso Block A
// ============================================================

#include <WiFi.h>
#include <FirebaseESP32.h>

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
// METER CONFIGURATION - SELECT YOUR METER BELOW
// ============================================================

// METER 1 - User 1 (John) [DEFAULT - UNCOMMENT TO USE]
#define METER_ID "MTR-2026-0001"
#define PULSES_PER_LITER 450
#define CALIBRATION_FACTOR 4.5

// METER 2 - User 2 (Jane) - UNCOMMENT TO USE
// #define METER_ID "MTR-2026-0002"
// #define PULSES_PER_LITER 450
// #define CALIBRATION_FACTOR 4.5

// METER 3 - User 3 (Bob) - UNCOMMENT TO USE
// #define METER_ID "MTR-2026-0003"
// #define PULSES_PER_LITER 450
// #define CALIBRATION_FACTOR 4.5

// METER 4 - User 4 (Sarah) - UNCOMMENT TO USE
// #define METER_ID "MTR-2026-0004"
// #define PULSES_PER_LITER 450
// #define CALIBRATION_FACTOR 4.5

// ============================================================
// SENSOR RANGES
// ============================================================
#define PRESSURE_MIN_VOLTAGE 0.5
#define PRESSURE_MAX_VOLTAGE 4.5
#define PRESSURE_MIN_KPA 0.0
#define PRESSURE_MAX_KPA 100.0

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

const unsigned long SEND_INTERVAL = 5000;

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
    return "2026-08-04";
}

// ============================================================
// GET CURRENT HOUR (0-23)
// ============================================================
int getHour() {
    unsigned long currentTime = millis();
    return (currentTime / 3600000) % 24;
}

// ============================================================
// GET TIMESTAMP STRING - FIXED
// ============================================================
String getTimestamp() {
    return String(__DATE__) + " " + String(__TIME__);
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
    
    connectToWiFi();
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
        Serial.println("Please check:");
        Serial.println("1. Network name: " + String(WIFI_SSID));
        Serial.println("2. Password: " + String(WIFI_PASSWORD));
        Serial.println("3. Router is powered on");
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
    
    if (Firebase.get(firebaseData, path + "/registeredAt")) {
        if (firebaseData.dataType() == "string") {
            Serial.println("Device already registered.");
            return;
        }
    }
    
    FirebaseJson json;
    json.set("meterId", METER_ID);
    json.set("model", "ESP32-YF-S201");
    json.set("firmwareVersion", "2.0.0");
    json.set("registeredAt", getTimestamp());
    json.set("status", "online");
    
    if (Firebase.setJSON(firebaseData, path, json)) {
        Serial.println("Device registered in Firebase.");
        
        FirebaseJson reading;
        reading.set("flow", 0);
        reading.set("volume", 0);
        reading.set("pressure", 0);
        reading.set("status", "online");
        reading.set("timestamp", getTimestamp());
        
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
    
    totalVolume += flow * (timeInSeconds / 60.0);
    
    if (flow > 100.0) flow = 100.0;
    if (flow < 0) flow = 0;
    
    return flow;
}

// ============================================================
// READ PRESSURE
// ============================================================
float readPressure() {
    int rawValue = analogRead(PRESSURE_SENSOR_PIN);
    float voltage = (rawValue / 4095.0) * 3.3;
    
    float pressureKpa = ((voltage - PRESSURE_MIN_VOLTAGE) / 
                         (PRESSURE_MAX_VOLTAGE - PRESSURE_MIN_VOLTAGE)) * 
                         (PRESSURE_MAX_KPA - PRESSURE_MIN_KPA) + PRESSURE_MIN_KPA;
    
    if (pressureKpa < 0) pressureKpa = 0;
    if (pressureKpa > 150) pressureKpa = 150;
    
    return pressureKpa;
}

// ============================================================
// SEND DATA TO FIREBASE
// ============================================================
void sendDataToFirebase(float flow, float volume, float press) {
    String path = "meters/" + String(METER_ID);
    
    FirebaseJson json;
    json.set("flow", flow);
    json.set("volume", volume);
    json.set("pressure", press);
    json.set("status", "online");
    json.set("timestamp", getTimestamp());
    
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
    
    saveHistory(flow, volume, press);
}

// ============================================================
// SAVE TO HISTORY
// ============================================================
void saveHistory(float flow, float volume, float press) {
    String date = getDateString();
    int hour = getHour();
    
    String path = "meters/" + String(METER_ID) + "/history/" + date + "/hourly/" + String(hour);
    
    FirebaseJson json;
    json.set("flow", flow);
    json.set("volume", volume);
    json.set("pressure", press);
    json.set("timestamp", getTimestamp());
    
    Firebase.setJSON(firebaseData, path, json);
    
    String totalPath = "meters/" + String(METER_ID) + "/history/" + date + "/total";
    Firebase.get(firebaseData, totalPath);
    float existingTotal = 0;
    if (firebaseData.dataType() != "null") {
        existingTotal = firebaseData.floatData();
    }
    
    Firebase.setFloat(firebaseData, totalPath, existingTotal + volume);
}

// ============================================================
// MAIN LOOP - FIXED
// ============================================================
void loop() {
    float flow = readFlowRate();
    float press = readPressure();
    
    if (millis() - lastSendTime >= SEND_INTERVAL) {
        sendDataToFirebase(flow, totalVolume, press);
        lastSendTime = millis();
    }
    
    if (WiFi.status() != WL_CONNECTED) {
        Serial.println("WiFi disconnected. Reconnecting...");
        connectToWiFi();
    }
    
    delay(100);  // FIXED
}
