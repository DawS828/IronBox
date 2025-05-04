#define D0_PIN 12  // GPIO12
#define D1_PIN 14  // GPIO14

volatile unsigned long lastWiegand = 0;
volatile uint64_t wiegandCode = 0;
volatile byte bitCount = 0;

void ICACHE_RAM_ATTR readD0() {
  wiegandCode <<= 1;  // Décale vers la gauche
  bitCount++;
  lastWiegand = millis();
}

void ICACHE_RAM_ATTR readD1() {
  wiegandCode <<= 1;
  wiegandCode |= 1;   // Ajoute un 1 à la fin
  bitCount++;
  lastWiegand = millis();
}

void setup() {
  Serial.begin(9600);  // Communication série avec le Raspberry Pi
  pinMode(D0_PIN, INPUT_PULLUP);
  pinMode(D1_PIN, INPUT_PULLUP);

  attachInterrupt(digitalPinToInterrupt(D0_PIN), readD0, FALLING);
  attachInterrupt(digitalPinToInterrupt(D1_PIN), readD1, FALLING);
}

void loop() {
  if (bitCount > 0 && (millis() - lastWiegand) > 50) {
    if (bitCount == 88) {  // Si la trame Wiegand fait 88 bits (format standard)
      Serial.print("BADGE:");
      Serial.println(wiegandCode);  // Envoie l'ID du badge via le port série
    }

    bitCount = 0;
    wiegandCode = 0;
  }
}
