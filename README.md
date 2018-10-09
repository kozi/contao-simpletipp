# Simpletipp - contao-simpletipp #

Eine Tippspiel-Erweiterung für das CMS [Contao][] auf Basis von [OpenLigaDB][].

[Contao]: https://contao.org/
[OpenLigaDB]: http://openligadb.de/

## Anleitung ##

Nach der Installation der Erweiterung muss über den Menüpunkt **Tipprunden** eine neue Tipprunde erstellt werden.
In der Tipprunde wird festgelegt welche Liga getippt wird und welche Mitglieder teilnehmen.
Hat man noch keine Mitgliedergruppe erstellt so muss man dies noch vorher erledigen.  
In der Seitenstruktur muss man dann im _Startpunkt der Webseite_ die erstellte Tipprunde auswählen. Diese Tipprunde wird dann für alle Module, die sich auf den Seiten dieser Webseite befinden, verwendet. 

Mit den folgenden Modulen kann man nun beginnen eine Seite für die Tipprunden zu erstellen.

* **Simpletipp Benutzerauswahl**:
    Mit diesem Modul kann man eine Benutzerauswahl generieren. Benötigt wird diese um einen Benutzer
    auszuwählen, dessen Daten angezeigt werden sollen. Die Anzeige der anderen Module wird damit
    also beeinflusst. Im Moment ist dies nur für das Modul _Simpletipp Spiele_ relevant.

* **Simpletipp Kalender**:
    Mit diesem Modul kann ein iCal Kalender mit den Spielen einer Tipprunde erstellt werden.
    Das Modul muss als einziges auf einer Seite eingebunden werden. **Details folgen**.

* **Simpletipp Telegram**:
    Mit diesem Modul kann man über einen Chat mit einem [Telegram-Bot](https://core.telegram.org/bots)
    einige der Tippspiel-Funktionen ansteuern. Dazu muss ein Telegram-Bot erstellt werden und als
    [Webhook-URL](https://core.telegram.org/bots/api#setwebhook) die Seite angegeben werden die dieses Modul enthält.
    Die URL muss als Parameter auch einen geheimen Token enthalten, den man im Modul festlegt.
    Der Link für die Mitglieder wird über den InsertTag {{telegram_chat::BOTNAME}} erzeugt. Mit diesem Link wird ein
    Chat mit dem Bot gestartet.

* **Simpletipp Nicht getippt**:
    Einfache Auflistung der Mitglieder, die das nächste Spiel der gewählten Tipprunde noch nicht getippt haben.

* **Simpletipp Pokal**:
    Eine Pokalrunde für das Tippspiel. **Details folgen**.

* **Simpletipp Einzelnes Spiel**:
    Das Modul zeigt Informationen über ein einzelnes Spiel.

* **Simpletipp Spiele**:
    Diese Modul zeigt die Spiele der gewählten Tipprunde an.

* **Simpletipp Rangliste**:
    Dieses Modul generiert die Rangliste der gewählten Tipprunde.

* **Simpletipp - Wettbewerbstabelle**:
    Generiert eine Tabelle des Wetbewerbs. Im Moment nur für die Bundesliga getestet.

> Die Anleitung ist im Moment natürlich noch sehr dürftig. Ich werde aber versuchen diese zu erweitern und mit ein paar Screenshots zu versehen.
