# Fenster- und Türkontakt (Contact Sensor)

[![Version](https://img.shields.io/badge/Symcon-PHP--Modul-red.svg?style=flat-square)](https://www.symcon.de/service/dokumentation/entwicklerbereich/sdk-tools/sdk-php/)
[![Product](https://img.shields.io/badge/Symcon%20Version-6.4-blue.svg?style=flat-square)](https://www.symcon.de/produkt/)
[![Version](https://img.shields.io/badge/Modul%20Version-3.0.20240908-orange.svg?style=flat-square)](https://github.com/Wilkware/ContactSensor)
[![License](https://img.shields.io/badge/License-CC%20BY--NC--SA%204.0-green.svg?style=flat-square)](https://creativecommons.org/licenses/by-nc-sa/4.0/)
[![Actions](https://img.shields.io/github/actions/workflow/status/wilkware/ContactSensor/ci.yml?branch=main&label=CI&style=flat-square)](https://github.com/Wilkware/ContactSensor/actions)

Das Modul reagiert entsprechend hinterlegter Verzögerungszeit und Bedingungen auf das Öffnen bzw. Schließen von Fenster- bzw. Türkontakten und führt eine Temperaturabsenkung durch.  
  
Wer die Meldungsverwaltung (Thema: [Meldungsanzeige im Webfront](https://www.symcon.de/forum/threads/12115-Meldungsanzeige-im-WebFront?highlight=Meldungsverwaltung)) kann sich über den Schaltvorgang informieren lassen.

## Inhaltverzeichnis

1. [Funktionsumfang](#user-content-1-funktionsumfang)
2. [Voraussetzungen](#user-content-2-voraussetzungen)
3. [Installation](#user-content-3-installation)
4. [Einrichten der Instanzen in IP-Symcon](#user-content-4-einrichten-der-instanzen-in-ip-symcon)
5. [Statusvariablen und Profile](#user-content-5-statusvariablen-und-profile)
6. [Visualisierung](#user-content-6-visualisierung)
7. [PHP-Befehlsreferenz](#user-content-7-php-befehlsreferenz)
8. [Versionshistorie](#user-content-8-versionshistorie)

### 1. Funktionsumfang

* Überwachen von bis zu 4 Kontaktsensoren (z.B. pro Raum)
* Verzögertes Absenken der Heizung entsprechend eingestellter Zeit
* Schalten von bis zu 2 Heizkörpern (Thermostaten bzw. Ventilantrieben)
* Bedingtes Schalten in Abhängigkeit ...
  * der Ventilstellung / Ventilöffnung
  * der Differenz zwischen Aussen- und Innentemperatur
  * Wiederholtes Testen der Bedingungen nach einstellbarer Zeit
* Automatisches Aufheben der Absenkung unabhängig von Zustand der Sensoren

### 2. Voraussetzungen

* IP-Symcon ab Version 6.4
* Heizkörpersteuerung getestet mit HmIP-WTH2 und/oder HmIP-eTRV(-2)

### 3. Installation

* Über den Modul Store das Modul _Contact Sensor_ installieren.
* Alternativ Über das Modul-Control folgende URL hinzufügen.  
`https://github.com/Wilkware/ContactSensor` oder `git://github.com/Wilkware/ContactSensor.git`

### 4. Einrichten der Instanzen in IP-Symcon

* Unter 'Instanz hinzufügen' ist das _Contact Sensor_-Modul (Alias: _Fenster- und Türkontakt_, _Türkontakt_ oder _Fensterkontakt_) unter dem Hersteller '(Geräte)' aufgeführt.

__Konfigurationsseite__:

Einstellungsbereich:

> Kontakt-Sensoren ...

Name                            | Beschreibung
------------------------------- | -----------------------------------------------------------------
1.Sensor                        | Statusvariable, eines Kontaktsensors (offen/geschlossen)
2.Sensor                        | StatusVariable, eines zweiten Kontaktsensors (offen/geschlossen)
3.Sensor                        | StatusVariable, eines dritten Kontaktsensors (offen/geschlossen)
4.Sensor                        | StatusVariable, eines vierten Kontaktsensors (offen/geschlossen)

> Bedingtes Schalten ...

Name                            | Beschreibung
------------------------------- | -----------------------------------------------------------------
Reaktionszeit (Verzögerung)     | Zeit zwischen Erkennen und Schalten
Checkbox Ventilöffnung          | Nur Absenken wenn gerade geheizt wird (Ventilstellung > 0%)
Positionsvariable               | Variable, welche die aktuelle Ventilposition enthält
Checkbox Temperatur             | Nur Absenken wenn Differenz (Schwellwert) zwischen Außen- und Innentemperatur eingestellten Wert überschreitet
Temeraturdifferenz              | Schwellert zwischen Außen- und Innentemperatur
Checkbox Wiederholungsintervall | Zeitraum in welchem wiederholt die eingstellten Bedingungen (Ventilposition & Temperaturdifferenz) getestet werden
Zeitspanne (Wiederholung)       | Intervall (Zeit) zwischen den Tests
Checkbox Absenkung aufheben     | Aktivierung der automatischen Aufhebung der Absenkung unabhängig vom Zustand der Sensoren
Zeitspanne (Aufhebung)          | Zeitraum nach dem die Absenkung aufgehoben werden soll

> Heizungssystem ...

Name                            | Beschreibung
------------------------------- | -----------------------------------------------------------------
1.Heizkörper                    | Steuerungskanal des ersten Heizungsthermostats oder -stellantriebs
2.Heizkörper                    | Steuerungskanal des zweiten Heizungsthermostats oder -stellantriebs
Skript                          | Auswahl eines Skriptes, welches nur oder zusätzlich ausgeführt werden soll (IPS_RunScriptEX). Status 1(open) bzw. 0(close) wird im Array als 'WINDOW_STATE' übergeben. Die ID des ausführenden Moduls wird in 'MODUL' mitgegeben.).

> Klimawerte ...

Name                            | Beschreibung
------------------------------- | -----------------------------------------------------------------
Außentemperatur                 | Aktuelle Außentemperatur
Innentemperatur                 | Aktuelle Raumtemperatur

> Meldungsverwaltung ...

Name                                 | Beschreibung
------------------------------------ | -----------------------------------------------------------------
Meldung an Anzeige senden            | Auswahl ob Eintrag in die Meldungsverwaltung erfolgen soll oder nicht (Ja/Nein)
Auslöser der Nachricht               | Auswahl bei welcher Aktion eine Nachricht erfolgen soll
Lebensdauer der Nachricht (Öffnen)   | Wie lange soll die öffnende Meldung angezeigt werden?
Lebensdauer der Nachricht (Schließen)| Wie lange soll die schließende Meldung angezeigt werden?
Nachricht an Visualisierung senden   | Auswahl ob Push-Nachricht gesendet werden soll oder nicht (Ja/Nein)
Auslöser der Nachricht               | Auswahl bei welcher Aktion eine Nachricht erfolgen soll
Raumname                             | Text zur eindeutigen Zuordnung des Raums
Format der Textmitteilung (Öffnen)   | Frei wählbares Format der öffnenden Nachricht/Meldung
Format der Textmitteilung (Schließen)| Frei wählbares Format der schließenden Nachricht/Meldung
Visualisierungs-Instanz              | ID der Visualisierung, an welches die Push-Nachrichten gesendet werden soll (WebFront oder TileVisu Instanz)
Meldsungsskript                      | Skript ID des Meldungsverwaltungsskripts

### 5. Statusvariablen und Profile

Es werden keine zusätzlichen Statusvariablen/Profile benötigt.

### 6. Visualisierung

Es ist keine weitere Steuerung oder gesonderte Darstellung integriert.

_Hinweis:_ Das Script 'Meldungsanzeige im Webfront' (Meldungsverwaltung) wird unterstützt.

### 7. PHP-Befehlsreferenz

Das Modul stellt keine direkten Funktionsaufrufe zur Verfügung.

### 8. Versionshistorie

v3.0.20240908

* _NEU_: Kompatibilität auf IPS 6.4 hoch gesetzt
* _FIX_: Unterscheidung der verschiedenen Visualisierungsinstanzen (PushNotification)
* _FIX_: Bibliotheks- bzw. Modulinfos vereinheitlicht
* _FIX_: Namensnennung und Repo vereinheitlicht
* _FIX_: Update Style-Checks
* _FIX_: Übersetzungen überarbeitet und verbessert
* _FIX_: Dokumentation vereinheitlicht 

v2.1.20230110

* _NEU_: Referenzieren der Gerätevariablen hinzugefügt (sicheres Löschen)
* _NEU_: Erweiterung zum Ausführen eines Skriptes
* _FIX_: 4. Kontaktsensor wurde nicht berücksichtigt

v2.0.20221204

* _NEU_: Konfigurationsformular überarbeitet und vereinheitlicht
* _NEU_: Kompatibilität auf 6.0 hoch gesetzt
* _NEU_: Meldungswesen komplett überarbeitet und erweitert
* _FIX_: Interne Bibliotheken überarbeitet und vereinheitlicht
* _FIX_: Dokumentation überarbeitet

v1.2.20201219

* _NEU_: 3. und 4. Kontaktsensor hinzugefügt
* _FIX_: Meldungslogik verbessert

v1.1.20201204

* _NEU_: 2. Kontaktsensor hinzugefügt
* _NEU_: Wiederholungsintervall für bedingtes Schalten hinzugefügt
* _NEU_: Zeitspanne für Aufhebung der Absenkung hinzugefügt
* _NEU_: Aliase für Modul auf Türkontakt und Fensterkontakt geändert
* _FIX_: Schaltungslogik komplett neu umgesetzt (via _WINDOW_STATE_)
* _FIX_: Zugriff auf interne Funktionen aufgehoben
* _FIX_: Meldungslogik umgebaut

v1.0.20200515

* _NEU_: Initialversion

## Entwickler

Seit nunmehr über 10 Jahren fasziniert mich das Thema Haussteuerung. In den letzten Jahren betätige ich mich auch intensiv in der IP-Symcon Community und steuere dort verschiedenste Skript und Module bei. Ihr findet mich dort unter dem Namen @pitti ;-)

[![GitHub](https://img.shields.io/badge/GitHub-@wilkware-181717.svg?style=for-the-badge&logo=github)](https://wilkware.github.io/)

## Spenden

Die Software ist für die nicht kommerzielle Nutzung kostenlos, über eine Spende bei Gefallen des Moduls würde ich mich freuen.

[![PayPal](https://img.shields.io/badge/PayPal-spenden-00457C.svg?style=for-the-badge&logo=paypal)](https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=8816166)

## Lizenz

Namensnennung - Nicht-kommerziell - Weitergabe unter gleichen Bedingungen 4.0 International

[![Licence](https://img.shields.io/badge/License-CC_BY--NC--SA_4.0-EF9421.svg?style=for-the-badge&logo=creativecommons)](https://creativecommons.org/licenses/by-nc-sa/4.0/)
