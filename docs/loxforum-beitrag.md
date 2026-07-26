<!--
Entwurf für den Startbeitrag im LoxForum (Bereich Projektforen > LoxBerry > Plugins),
im Stil des Homebridge-Plugin-Beitrags von blacksun:
https://www.loxforum.com/forum/projektforen/loxberry/plugins/337099-homebridge-plugin-zur-einfacher-installation-von-homebridge-auf-dem-loxberry
Kurz, persönlich, Funktion + Links, Bitte um Rückmeldung - keine ausführliche
Feld-für-Feld-Doku (die steht im Wiki, siehe docs/wiki.md).
-->

# Modbus-Proxy - Plugin zur einfachen gemeinsamen Nutzung eines Modbus-TCP-Geräts

Hallo zusammen,

ich möchte euch mein neues Plugin **Modbus-Proxy** vorstellen.

Viele Modbus-TCP-Geräte (SPS, Wärmepumpen-Regler, Wechselrichter, Zähler, ...) erlauben nur eine
einzige bzw. sehr wenige gleichzeitige Verbindungen. Das Plugin installiert und verwaltet den
Python-Dienst [modbus-proxy](https://pypi.org/project/modbus-proxy/), der sich als Brücke
dazwischen setzt: mehrere Clients (z.B. mehrere Loxone Miniserver oder eine Visualisierung)
verbinden sich mit dem Proxy auf dem LoxBerry, statt direkt mit dem Gerät - der Proxy reicht die
Anfragen dann seriell an das eigentliche Gerät weiter.

Die komplette Konfiguration läuft über eine grafische Oberfläche (beliebig viele Geräte, Status
je Verbindung inkl. Datenverkehr, Log direkt einsehbar, Export/Import als Backup) - eine manuelle
Bearbeitung der YAML-Konfigurationsdatei ist nicht mehr nötig.

**Download & Doku:**
- GitHub: https://github.com/blacksun80/LoxBerry-Plugin_Modbus-Proxy
- Wiki: https://wiki.loxberry.de/plugins/modbus-proxy/start
- Aktuelle Releases: https://github.com/blacksun80/LoxBerry-Plugin_Modbus-Proxy/releases

Über Rückmeldungen und Tests freue ich mich!
