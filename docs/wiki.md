<!--
Quelltext für die LoxBerry-Wiki-Seite dieses Plugins (wiki.loxberry.de/plugins/modbus-proxy/start),
in echter DokuWiki-Syntax (nicht Markdown!) - so wie er 1:1 in den DokuWiki-Markup-Editor
eingefügt werden kann. Aufbau nach der offiziellen LoxBerry-Wiki-Vorlage (Beispiel: Dreame-Plugin).

Der Seitenkopf "LoxBerry Wiki - BEYOND THE LIMITS" ist Seiten-Chrome der Wiki-Vorlage selbst
und gehört NICHT in den Seiteninhalt - der beginnt direkt mit der Plugin-Daten-Tabelle.

Syntax-Referenz: Claude_Gedächtnis\LoxBerry\DokuWiki-Syntax.md
-->

^ Plugin-Daten ||
^ Autor | Michael Kaufmann |
^ Logo | {{plugins:modbus-proxy:icon_256.png?128}} |
^ Status | BETA |
^ Version | 1.0.0 |
^ Min. LB Version | 3.0.0 |
^ Release Download | [[https://github.com/blacksun80/LoxBerry-Plugin_Modbus-Proxy/archive/refs/tags/Modbus-Proxy-V1.0.0.zip|Modbus-Proxy-V1.0.0.zip]] |
^ Beschreibung | Stellt eine grafische Oberfläche für den Python-Dienst modbus-proxy bereit: mehrere Modbus-TCP-Clients (z.B. mehrere Loxone Miniserver) können sich einen einzelnen Modbus-TCP-Anschluss teilen. |
^ Sprachen | DE, EN |
^ Diskussion | TODO: Link zum LoxForum-Beitrag einfügen, sobald veröffentlicht |

====== Modbus-Proxy ======

===== Funktion des Plugins =====

Viele Modbus-TCP-Geräte (SPS, Wärmepumpen-Regler, Wechselrichter, Zähler, ...) erlauben nur eine
sehr kleine Anzahl gleichzeitiger Verbindungen. Das Modbus-Proxy-Plugin installiert und verwaltet
den Python-Dienst [[https://pypi.org/project/modbus-proxy/|modbus-proxy]] von Tiago Coutinho,
der sich als Brücke dazwischen setzt: mehrere Clients verbinden sich mit dem Proxy auf dem
LoxBerry, der die Anfragen seriell an das eigentliche Gerät weiterreicht.

**Funktionen:**

  * Konfiguration beliebig vieler Modbus-Geräte per grafischer Oberfläche statt Handbearbeitung
    einer YAML-Datei
  * Speichern schreibt die Konfiguration und startet den Dienst automatisch neu
  * Status-Anzeige: läuft/gestoppt, Prozess-ID, installierte modbus-proxy-Version
  * Je Gerät: erreichbarer Listen-Port, Anzahl verbundener Clients, empfangene/gesendete
    Datenmenge mit Aktivitätsanzeige, ob der Proxy mit dem echten Gerät verbunden ist
  * Start/Stopp/Neustart des Dienstes direkt aus der GUI
  * Log-Datei einsehbar direkt in der GUI (letzte 120 Zeilen) sowie über den LoxBerry-Log-Viewer
  * Log-Level einstellbar (DEBUG/INFO/WARNING/ERROR)
  * Export/Import der Konfiguration als YAML-Datei (Backup oder Übertragung auf einen anderen
    LoxBerry)
  * Update-fest: die Konfiguration bleibt bei einem Plugin-Update erhalten
  * Mehrsprachig (Deutsch/Englisch)

===== Download =====

  * [[https://github.com/blacksun80/LoxBerry-Plugin_Modbus-Proxy/releases/latest|Neueste Version auf GitHub]]
  * Die ZIP-URL kann direkt im LoxBerry Plugin-Manager eingegeben werden.

===== Installation =====

  - Im LoxBerry-Webfrontend **Plugin-Verwaltung → Plugin installieren** aufrufen.
  - Die URL der aktuellen Release-ZIP eintragen, z.B.:
<code>
https://github.com/blacksun80/LoxBerry-Plugin_Modbus-Proxy/archive/refs/tags/Modbus-Proxy-V1.0.0.zip
</code>
  - Installation starten und abwarten. Das Plugin installiert automatisch das Python-Paket
    ''modbus-proxy'' per ''pip3''.

**Voraussetzungen:**

  * LoxBerry 3.0.0 oder neuer
  * Internetzugang beim Installieren/Updaten (für die pip-Installation von modbus-proxy)
  * Kein externes Konto/keine Cloud nötig - die Verbindung zum Modbus-Gerät läuft ausschließlich
    im lokalen Netzwerk

===== Konfigurationsoptionen =====

==== Plugin-Seite öffnen ====

Die Plugin-Seite ist unter **LoxBerry → Plugins → Modbus-Proxy** erreichbar. Sie enthält drei
Reiter:

  * **Status & Konfiguration** — Dienststatus, Verbindungsübersicht je Gerät, Geräte anlegen/
    bearbeiten/entfernen
  * **Log** — Log-Level einstellen, aktuelle Logdatei einsehen
  * **Backup/Restore** — Konfiguration exportieren/importieren

==== Status & Konfiguration ====

Der Statusbereich zeigt:

^ Farbe ^ Bedeutung ^
| 🟢 grün | Dienst läuft / Gerät erreichbar bzw. verbunden |
| 🔴 rot | Dienst gestoppt / Gerät nicht erreichbar |
| ⚪ grau | keine Aktivität in den letzten 10 Sekunden |
| 🔵 blau | gerade Daten empfangen/gesendet (Aktivitätsanzeige) |

Je konfiguriertem Gerät:

^ Feld ^ Bedeutung ^
| Ziel-Adresse (Host:Port) | Adresse des echten Modbus-TCP-Geräts, z.B. ''192.168.1.10:502'' (Standard-Port 502) |
| Listen-Adresse (Host:Port) | Adresse, unter der der Proxy für die Clients erreichbar ist - **nicht** die Geräteadresse. ''0'' als Host = alle Netzwerkschnittstellen des LoxBerry; die Portnummer dahinter tragen die Clients in ihrer eigenen Modbus-Konfiguration ein |
| Timeout (s) | Wie lange auf eine Antwort des echten Geräts gewartet wird |
| Verbindungsverzögerung (s) | Wartezeit nach dem Verbindungsaufbau zum Gerät, bevor die erste Anfrage gesendet wird (nur bei Verbindungsfehlern direkt nach dem Connect erhöhen) |
| Unit-ID-Umleitung (optional) | Leitet eine vom Client angefragte Unit-ID auf eine andere Unit-ID am echten Gerät um, Format ''1:0, 2:1'' |

Über **Speichern** wird die Konfiguration geschrieben und der Dienst automatisch neu gestartet.

==== Log ====

Legt fest, wie ausführlich der Dienst protokolliert (DEBUG/INFO/WARNING/ERROR) und zeigt die
letzten 120 Zeilen der Logdatei direkt in der GUI. Über den Button lässt sich das vollständige
Log im LoxBerry-eigenen Log-Viewer öffnen.

==== Backup/Restore ====

Lädt die aktuelle Konfiguration als YAML-Datei herunter bzw. spielt eine zuvor exportierte Datei
wieder ein (die bisherige Konfiguration wird dabei als ''modbus-proxy.yml.bak'' gesichert).

===== Einrichtung in der Loxone Config Software =====

Ein per Modbus-Proxy bereitgestelltes Gerät wird in Loxone genauso eingebunden wie das Gerät
selbst - nur mit anderer Adresse:

  - In Loxone Config einen **Modbus-TCP-Server** anlegen.
  - Als Adresse die **IP-Adresse des LoxBerry** eintragen.
  - Als Port die in der Plugin-GUI konfigurierte **Listen-Adresse** (z.B. ''9010'') eintragen -
    nicht den Port des echten Geräts.
  - Register/Datenpunkte wie gewohnt anlegen; der Proxy reicht alle Anfragen transparent an das
    echte Gerät weiter.

Mehrere Miniserver bzw. mehrere Clients können sich so denselben Modbus-TCP-Anschluss teilen,
ohne dass sich die Verbindungen gegenseitig stören.

===== Fragen stellen und Fehler melden =====

  * **GitHub Issues:** [[https://github.com/blacksun80/LoxBerry-Plugin_Modbus-Proxy/issues]]
  * **LoxBerry Forum:** TODO: Link zum LoxForum-Beitrag einfügen, sobald veröffentlicht
