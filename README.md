# 🪟 Fenster- und Türkontakt (Contact Sensor)

[![Home](https://img.shields.io/badge/Home-wilkware.de-0b1830.svg?style=flat-square)](https://wilkware.de/module/kontaktsensor/)
[![Version](https://img.shields.io/badge/Symcon-PHP--Modul-red.svg?style=flat-square)](https://www.symcon.de/service/dokumentation/entwicklerbereich/sdk-tools/sdk-php/)
[![Product](https://img.shields.io/badge/Symcon%20Version-8.1-blue.svg?style=flat-square)](https://www.symcon.de/produkt/)
[![Version](https://img.shields.io/badge/Modul%20Version-4.1.20261025-orange.svg?style=flat-square)](https://github.com/Wilkware/ContactSensor)
[![License](https://img.shields.io/badge/License-CC%20BY--NC--SA%204.0-green.svg?style=flat-square)](https://creativecommons.org/licenses/by-nc-sa/4.0/)
[![Actions](https://img.shields.io/github/actions/workflow/status/wilkware/ContactSensor/ci.yml?branch=main&label=CI&style=flat-square)](https://github.com/Wilkware/ContactSensor/actions)

Das Modul reagiert entsprechend hinterlegter Verzögerungszeit und Bedingungen auf das Öffnen bzw. Schließen von Fenster- bzw. Türkontakten und führt eine Temperaturabsenkung durch.  
  
Wer die Meldungsverwaltung (Thema: [Meldungsanzeige im WebFront](https://www.symcon.de/forum/threads/12115-Meldungsanzeige-im-WebFront?highlight=Meldungsverwaltung)) nutzt, kann sich über den Schaltvorgang informieren lassen.

![Module-Visu](imgs/contact-sensor.png)

## Inhaltverzeichnis

1. [Funktionsumfang](#user-content-1-funktionsumfang)
2. [Voraussetzungen](#user-content-2-voraussetzungen)
3. [Installation](#user-content-3-installation)
4. [Einrichtung](#user-content-4-einrichtung)
5. [Statusvariablen](#user-content-5-statusvariablen)
6. [Darstellungen](#user-content-6-darstellungen)
7. [Visualisierung](#user-content-7-visualisierung)
8. [Befehlsreferenz](#user-content-8-befehlsreferenz)
9. [Versionshistorie](#user-content-9-versionshistorie)

### 1. Funktionsumfang

* Überwachen beliebig vieler Kontaktsensoren (z.B. pro Raum), jeder Wert ungleich 0 gilt als offen (auch gekippt), je Sensor invertierbar (z.B. Zigbee2MQTT)
* Verzögertes Absenken der Heizung entsprechend eingestellter Zeit
* Schalten beliebig vieler Heizkörper (Thermostate bzw. Stellantriebe) herstellerneutral per `RequestAction`
* Bedingtes Schalten in Abhängigkeit ...
  * der Ventilstellung / Ventilöffnung
  * der Differenz zwischen Außen- und Innentemperatur
  * Wiederholtes Testen der Bedingungen nach einstellbarer Zeit
* Automatisches Aufheben der Absenkung unabhängig vom Zustand der Sensoren
* Nachrichten an Meldungsverwaltung und Visualisierung beim Öffnen, Schließen und bei zu lange geöffnetem Fenster (optional nur unterhalb einer Außentemperatur)
* Statusvariablen zum Steuern des bedingten Schaltens (z.B. über die Kachel-Visualisierung)
* Testfunktionen im Aktionsbereich der Konfiguration

### 2. Voraussetzungen

* Symcon ab Version 8.1
* Heizkörper mit schaltbarer Fensterstatus-Variable (Bool, Integer oder Float mit Aktion), z.B. `WINDOW_STATE` bei HmIP-WTH2 und HmIP-eTRV(-2), oder mit schaltbarer Solltemperatur-Variable (Integer oder Float mit Aktion) für Geräte ohne Fensterstatus

### 3. Installation

* Über den Modul Store das Modul _Fenster- und Türkontakt_ installieren.
* Alternativ über das Modul Control folgende URL hinzufügen.  
`https://github.com/Wilkware/ContactSensor` oder `git://github.com/Wilkware/ContactSensor.git`

### 4. Einrichtung

* Unter 'Instanz hinzufügen' ist das _Fenster- und Türkontakt_-Modul (Alias: _Türkontakt_, _Fensterkontakt_) unter dem Hersteller '(Geräte)' aufgeführt.

__Konfigurationsseite__:

Einstellungsbereich:

> 🪟 Kontakt-Sensoren ...

Name                            | Beschreibung
------------------------------- | -----------------------------------------------------------------
Sensoren                        | Liste der Statusvariablen der Kontaktsensoren (0/false = geschlossen, jeder andere Wert = offen; mit „Invertiert“ gilt 0/false als offen, z.B. `contact` bei Zigbee2MQTT) mit Typ je Sensor (Fenster, Dachfenster, Tür, Terrassentür oder Sonstiges); der Typ bestimmt das Symbol in der Kachel

> 🔀 Bedingtes Schalten ...

Name                            | Beschreibung
------------------------------- | -----------------------------------------------------------------
Reaktionszeit (Verzögerung)     | Zeit zwischen Erkennen und Schalten
Positionsvariable               | Variable, welche die aktuelle Ventilposition enthält (für die Prüfung der Ventilstellung)

_Hinweis:_ Ob Ventilstellung und Temperaturdifferenz (auch wiederholt) geprüft werden und ob bzw. wann die Absenkung automatisch aufgehoben wird, wird über die Statusvariablen eingestellt (siehe 5.).

> 🔥 Heizungssystem ...

Name                            | Beschreibung
------------------------------- | -----------------------------------------------------------------
Heizkörper                      | Liste der zu schaltenden Variablen der Thermostate bzw. Stellantriebe mit Modus je Eintrag, Schalten per RequestAction:<br>_Fensterstatus_: offen = true/1, geschlossen = false/0<br>_Solltemperatur_: Absenken auf die _Absenktemperatur_ (im Bearbeiten-Dialog nur in diesem Modus einstellbar), beim Schließen wird der vorherige Sollwert wiederhergestellt (nicht, wenn er zwischenzeitlich manuell oder per Wochenprogramm verändert wurde)

> 🌡️ Klimawerte ...

Name                            | Beschreibung
------------------------------- | -----------------------------------------------------------------
Innentemperatur                 | Aktuelle Raumtemperatur
Außentemperatur                 | Aktuelle Außentemperatur

> 🔔 Meldungsverwaltung ...

Name                                 | Beschreibung
------------------------------------ | -----------------------------------------------------------------
Meldung an Anzeige senden            | Auswahl ob Eintrag in die Meldungsverwaltung erfolgen soll oder nicht (Ja/Nein)
Auslöser der Nachricht               | Auswahl bei welcher Aktion eine Nachricht erfolgen soll (Öffnen, Schließen, Zu lange offen oder Kombinationen)
Lebensdauer der Nachricht (Öffnen)   | Wie lange soll die öffnende Meldung angezeigt werden?
Lebensdauer der Nachricht (Schließen)| Wie lange soll die schließende Meldung angezeigt werden?
Nachricht an Visualisierung senden   | Auswahl ob Push-Nachricht gesendet werden soll oder nicht (Ja/Nein)
Auslöser der Nachricht               | Auswahl bei welcher Aktion eine Nachricht erfolgen soll (Öffnen, Schließen, Zu lange offen oder Kombinationen)
Raumname                             | Text zur eindeutigen Zuordnung des Raums
Format der Textmitteilung (Öffnen)   | Frei wählbares Format der öffnenden Nachricht/Meldung
Format der Textmitteilung (Schließen)| Frei wählbares Format der schließenden Nachricht/Meldung
Offen länger als                     | Dauer, nach der beim Auslöser „Zu lange offen“ eine Nachricht erfolgt, wenn noch mindestens ein Sensor offen ist (einmal je Öffnung, unabhängig von der Automatik)
Nur wenn Außentemperatur unter       | Nachricht „Zu lange offen“ nur, wenn die Außentemperatur (Klimawerte) unter dem eingestellten Wert liegt; sonst wird minütlich erneut geprüft, solange der Sensor offen ist
Format der Textmitteilung (zu lange offen) | Frei wählbares Format der Nachricht/Meldung „Zu lange offen“ (die Meldung wird beim Schließen aller Sensoren entfernt)
Visualisierungs-Instanz              | ID der Visualisierung, an welches die Push-Nachrichten gesendet werden soll (WebFront oder TileVisu Instanz)
Meldungsskript                       | Skript ID des Meldungsverwaltungsskripts

> ⚙️ Erweiterte Einstellungen ...

Name                            | Beschreibung
------------------------------- | -----------------------------------------------------------------
Gleichzeitiges Ausführen eines Skriptes | Auswahl eines Skriptes, welches nur oder zusätzlich ausgeführt werden soll (IPS_RunScriptEx). Status 1(open) bzw. 0(close) wird im Array als 'WINDOW_STATE' übergeben. Die ID des ausführenden Moduls wird in 'MODUL' mitgegeben.
Statusvariable für den Kontaktzustand anlegen | Legt die Statusvariable „Kontaktzustand“ an (offen, sobald mindestens ein Sensor offen ist), z.B. zur Zusammenfassung mehrerer Fenster eines Raumes; beim Abwählen wird sie wieder gelöscht

Aktionsbereich:

> 🧪 Test ...

Aktion                          | Beschreibung
------------------------------- | -----------------------------------------------------------------
TEMPERATUR ABSENKEN             | Schaltet die Heizkörper (und das Skript) direkt auf OFFEN, ohne Prüfung der Bedingungen
ABSENKUNG AUFHEBEN              | Hebt eine aktive Absenkung auf
ZU LANGE OFFEN                  | Sendet die Nachricht „Zu lange offen“ an Visualisierungs-Instanz und Meldungsskript, unabhängig von Nachrichteneinstellungen, Auslösern und Bedingungen (die Meldung im Dashboard wird beim nächsten Schließen aller Sensoren entfernt)

_Hinweis:_ Das Ergebnis wird jeweils als Popup angezeigt, inkl. Anzahl der Heizkörper, die nicht geschaltet werden konnten (Details im Meldungsfenster).

### 5. Statusvariablen

Die Statusvariablen werden automatisch angelegt. Das Löschen einzelner kann zu Fehlfunktionen führen.

Name                           | Typ     | Beschreibung
------------------------------ | ------- | ------------------------------------------------------------
Kontaktzustand                 | Boolean | Optional (Erweiterte Einstellungen): offen, sobald mindestens ein Sensor offen ist (nur lesend)
Automatik                      | Boolean | Schaltet die automatische Absenkung ein/aus
Ventilstellung prüfen          | Boolean | Absenkung nur bei laufender Heizung (Ventilstellung > 0%)
Temperaturdifferenz prüfen     | Boolean | Absenkung nur bei Überschreiten der Temperaturdifferenz
Temperaturdifferenz            | Integer | Schwellwert zwischen Innen- und Außentemperatur
Bedingungen wiederholt prüfen  | Integer | Intervall der erneuten Prüfung der Bedingungen
Absenkung automatisch aufheben | Integer | Zeitspanne bis zur automatischen Aufhebung der Absenkung
Absenkung                      | Boolean | Anzeige, ob die Absenkung aktiv ist (nur lesend)

_Hinweis:_ Die Statusvariablen sind die einzige Quelle für die Einstellungen des bedingten Schaltens. Beim Update von v3.x werden sie mit den bisherigen Konfigurationswerten initialisiert.

### 6. Darstellungen

Die Darstellungen werden direkt an den Statusvariablen hinterlegt, es werden keine Profile angelegt.

Variable                       | Darstellung   | Werte
------------------------------ | ------------- | ------------------------------------------------------------
Kontaktzustand                 | Wertanzeige   | Geschlossen (false), Offen (true); Symbol je nach Typ der Sensoren wie in der Kachel
Automatik                      | Schalter      | An / Aus
Ventilstellung prüfen          | Schalter      | An / Aus
Temperaturdifferenz prüfen     | Schalter      | An / Aus
Temperaturdifferenz            | Schieberegler | 0 – 30 °C (Schrittweite 1)
Bedingungen wiederholt prüfen  | Aufzählung    | Aus, 1, 2, 3, 4, 5, 10, 15 min
Absenkung automatisch aufheben | Aufzählung    | Aus, 10, 20, 30, 40, 50 min, 1, 2, 5 h
Absenkung                      | Wertanzeige   | Inaktiv (false), Aktiv (true)

### 7. Visualisierung

Das Modul bringt eine eigene Kachel für die Kachel-Visualisierung mit:

* Symbol je nach Typ der Sensoren (Fenster, Dachfenster, Tür, Terrassentür; bei gemischten Typen oder Sonstiges ein allgemeines Sensorsymbol) geöffnet bzw. geschlossen und Zustandstext in Zustandsfarbe (Geschlossen, Offen, Absenkung startet, Warte auf Bedingungen, Absenkung aktiv, Automatik aus)
* Countdown für Verzögerung, nächste Prüfung bzw. automatische Aufhebung
* Schalter für die Automatik (oben rechts)
* Leiste mit den Einstellungen des bedingten Schaltens (Ventil, Temperatur, Wiederholen, Aufheben); Ventil wird per Tippen umgeschaltet, die übrigen öffnen einen Slider
* Ventil wird nur angezeigt, wenn eine Ventilstellungs-Variable hinterlegt ist, Temperatur nur bei hinterlegter Innen- und Außentemperatur, Wiederholen nur, wenn mindestens eine der beiden Bedingungen verfügbar ist

Geschlossen:

![Geschlossen](imgs/contact-sensor-closed.png)

Offen, keine Absenkung (Bedingungen nicht erfüllt):

![Offen, keine Absenkung](imgs/contact-sensor-open.png)

Warte auf Bedingungen (Countdown bis zur nächsten Prüfung):

![Warte auf Bedingungen](imgs/contact-sensor-waiting.png)

Absenkung startet (Verzögerung läuft):

![Absenkung startet](imgs/contact-sensor-delay.png)

Absenkung aktiv (Countdown bis zur automatischen Aufhebung):

![Absenkung aktiv](imgs/contact-sensor-reduced.png)

Automatik aus:

![Automatik aus](imgs/contact-sensor-off.png)

Zusätzlich können die Statusvariablen mit ihren Darstellungen (siehe 6.) einzeln genutzt werden.

_Hinweis:_ Das Script 'Meldungsanzeige im WebFront' (Meldungsverwaltung) wird unterstützt.

### 8. Befehlsreferenz

Das Modul stellt keine direkten Funktionsaufrufe zur Verfügung.

### 9. Versionshistorie

v4.2.20261027

* _NEU_: Nachrichtenauslöser „Zu lange offen“, optional nur unterhalb einer Außentemperatur
* _NEU_: Kontaktsensoren invertierbar (z.B. `contact` bei Zigbee2MQTT: true = geschlossen)
* _NEU_: Optionale Statusvariable „Kontaktzustand“ (offen/geschlossen) unter „Erweiterte Einstellungen“
* _NEU_: Kachel zeigt nur die Bedingungen, deren Variablen hinterlegt sind

v4.1.20261025

* _NEU_: Alternativ Absenkung der Solltemperatur auf einen wählbaren Wert für Geräte ohne Fensterstatus-Variable
* _NEU_: Bearbeiten-Dialog der Heizkörper (Absenktemperatur nur im Modus Solltemperatur einstellbar)
* _NEU_: Rückmeldung der Testfunktionen als Popup
* _NEU_: Testbereich vereinheitlicht
* _FIX_: Status in den Listen zeigte einen veralteten, mitgespeicherten Wert (z.B. „Aktion erforderlich“)

v4.0.20261004

* _NEU_: Kompatibilität auf Symcon 8.1 hoch gesetzt
* _NEU_: Heizkörper werden herstellerneutral per RequestAction geschaltet (Liste von Fensterstatus-Variablen)
* _NEU_: Beliebig viele Kontaktsensoren (Liste), gekippt wird als offen gewertet
* _NEU_: Einstellungen des bedingten Schaltens als Statusvariablen inkl. Unterstützung der Kachel-Visualisierung
* _NEU_: Eigene Kachel für die Kachel-Visualisierung inkl. Einstellungen
* _NEU_: Auswahl des Typs je Sensor (Fenster, Dachfenster, Tür, Terrassentür, Sonstiges) für das Symbol in der Kachel
* _NEU_: Automatik zum Ein-/Ausschalten der Absenkung (unterbrochener Ablauf wird beim Einschalten fortgesetzt)
* _NEU_: Skriptauswahl unter 'Erweiterte Einstellungen' verschoben
* _NEU_: Testfunktionen im Aktionsbereich
* _NEU_: Automatische Migration der bisherigen Konfiguration
* _NEU_: Konfigurationsformular auf Standard-Struktur umgestellt
* _FIX_: 4. Kontaktsensor wurde nicht überwacht
* _FIX_: Fehlerhafte Zustandsverwaltung bei mehreren offenen Sensoren
* _FIX_: Typfehler beim Speichern der Meldungsnummer
* _FIX_: Meldung wird beim Schließen immer entfernt
* _FIX_: Warten auf Kernel-Start, Prüfung gelöschter Variablen

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

Seit nunmehr über 10 Jahren fasziniert mich das Thema Haussteuerung. In den letzten Jahren betätige ich mich auch intensiv in der Symcon Community und steuere dort verschiedenste Skript und Module bei. Ihr findet mich dort unter dem Namen @pitti ;-)

[![GitHub](https://img.shields.io/badge/GitHub-@wilkware-181717.svg?style=for-the-badge&logo=github)](https://wilkware.github.io/)

## Spenden

Die Software ist für die nicht kommerzielle Nutzung kostenlos, über eine Spende bei Gefallen des Moduls würde ich mich freuen.

[![PayPal](https://img.shields.io/badge/PayPal-spenden-00457C.svg?style=for-the-badge&logo=paypal)](https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=8816166)

## Lizenz

Namensnennung - Nicht-kommerziell - Weitergabe unter gleichen Bedingungen 4.0 International

[![Licence](https://img.shields.io/badge/License-CC_BY--NC--SA_4.0-EF9421.svg?style=for-the-badge&logo=creativecommons)](https://creativecommons.org/licenses/by-nc-sa/4.0/)
