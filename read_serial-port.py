import serial
import json
import os
import time
from datetime import datetime
import pymysql

# Configuration
port = '/dev/ttyUSB0'
baudrate = 9600
badge_file = '/var/www/html/IronBox/Client/badge.json'

# Connexion BDD
db = pymysql.connect(
    host='localhost',
    user='dawson',
    password='Dawson11@',
    database='IronBox',
    charset='utf8mb4',
    cursorclass=pymysql.cursors.DictCursor
)

try:
    ser = serial.Serial(port, baudrate, timeout=1)
    print(f"Lecture sur {port}...")

    while True:
        raw = ser.readline()
        try:
            line = raw.decode('utf-8', errors='ignore').strip()
        except Exception as e:
            print("Erreur de décodage :", e)
            continue

        if line.startswith("BADGE:"):
            badge_code = line.split("BADGE:")[1].strip()
            print(f"Badge détecté : {badge_code}")

            now = datetime.now()
            data = {
                "badge": badge_code,
                "heure": now.strftime("%Y-%m-%d %H:%M:%S")
            }

            # Écriture JSON
            with open(badge_file, 'w') as f:
                json.dump(data, f)

            # Insertion BDD
            with db.cursor() as cursor:
                cursor.execute("SELECT TechnicienID FROM Technicien WHERE TechBadge = %s", (badge_code,))
                result = cursor.fetchone()
                tech_id = result['TechnicienID'] if result else None

                cursor.execute("""
                    INSERT INTO Historique (HistoriqueDate, HistoriqueHeure, HistoriqueTypeEvent, badge, TechID)
                    VALUES (%s, %s, %s, %s, %s)
                """, (
                    now.strftime('%Y-%m-%d'),
                    now.strftime('%H:%M:%S'),
                    "Scan badge",
                    badge_code,
                    tech_id
                ))
                db.commit()

            # Pause + suppression du fichier après 5 secondes
            time.sleep(5)
            if os.path.exists(badge_file):
                os.remove(badge_file)
                print("badge.json supprimé")

except Exception as e:
    print("Erreur :", e)

finally:
    db.close()
