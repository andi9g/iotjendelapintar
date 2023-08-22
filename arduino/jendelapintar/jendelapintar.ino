#include <ESP8266WebServer.h>
#include <ESP8266HTTPClient.h>
#include <WiFiClient.h>
#include <ArduinoJson.h>

#include <NTPClient.h>
#include <WiFiUdp.h>

#include <Servo.h>

//wifi
//const char* ssid = "Kedai Kopi ARKHA";
//const char* password = "sukasuka";
const char* ssid = "Internetan Puas";
const char* password = "gratiskok";

String serverUrl = "http://192.168.124.124";
String token_sensor = "64c52b96bf31f_1690643350";

DynamicJsonDocument tampungdata(1024);
DynamicJsonDocument namadata(1024);
DynamicJsonDocument aksi(1024);
DynamicJsonDocument buzzer(1024);
DynamicJsonDocument kirimData(2048);


WiFiUDP ntpUDP;
NTPClient timeClient(ntpUDP, "pool.ntp.org");

//pin raindrop
const int raindropSensorPin = A0;

//infrared
const int infraredSensorPin = D1;

//servoGorden
const int servoPinGorden = D4;
Servo myservoGorden;
int nilaiPutarGorden = 90; // Sudut saat ini

//servoJendela
const int servoPinJendela = D3;
Servo myservoJendela;
int nilaiPutarJendela = 90; // Sudut saat ini


//buzzer
int notif = 0;
#define BUZZER_PIN D0

void setup() {
  Serial.begin(115200);
  WiFi.begin(ssid, password);

  Serial.print("Connecting");
  while (WiFi.status() != WL_CONNECTED) {
    Serial.print(".");
    delay(500);
  }

  Serial.print("Successfully connected to : ");
  Serial.println(ssid);
  Serial.print("IP address: ");
  Serial.println(WiFi.localIP());

  pinMode(BUZZER_PIN, OUTPUT);
  timeClient.begin();
  timeClient.setTimeOffset(0);
  
  myservoGorden.attach(servoPinGorden);
  myservoJendela.attach(servoPinJendela);
  dataServer();
  servoStopGorden();
  servoStopJendela();
  

}

void loop() {
  
  
  proses();
  kerjakan();
  
  delay(2500);

}


void notifikasi(int jumlahNotifikasi) {
  for (int i = 0; i < jumlahNotifikasi; i++) {
    digitalWrite(BUZZER_PIN, HIGH); // Hidupkan buzzer
    delay(1000); // Tahan selama durasiHidup
    digitalWrite(BUZZER_PIN, LOW); // Matikan buzzer
    delay(1000); // Tahan selama durasiMati sebelum mengulang
  }
}


void dataServer() {
   HTTPClient http;
   WiFiClient client;
   String url = "/jendelapintar/public/api/sensor/data";
   url = serverUrl + url;
   client.connect(serverUrl, 80);
   http.begin(client, url);

   int httpResponseCode = http.GET();

   if (httpResponseCode == HTTP_CODE_OK) {
      // Mendapatkan respons JSON dari server
      String response = http.getString();
  
      // Mendekode JSON dari respons
      deserializeJson(tampungdata, response);

//      Serial.println(response);
      JsonArray dataArray = tampungdata.as<JsonArray>();
      for (JsonVariant v : dataArray) {
        JsonObject obj = v.as<JsonObject>();
        
        JsonObject objdata = namadata.createNestedObject();
        objdata["namadata"] = obj["namadata"];
        
      }
    
   }else {
      Serial.print("Gagal mengambil data. Kode respons: ");
      Serial.println(httpResponseCode);
   }
}


void proses() {
  timeClient.update();
  time_t epochTime = timeClient.getEpochTime();
  String kirim = "";

  int jarakJendela = readInfraredData();
  int rainDrop = readRaindropData();
  
  JsonArray dataArray = namadata.as<JsonArray>();

  JsonObject objdata = kirimData.createNestedObject();
  for (JsonVariant v : dataArray) {
        JsonObject obj = v.as<JsonObject>();
        
       if(obj["namadata"].as<String>() == "jarakjendela") {
          objdata[obj["namadata"].as<String>()] = jarakJendela;  
       }else if(obj["namadata"].as<String>() == "jarakgorden") {
          objdata[obj["namadata"].as<String>()] = readInfraredData(); 
       }else if(obj["namadata"].as<String>() == "raindrops") {
          objdata[obj["namadata"].as<String>()] = rainDrop;
       }
       objdata["waktu"] = epochTime;
  }
  
  
  upload(kirim);
  
  kirim = "";
  kirimData.clear();
  
}

void upload(String kirim)
{
  HTTPClient http;
  WiFiClient client;
  String url = "/jendelapintar/public/api/sensor/data/kirim";
  url = serverUrl + url;

  serializeJson(kirimData, kirim);

  Serial.println(kirim);
  client.connect(serverUrl, 80);
  http.begin(client, url);

  http.addHeader("Content-Type", "application/json");
  http.addHeader("token-sensor", String(token_sensor));
  int httpResponseCode = http.POST(kirim);

  if (httpResponseCode == HTTP_CODE_OK) {
    String response = http.getString();
    deserializeJson(buzzer, response);

    JsonObject obj = buzzer.as<JsonObject>();
//    Serial.println(obj["buzzer"].as<int>());
    int buzzer = obj["buzzer"].as<int>();
  
    if(notif != buzzer) {
      notif = buzzer;
      notifikasi(notif);
    }
  }else {
    Serial.print("Gagal mengambil data upload");
  }

  
  
}


