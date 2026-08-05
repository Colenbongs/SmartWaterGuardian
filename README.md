# Smart Water Guardian - ESP32 Water Flow Monitor

## README File

---

# Smart Water Guardian

### IoT-Based Water Consumption Monitoring System

---

## Table of Contents

1. [Project Overview](#project-overview)
2. [Team Members](#team-members)
3. [Hardware Requirements](#hardware-requirements)
4. [Software Requirements](#software-requirements)
5. [Wiring Instructions](#wiring-instructions)
6. [Arduino Code](#arduino-code)
7. [Upload Instructions](#upload-instructions)
8. [Firebase Setup](#firebase-setup)
9. [Expected Output](#expected-output)
10. [Troubleshooting](#troubleshooting)
11. [Data Flow Diagram](#data-flow-diagram)
12. [License](#license)

---

## Project Overview

The **Smart Water Guardian** is an IoT-based water monitoring system that tracks water consumption in real-time. The system uses an ESP32 microcontroller connected to a YF-S201 flow sensor to measure water flow rate and total volume consumed. All data is sent to Firebase Realtime Database and displayed on a web dashboard for users to monitor their water usage.

### Key Features

- Real-time flow rate monitoring (L/min)
- Total volume accumulation (Liters)
- Historical data storage
- User-specific meter assignment
- Web-based dashboard
- Light/Dark mode support

---

## Team Members

| Student Number | Name |
|----------------|------|
| 221152725 | Mongiwethu Eddy Ncube |
| 220115085 | Sandile Sibeko |
| 220068905 | Keamogetse Selebano |
| 220122253 | Ndzulamo Michelle Yingwani |
| 220080694 | Hlonipho Nersely Bila |
| 220061777 | Zizile Ezona Mbanqi |
| 219027546 | Bongane Sithole |

---

## Hardware Requirements

| Component | Quantity | Purpose |
|-----------|----------|---------|
| ESP32 Dev Module | 1 | Main microcontroller |
| YF-S201 Flow Sensor | 1 | Measures water flow rate |
| MPX5010DP Pressure Sensor | 1 | Measures water pressure (optional) |
| USB Cable (Data) | 1 | For programming and power |
| Jumper Wires | Several | For connections |
| Water Pipes | 2 | For connecting flow sensor |

---

## Software Requirements

### 1. Arduino IDE

Download and install the Arduino IDE from: https://www.arduino.cc/en/software

### 2. ESP32 Board Package

1. Open Arduino IDE
2. Go to **File > Preferences**
3. Add this URL to "Additional Boards Manager URLs":
   ```
   https://raw.githubusercontent.com/espressif/arduino-esp32/gh-pages/package_esp32_index.json
   ```
4. Go to **Tools > Board > Boards Manager**
5. Search for "esp32"
6. Install "esp32 by Espressif Systems"

### 3. Required Libraries

Go to **Sketch > Include Library > Manage Libraries** and install:

| Library Name | Author | Purpose |
|--------------|--------|---------|
| Firebase ESP32 Client | mobizt | Firebase communication |

---

## Wiring Instructions

### Flow Sensor (YF-S201)

| YF-S201 Wire | ESP32 Pin |
|--------------|-----------|
| Red | 3.3V |
| Black | GND |
| Yellow | GPIO34 |

### Pressure Sensor (MPX5010DP) - Optional

| MPX5010DP Pin | ESP32 Pin |
|---------------|-----------|
| Pin 1 (VCC) | 5V |
| Pin 2 (GND) | GND |
| Pin 3 (OUT) | GPIO35 |

### LED Status Indicator

| LED Pin | ESP32 Pin |
|---------|-----------|
| Anode (+) | GPIO2 |
| Cathode (-) | GND (via resistor) |

---

## Arduino Code

### Complete Code

```cpp
// ============================================================
// Smart Water Guardian - ESP32 Water Monitor
// BOARD: ESP32 Dev Module
// Measures: Flow Rate, Total Volume, Pressure
// WiFi: Treviso Block A
// ============================================================

#include <WiFi.h>
#include <FirebaseESP32.h>

// ============================================================
// WIFI CREDENTIALS - UPDATE THESE
// ============================================================
#define WIFI_SSID "Treviso Block A"
#define WIFI_PASSWORD "TrevisoA#2022#"

// ============================================================
// FIREBASE CONFIGURATION - UPDATE THESE
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
// METER CONFIGURATION - UPDATE THIS
// ============================================================
#define METER_ID "MTR-2026-0001"
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
// GET TIMESTAMP STRING
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
    Serial.print("Connecting to WiFi");
    
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
        Serial.println("Please check SSID and password.");
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
// MAIN LOOP
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
    
    delay(100);
}
```

---

## Upload Instructions

### Step 1: Select Board
```
Tools > Board > ESP32 Arduino > ESP32 Dev Module
```

### Step 2: Select Port
```
Tools > Port > (select your COM port)
```

### Step 3: Update Credentials

| Variable | Description | Where to Find |
|----------|-------------|---------------|
| WIFI_SSID | Your WiFi network name | From your router |
| WIFI_PASSWORD | Your WiFi password | From your router |
| FIREBASE_HOST | Firebase database URL | Firebase Console > Realtime Database |
| FIREBASE_AUTH | Firebase API key | Firebase Console > Project Settings |
| METER_ID | Unique meter number | Assigned during registration |

### Step 4: Upload

1. Click the **Upload** button (right arrow icon)
2. Wait for compilation and upload

---

## Firebase Setup

### Step 1: Update Firebase Rules

Go to Firebase Console > Realtime Database > Rules and set:

```json
{
  "rules": {
    ".read": true,
    ".write": true
  }
}
```

### Step 2: Verify Data

1. Go to Firebase Console > Realtime Database
2. Navigate to: `meters/MTR-2026-0001/lastReading`
3. You should see:

```json
{
  "flow": 12.5,
  "volume": 350.2,
  "pressure": 45.6,
  "status": "online",
  "timestamp": "Aug 5 2026 ..."
}
```

---

## Expected Output

### Serial Monitor

```
==========================================
Smart Water Guardian - ESP32
Board: ESP32 Dev Module
Meter ID: MTR-2026-0001
==========================================

Connecting to WiFi.....
WiFi connected successfully.
IP Address: 192.168.1.100
Signal Strength: -45 dBm
Connecting to Firebase... Connected.
Device registered in Firebase.
System initialized successfully.
Waiting for water flow data...

Data sent: Flow=0.00 L/min, Volume=0.00 L, Pressure=0.00 kPa
Data sent: Flow=12.50 L/min, Volume=1.04 L, Pressure=45.60 kPa
Data sent: Flow=8.30 L/min, Volume=2.08 L, Pressure=42.10 kPa
Data sent: Flow=5.10 L/min, Volume=3.13 L, Pressure=38.50 kPa
Data sent: Flow=0.00 L/min, Volume=3.13 L, Pressure=35.00 kPa
```

### Dashboard Display

| Metric | Value |
|--------|-------|
| Flow Rate | 12.5 L/min |
| Total Volume | 350.2 L |
| Pressure | 45.6 kPa |
| Device Status | Online |

---

## Troubleshooting

| Problem | Solution |
|---------|----------|
| WiFi not connecting | Check SSID and password, move closer to router |
| Firebase not connecting | Check FIREBASE_HOST and FIREBASE_AUTH |
| Permission denied | Update Firebase rules to allow writes |
| No flow reading | Check sensor wiring, turn on tap |
| Wrong volume reading | Adjust CALIBRATION_FACTOR |
| Port not showing | Install USB drivers, try different cable |
| Compilation errors | Install Firebase ESP32 Client library |

---

## Data Flow Diagram

```
Water Flow → YF-S201 Sensor → ESP32 → WiFi → Firebase → Web Dashboard
     ↓             ↓              ↓       ↓        ↓           ↓
  Physical      Generates     Counts    Sends    Stores     Displays
   Water        Pulses        Pulses    Data     Data       Charts
```

### How Volume is Calculated

The ESP32 code calculates volume using this formula:

```
Flow Rate (L/min) × Time (minutes) = Volume (Liters)

Example:
Water flows at 12.5 L/min for 5 seconds:
Volume = 12.5 × (5/60) = 1.04 Liters

totalVolume keeps accumulating:
Reading 1: +1.04 L → Total = 1.04 L
Reading 2: +1.04 L → Total = 2.08 L
Reading 3: +1.04 L → Total = 3.13 L
...and so on
```

---

## License

This project is for educational and research purposes.

