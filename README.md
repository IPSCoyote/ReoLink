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

### 1.1. Bewegungserkennung
Jedes Modul enthält boolsche Variablen je Kamera welche angeben, ob die Kamera eine Bewegung erkannt hat. Voraussetzung ist aktives Update-Interval (da nur dann die Daten in abhängigkeit des Intervals abgerufen werden.

### 1.2. Bildeinstellungen / Bildprofile
Ein Problem ist die Tatsache, das es bei unterschiedlichen Lichtverhältnissen sinnvoll sein kann, die Bild-Einstellungen (Helligkeit, usw.) anders einzustellen. Aus diesem Grund unterstützten die Module Bild-Profile (ImageProfiles). Man kann mittels Befehlen je Kamera die aktuellen Bildeinstellungen als Profil (mit eigenem Namen) speichern und dieses auch wieder abrufen (in die Kamera senden). So kann man dann z.B. mit einem eigenen Skript zwischen einem "Tag" und einem "Nachtprofil" umschalten. 

Beispielhaft erklärt für eine einzelne Kamera:

1. Bildeinstellungen in der Kamera für den Tag vornehmen, dann mittels 
```php
ReolinkCamera_StoreImageProfile( 12345, "Tag" ); 
```
das "Tag"-Profil abspeichern.

2. Bildeinstellungen in der Kamera für die Nacht vornehmen, dann mittels 
```php
ReolinkCamera_StoreImageProfile( 12345, "Nacht" ); 
```
das "Nacht"-Profil abspeichern.

3. Nun in einem helligkeits- oder Zeitgesteuertem Skript einfach das gewünschte Profil abrufen
```php
ReolinkCamera_ActivateImageProfile( 12345, "Nacht" );
```

Die Anzahl der Profile je Kamera ist nicht eingeschränkt. Die Profile werden in Instanz-Variablen gespeichert und können dort auch über die Konsole bearbeitet werden. Zusätzlich können die Profile auch mittels
```php
ReolinkCamera_RemoveImageProfile( 12345, "Tag" );

ReolinkCamera_RemoveAllImageProfiles( 12345 );
```
einzeln oder komplett gelöscht werden.

## 2. Systemanforderungen
- IP-Symcon ab Version 5.4 (tiefere Versionen können funktionieren, wurden aber nicht getestet)
- getestet mit RLN16-410, RLC-410W, RLC-511W, RLC-420 E1 Pro 

Es können nicht alle Kameras getestet und somit kann auch nicht die Funktion mit allen Kameras sichergestellt werden!

## 3. Installation

### Vorbereitung der Reolink Produkte
Vor der Installation des Moduls in IPSymcon sollte die Reolink Kamera oder der NVR vollständig eingerichtet sein. Da dieses Modul via IP-Adresse auf die Kamera oder den NVR zugreift, muss diese(r) im lokalen WLAN (nicht dem WLAN der Kamera oder des NVR) oder lokalen LAN mit einer statischen IP erreichbar sein.

## 4. Module
Derzeit bietet das GIT zwei Module. Einmal das Modul "REOLINK_CAMERA" für die direkte Anbindung einer einzelnen Kamera sowie das Modul "REOLINK_NVR" für den Zugriff auf einen NVR. Beide Module bieten folgende Funktionen an:

### 4.1. REOLINK_CAMERA
Das Modul "REOLINK_CAMERA" dient als Schnittstelle zu einer lokal installieten Kamera. 

### 4.1. REOLINK_NVR
Das Modul "REOLINK_NVR" dient als Schnittstelle zu einem lokal installierten NVR

## 5. Versionshistorie