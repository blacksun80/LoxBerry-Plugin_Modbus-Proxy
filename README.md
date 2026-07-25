# LoxBerry-Plugin: Modbus-Proxy

Ein [LoxBerry](https://www.loxberry.de/)-Plugin mit grafischer Oberflaeche fuer den Python-Dienst
[**modbus-proxy**](https://pypi.org/project/modbus-proxy/) von Tiago Coutinho.

Viele Modbus-TCP-Geraete (SPS, Waermepumpen-Regler, Zaehler, ...) erlauben nur eine sehr kleine
Anzahl gleichzeitiger Verbindungen. modbus-proxy setzt sich als Bruecke dazwischen: mehrere
Clients (z.B. ein Loxone Miniserver, eine Visualisierung, ein Skript) verbinden sich mit dem
Proxy, der die Anfragen seriell an das eigentliche Geraet weiterreicht.

## Funktionen dieses Plugins

- **Konfiguration per GUI** statt Handbearbeitung der YAML-Datei: beliebig viele Modbus-Geraete
  mit Ziel-Adresse, Timeout, Verbindungsverzoegerung, Listen-Adresse und optionaler
  Unit-ID-Umleitung.
- **Speichern** schreibt die Konfiguration und startet den Dienst automatisch neu, damit die
  Aenderungen sofort aktiv sind.
- **Status-Anzeige** (laeuft/gestoppt, PID, installierte modbus-proxy-Version, pro Geraet ob der
  Listen-Port erreichbar ist), aktualisiert sich automatisch alle 5 Sekunden.
- **Start/Stopp/Neustart** des Dienstes direkt aus der GUI.
- **Export/Import** der Konfiguration als YAML-Datei (Backup oder Uebertragung auf einen anderen
  LoxBerry).
- **Update-fest:** die Konfiguration bleibt bei einem Plugin-Update erhalten (wird vor dem
  Update gesichert und danach automatisch wiederhergestellt).
- Mehrsprachig (Deutsch/Englisch).

## Installation

Ueber die LoxBerry-Pluginverwaltung als ZIP-Archiv installieren, oder die Update-URLs aus
`plugin.cfg` fuer automatische Updates nutzen. Das Plugin installiert das Python-Paket
`modbus-proxy` selbststaendig per `pip3` (Internetzugang des LoxBerry beim Installieren/Updaten
erforderlich).

## Deinstallation

Der laufende Dienst wird gestoppt. Das per pip installierte Python-Paket `modbus-proxy` selbst
bleibt bewusst installiert (falls es anderweitig genutzt wird) und kann bei Bedarf manuell per
`pip3 uninstall modbus-proxy` entfernt werden.

## Danksagung

Dieses Plugin basiert strukturell auf dem offiziellen
[`LoxBerry-Plugin-SamplePlugin-V4`](https://github.com/mschlenstedt/LoxBerry-Plugin-SamplePlugin-V4)
und nutzt das Python-Paket [`modbus-proxy`](https://pypi.org/project/modbus-proxy/) (GPLv3) von
Tiago Coutinho. Herzlichen Dank an beide Autoren fuer die Vorarbeit!

## Lizenz

Siehe [LICENSE](LICENSE). Das eingebundene `modbus-proxy`-Paket steht unter GPLv3 und wird nicht
mitgeliefert, sondern zur Installationszeit von PyPI bezogen.