void kerjakan() {
  HTTPClient http;
  WiFiClient client;
  String url = "/jendelapintar/public/api/kendali/"+token_sensor+"/data";
  url = serverUrl + url;
  
  client.connect(serverUrl, 80);
  http.begin(client, url);

  int httpResponseCode = http.GET();

   if (httpResponseCode == HTTP_CODE_OK) {
      // Mendapatkan respons JSON dari server
      String response = http.getString();
  
      // Mendekode JSON dari respons
      deserializeJson(aksi, response);

      
      JsonObject obj = aksi.as<JsonObject>();
      int nilaiJendela = obj["jendela"].as<int>();
      int nilaiGorden = obj["gorden"].as<int>();

        if(nilaiJendela == 1 && nilaiGorden == 1){
          if( nilaiGorden== 1) {
            if(nilaiPutarGorden == 90){
              nilaiPutarGorden = 0;
            }
            
            if(nilaiPutarGorden != 0) {
               putarKananGorden();
               nilaiPutarGorden=0;
            }
          }else {
            if(nilaiPutarGorden == 90){
              nilaiPutarGorden = 180;
            }
            if(nilaiPutarGorden != 180) {
              putarKiriGorden();
              nilaiPutarGorden=180;
            }
          }
        ///////////////////////////////////////////////
          if( nilaiJendela== 1) {
            if(nilaiPutarJendela == 90){
              nilaiPutarJendela = 0;
            }
            
            if(nilaiPutarJendela != 0) {
               putarKananJendela();
               nilaiPutarJendela=0;
            }
          }else {
            if(nilaiPutarJendela == 90){
              nilaiPutarJendela = 180;
            }
            if(nilaiPutarJendela != 180) {
              putarKiriJendela();
              nilaiPutarJendela=180;
            }
          }
        }else if(nilaiJendela == 0 && nilaiGorden == 0){
            if( nilaiJendela== 1) {
              if(nilaiPutarJendela == 90){
                nilaiPutarJendela = 0;
              }
              
              if(nilaiPutarJendela != 0) {
                 putarKananJendela();
                 nilaiPutarJendela=0;
              }
            }else {
              if(nilaiPutarJendela == 90){
                nilaiPutarJendela = 180;
              }
              if(nilaiPutarJendela != 180) {
                putarKiriJendela();
                nilaiPutarJendela=180;
              }
            }
            ////////////////////////////////
            if( nilaiGorden== 1) {
              if(nilaiPutarGorden == 90){
                nilaiPutarGorden = 0;
              }
              
              if(nilaiPutarGorden != 0) {
                 putarKananGorden();
                 nilaiPutarGorden=0;
              }
            }else {
              if(nilaiPutarGorden == 90){
                nilaiPutarGorden = 180;
              }
              if(nilaiPutarGorden != 180) {
                putarKiriGorden();
                nilaiPutarGorden=180;
              }
            }

            
        }else {
          if( nilaiGorden== 1) {
            if(nilaiPutarGorden == 90){
              nilaiPutarGorden = 0;
            }
            
            if(nilaiPutarGorden != 0) {
               putarKananGorden();
               nilaiPutarGorden=0;
            }
          }else {
            if(nilaiPutarGorden == 90){
              nilaiPutarGorden = 180;
            }
            if(nilaiPutarGorden != 180) {
              putarKiriGorden();
              nilaiPutarGorden=180;
            }
          }
        ///////////////////////////////////////////////
          if( nilaiJendela== 1) {
            if(nilaiPutarJendela == 90){
              nilaiPutarJendela = 0;
            }
            
            if(nilaiPutarJendela != 0) {
               putarKananJendela();
               nilaiPutarJendela=0;
            }
          }else {
            if(nilaiPutarJendela == 90){
              nilaiPutarJendela = 180;
            }
            if(nilaiPutarJendela != 180) {
              putarKiriJendela();
              nilaiPutarJendela=180;
            }
          }
        }
        
        

        

        servoStopJendela();
        servoStopGorden();
        
      
    
   }else {
      Serial.print("Gagal mengambil data. Kode respons: ");
      Serial.println(httpResponseCode);
   }
}

















//---------------------------------------------------

int readRaindropData() {
  int raindropData = analogRead(raindropSensorPin);
  return raindropData;
}

void dataRaindrop() {
  int raindropValue = readRaindropData();
  Serial.print("Raindrop Data: ");
  Serial.println(raindropValue);
}

//---------------------------------------------------

int readInfraredData() {
  int infraredData = digitalRead(infraredSensorPin);
  return infraredData;
}

void dataInfrared() {
  int infraredValue = readInfraredData();
  Serial.print("Infrared Data: ");
  Serial.println(infraredValue);
}


void servoStopGorden() {
  myservoGorden.write(90); 
}

void putarKiriGorden() {
  if (myservoGorden.read() < 180) {
    
    myservoGorden.write(180); 
    Serial.print(myservoGorden.read());
    Serial.print(" | Kiri");
    delay(1400);
  }
}

void putarKananGorden() {
  if (myservoGorden.read() > 0) {
    
    myservoGorden.write(0);
    Serial.print(myservoGorden.read());
    Serial.print(" | Kanan");
    delay(1430);
  }
}

void servoStopJendela() {
  myservoJendela.write(90); 
}

void putarKiriJendela() {
  if (myservoJendela.read() < 180) {
    
    myservoJendela.write(180); 
    Serial.print(myservoJendela.read());
    Serial.print(" | Kiri");
    delay(1700);
  }
}

void putarKananJendela() {
  if (myservoJendela.read() > 0) {
    
    myservoJendela.write(0);
    Serial.print(myservoJendela.read());
    Serial.print(" | Kanan");
    delay(1300);
  }
}
