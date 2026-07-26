# LoxBerry-Plugin: Modbus-Proxy

Ein [LoxBerry](https://www.loxberry.de/)-Plugin mit grafischer Oberfläche für den Python-Dienst
[**modbus-proxy**](https://pypi.org/project/modbus-proxy/) von Tiago Coutinho.

Viele Modbus-TCP-Geräte (SPS, Wärmepumpen-Regler, Zähler, ...) erlauben nur eine sehr kleine
Anzahl gleichzeitiger Verbindungen. modbus-proxy setzt sich als Brücke dazwischen: mehrere
Clients (z.B. ein Loxone Miniserver, eine Visualisierung, ein Skript) verbinden sich mit dem
Proxy, der die Anfragen seriell an das eigentliche Gerät weiterreicht.

## Funktionen dieses Plugins

- **Konfiguration per GUI** statt Handbearbeitung der YAML-Datei: beliebig viele Modbus-Geräte
  mit Ziel-Adresse, Timeout, Verbindungsverzögerung, Listen-Adresse und optionaler
  Unit-ID-Umleitung.
- **Speichern** schreibt die Konfiguration und startet den Dienst automatisch neu, damit die
  Änderungen sofort aktiv sind.
- **Status-Anzeige** (läuft/gestoppt, PID, installierte modbus-proxy-Version), aktualisiert sich
  automatisch alle 5 Sekunden. Pro Gerät wird angezeigt, ob der Listen-Port erreichbar ist, wie
  viele Clients verbunden sind, wie viele Daten empfangen/gesendet wurden (mit Anzeige, ob gerade
  Daten fließen) und ob der Proxy aktuell mit dem Modbus-Gerät verbunden ist.
- **Start/Stopp/Neustart** des Dienstes direkt aus der GUI.
- **Export/Import** der Konfiguration als YAML-Datei (Backup oder Übertragung auf einen anderen
  LoxBerry).
- **Update-fest:** die Konfiguration bleibt bei einem Plugin-Update erhalten (wird vor dem
  Update gesichert und danach automatisch wiederhergestellt).
- Mehrsprachig (Deutsch/Englisch).

## Installation

Über die LoxBerry-Pluginverwaltung als ZIP-Archiv installieren, oder die Update-URLs aus
`plugin.cfg` für automatische Updates nutzen. Das Plugin installiert das Python-Paket
`modbus-proxy` selbstständig per `pip3` (Internetzugang des LoxBerry beim Installieren/Updaten
erforderlich).

## Deinstallation

Der laufende Dienst wird gestoppt. Das per pip installierte Python-Paket `modbus-proxy` selbst
bleibt bewusst installiert (falls es anderweitig genutzt wird) und kann bei Bedarf manuell per
`pip3 uninstall modbus-proxy` entfernt werden.

## Dokumentation

Ausführliche Dokumentation (Konfigurationsoptionen, Einrichtung in der Loxone Config Software)
im [LoxBerry-Wiki](https://wiki.loxberry.de/plugins/modbus-proxy/start).

## Lizenz

Siehe [LICENSE](LICENSE). Das eingebundene `modbus-proxy`-Paket steht unter GPLv3 und wird nicht
mitgeliefert, sondern zur Installationszeit von PyPI bezogen.
