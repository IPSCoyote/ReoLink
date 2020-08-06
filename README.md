### IP-Symcon Modul zur Einbindung von Reolink Kameras oder NVRs

<img src="./imgs/Reolink%20Logo.png">

PHP-Modul zur Einbindung von Reolink Kameras oder NVRs der Firma [Reolink](http://www.reolink.com) in IPSymcon. 

Nutzung auf eigene Gefahr ohne Gewähr. Das Modul kann jederzeit überarbeitet werden, so daß Anpassungen für eine weitere Nutzung notwendig sein können. Bei der Weiterentwicklung wird möglichst auf Kompatibilität geachtet. 

**Bei der Installation einer neuen Version sollte man in die [Versionshistorie](#5-versionshistorie) schauen, ob man evtl. manuell tätig werden muss!**

## Dokumentation

**Inhaltsverzeichnis**

1. [Funktionsumfang](#1-funktionsumfang) 
2. [Systemanforderungen](#2-systemanforderungen)
3. [Installation](#3-installation)
4. [Module](#4-module)
4. [Versionshistorie](#5-versionshistorie)

## 1. Funktionsumfang

Das Modul ist für die Handhabung von lokalen Reolink-Kameras gedacht. Der Reolink Cloud Service spielt _keine__ Rolle, da der Author diesen nicht nutzt. Auch soll das Modul nicht dazu dienen, komplette Konfigurationen von Kameras und dergl. in IPS vorzunehmen. Dieses sollte man weiterhin über die Reolink Apps machen. 
Das Ziel des Moduls ist eine bessere Einbindung in die IPS Haussteuerung um "Lücken" in den Reolink-Produkten (z.B. Bewegungsmeldung an IPS) zu schließen. Der Funktionsumfang wir nach Bedarf oder Anregungen erweitert.

## 2. Systemanforderungen
- IP-Symcon ab Version 5.4 (tiefere Versionen können funktionieren, wurden aber nicht getestet)
- getestet mit RLN16-410, RLC-410W, RLC-511W, RLC-420 E1 Pro 

Es können nicht alle Kameras getestet und somit kann auch nicht die Funktion mit allen Kameras sichergestellt werden!

## 3. Installation

### Vorbereitung der Reolink Produkte
Vor der Installation des Moduls in IPSymcon sollte die Reolink Kamera oder der NVR vollständig eingerichtet sein. Da dieses Modul via IP-Adresse auf die Kamera oder den NVR zugreift, muss diese(r) im lokalen WLAN (nicht dem WLAN der Kamera oder des NVR) oder lokalen LAN mit einer statischen IP erreichbar sein.

## 4. Module
Derzeit bietet das GIT zwei Module. Einmal das Modul "REOLINK_CAMERA" für die direkte Anbindung einer einzelnen Kamera sowie das Modul "REOLINK_NVR" für den Zugriff auf einen NVR.

### 4.1. REOLINK_CAMERA
Das Modul "REOLINK_CAMERA" dient als Schnittstelle zu einer lokal installieten Kamera. 

### 4.1. REOLINK_NVR
Das Modul "REOLINK_NVR" dient als Schnittstelle zu einem lokal installierten NVR

## 5. Versionshistorie