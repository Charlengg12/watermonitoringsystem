#include <WiFi.h>
#include <HTTPClient.h>
#include <ArduinoJson.h>

// WiFi credentials
const char* ssid = "main node";
const char* password = "PLDTWIFIkde5J";

// Server endpoint
const char* serverUrl = "http://water.monitoring.ehub.ph/sensor_data.php";

// Sensor pins (adjust to your setup)
const int TDS_PIN = 34;
const int PH_PIN = 35;
const int TURBIDITY_PIN = 32;
const int LEAD_PIN = 33;

// Timing
unsigned long lastPostTime = 0;
const unsigned long postInterval = 5000; // Post every 5 seconds

void setup() {
  Serial.begin(115200);
  delay(1000);
  
  // Connect to WiFi
  Serial.println("Connecting to WiFi...");
  WiFi.begin(ssid, password);
  
  while (WiFi.status() != WL_CONNECTED) {
    delay(500);
    Serial.print(".");
  }
  
  Serial.println("\nWiFi connected!");
  Serial.print("IP address: ");
  Serial.println(WiFi.localIP());
}

void loop() {
  unsigned long currentTime = millis();
  
  if (currentTime - lastPostTime >= postInterval) {
    lastPostTime = currentTime;
    
    // Read sensor values (replace with your actual sensor reading logic)
    float tdsValue = readTDS();
    float phValue = readPH();
    float turbidityValue = readTurbidity();
    float leadValue = readLead();
    String colorResult = readColor();
    
    // Determine status based on thresholds
    String tdsStatus = getStatus(tdsValue, 0, 300, 500, 1000);
    String phStatus = getStatus(phValue, 6.5, 8.5, 6.0, 9.0);
    String turbidityStatus = getStatus(turbidityValue, 0, 5, 10, 50);
    String leadStatus = getStatus(leadValue, 0, 0.01, 0.015, 0.02);
    String colorStatus = "Safe"; // Adjust based on your logic
    
    // Post data to server
    postSensorData(tdsValue, tdsStatus, phValue, phStatus, 
                   turbidityValue, turbidityStatus, leadValue, leadStatus,
                   colorResult, colorStatus);
  }
}

void postSensorData(float tds, String tdsStatus, float ph, String phStatus,
                   float turbidity, String turbidityStatus, float lead, String leadStatus,
                   String color, String colorStatus) {
  
  if (WiFi.status() == WL_CONNECTED) {
    HTTPClient http;
    http.begin(serverUrl);
    http.addHeader("Content-Type", "application/json");
    
    // Create JSON payload
    StaticJsonDocument<512> doc;
    doc["TDS_Value"] = tds;
    doc["TDS_Status"] = tdsStatus;
    doc["PH_Value"] = ph;
    doc["PH_Status"] = phStatus;
    doc["Turbidity_Value"] = turbidity;
    doc["Turbidity_Status"] = turbidityStatus;
    doc["Lead_Value"] = lead;
    doc["Lead_Status"] = leadStatus;
    doc["Color_Result"] = color;
    doc["Color_Status"] = colorStatus;
    
    String jsonPayload;
    serializeJson(doc, jsonPayload);
    
    Serial.println("Posting data: " + jsonPayload);
    
    // Send POST request
    int httpResponseCode = http.POST(jsonPayload);
    
    if (httpResponseCode > 0) {
      String response = http.getString();
      Serial.println("Response code: " + String(httpResponseCode));
      Serial.println("Response: " + response);
    } else {
      Serial.println("Error on sending POST: " + String(httpResponseCode));
    }
    
    http.end();
  } else {
    Serial.println("WiFi Disconnected");
  }
}

// Sensor reading functions (replace with your actual implementations)
float readTDS() {
  int rawValue = analogRead(TDS_PIN);
  return rawValue * 0.5; // Convert to mg/L based on your calibration
}

float readPH() {
  int rawValue = analogRead(PH_PIN);
  return map(rawValue, 0, 4095, 0, 1400) / 100.0; // 0-14 pH scale
}

float readTurbidity() {
  int rawValue = analogRead(TURBIDITY_PIN);
  return rawValue * 0.01; // Convert to NTU based on your calibration
}

float readLead() {
  int rawValue = analogRead(LEAD_PIN);
  return rawValue * 0.00001; // Convert to mg/L based on your calibration
}

String readColor() {
  // Implement your color detection logic
  return "Clear";
}

String getStatus(float value, float safeMin, float safeMax, float warningMin, float warningMax) {
  if (value >= safeMin && value <= safeMax) {
    return "Safe";
  } else if (value >= warningMin && value <= warningMax) {
    return "Warning";
  } else {
    return "Failed";
  }
}
