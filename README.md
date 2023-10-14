# LoxBerry-Plugin_Modbus-Proxy

Dieses Plugin für unseren Loxberry installiert das Paket modbus-proxy. 

In der Konfigurationsdatei unter /opt/loxberry/config/plugins/modbus-proxy/modbus-proxy.yml müssen bisher noch manuell die Modbus-TCP Geräte eingetragen werden. 

Nach Änderung dieser Datei muss der Loxberry neugestartet werden.

Dieses Plugin kann eingesetzt werden, wenn man im Netzwerk von mehreren Modbus-Clients aus, Werte eines Modbus-TCP Gerät Werte abrufen möchte. 

Bekanntlich ist nur eine Verbindung zwischen Modbus-TCP-Client <-> Modbus-TCP-Server möglich. 

Beispiel: Im PV Wechselrichter ist Modbus-TCP aktiviert. Nun möchte man von verschiedenen Clients Modbus-TCP Register des PV Wechselrichters auslesen. Dazu in der modbus-proxy.yml einen Eintrag für den
PV-Wechselrichter erstellen. 

`- modbus:`<br>
    `url: ip_des_PV-Wechselrichters:502  # device url (mandatory)`<br>
    `timeout: 10                         # communication timeout (s) (optional, default: 10)`<br>
    `connection_time: 0.1                # delay after connection (s) (optional, default: 0)`<br>
  `listen:`<br>
    `bind: 0:9009                        # listening address (mandatory)`<br>

Die Modbus-TCP Clients können jetzt eine Modbus-TCP Verbindung auf den Loxberry, auf dem das Loxberry-Plugin_Modbus-Proxy Plugin läuft, aufbauen. Bei der Konfiguration für die Modbus-TCP Verbindung darauf achten, dass
hier jetzt der Port 9009 verwendet werden muss. Modbus-Proxy erkennt die Anfrage und fragt nun die Register des PV-Wechselrichters ab.

Mehr Informationen gibt es hier:
https://pypi.org/project/modbus-proxy/
