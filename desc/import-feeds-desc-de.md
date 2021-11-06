Mit dem Modul "Import Feeds" können Sie die Daten für eine beliebige Entität im AtroCore-System und direkte Relationen zu diesen Daten importieren, zB es ist möglich die Produktdaten zusammen mit den dazugehörigen Kategorien zu importieren. Sie können mehrere Importfeeds erstellen, konfigurieren und verwenden.

Mit Hilfe des Moduls "Feeds importieren" kann der Datenimport auf zwei Arten erfolgen:

- **manuell** – durch direkte Verwendung eines konfigurierten Import-Feeds
- **automatisch** – nach geplantem Job.

Sie können nur dann einen geplanten Job für Ihre Importaufgaben erstellen, wenn ein entsprechender Importfeedtyp in Ihrem System verfügbar ist. Solche Import-Feed-Typen ermöglichen, die Daten per URL herunterzuladen, einen REST-API-Aufruf auszuführen, eine Abfrage direkt aus der Datenbank auszuführen (dafür werden Premium-Module benötigt). Mit diesem kostenlosen Modul können Sie die Daten manuell aus CSV-Dateien importieren. Sie können auch Ihre eigenen Import-Feed-Typen programmieren lassen.

## Die folgenden Module erweitern die Funktionalität der Import-Feeds

- Import Feeds: Rollback – ermöglicht das Rollback des letzten Imports mit der vollständigen Datenwiederherstellung
- Import Feeds: Datenbanken – ermöglicht den Import von Daten aus MSSQL-, MySQL-, Oracle-, HANA-Datenbanken
- Import Feeds: JSON und XML – ermöglicht den Import von Daten aus JSON- und XML-Dateien
- Import Feeds: URL – ermöglicht den Import von Daten per URL aus CSV-, JSON- und XML-Dateien
- Import Feeds: REST API – ermöglicht den Datenimport über die REST API
- Connector – orchestriert mehrere Import- und Export-Feeds, um einen komplexen Datenaustausch zu automatisieren

## Funktionen für den Administrator
Nach der Modulinstallation werden in Ihrem System zwei neue Entitäten erstellt - `Import Feeds` und `Import-Ergenisse`. Über `Administration > System > User Interface` können Sie diese Elemente zur Navigation Ihres Systems hinzufügen, falls es nicht automatisch geschehen ist.
![Feeds importieren hinzufügen](_assets/import-feeds-add.jpg)

### Zugangsrechte
Um die Erstellung, Bearbeitung, Nutzung und Entfernung des Import Feeds für andere Nutzer zu ermöglichen, konfigurieren Sie die entsprechenden Zugangsrechte zu den Entitäten `Import Feeds` und `Import-Ergebnisse` für die gewünschte Benutzer-/Team-/Portalbenutzerrolle auf der Seite `Administration > Rollen > 'Rollenname'`:
![Import role cfg](_assets/import-role-cfg.jpg)
Bitte beachten Sie, dass für einen Nutzer mindestens die Gewährung des Leserechts auf `Import-Feeds` erforderlich ist, damit er die Import Feeds auszuführen kann.   

## Funktionen für den Nutzer
Nachdem das Modul „Import Feeds“ vom Administrator installiert und konfiguriert wurde, kann der Nutzer mit Import Feeds entsprechend seiner Rollenrechte arbeiten, die vom Administrator vordefiniert wurden.

## Erstellung von Import Feeds
Um einen neuen Import Feed zu erstellen, klicken Sie im Navigationsmenü auf `Import Feeds` und dann auf den Button `Import Feed erstellen`. 

> Wenn es im Navigationsmenü keine `Import-Feeds`-Option gibt, wenden Sie sich bitte an Ihren Administrator.

Das Standardformular für die Importfeederstellung erscheint:
![Import feed creation](_assets/import-feeds-create.png)

Füllen Sie hier die Pflichtfelder aus und wählen den Import-Feed-Typ aus der entsprechenden Drop-down-Liste aus. Dieses Modul fügt den Import-Feed-Typ **CSV Datei** hinzu. 
Klicken Sie auf den Button `Speichern`. Der neue Datensatz wird zu der Import-Feeds-Liste hinzugefügt. Sie können es sofort auf Detailansichtsseite konfigurieren oder es später machen.

### Übersicht

Im Abschnitt Übersicht können Sie die wichtigsten Feedparameter (Name, Aktion, Aktivität usw.) definieren:
![Feed-cfg importieren](_assets/import-feeds-create-overview.png)

Folgende Einstellungen stehen hier zur Verfügung:

- **Aktiv** – Aktivität des Import-Feeds, Import ist nicht möglich, wenn dieses Kontrollkästchen nicht aktiviert ist
- **Name** – Importfeedname
- **Beschreibung** – Beschreibung des Import-Feeds, kann als Erinnerung für die Zukunft oder als Hinweis für andere Benutzer des angegebenen Import-Feeds verwendet werden
- **Typ** – der Import-Feed-Typ, kann später nicht geändert werden
- **Aktion** – Definieren Sie die Aktion, die beim Datenimport im System ausgeführt werden soll:
     - *Nur erstellen* – es werden nur neue Datensätze angelegt, bestehende Datensätze werden nicht aktualisiert
     - *Nur Update* – bestehende Datensätze werden aktualisiert, neue Datensätze werden nicht erstellt
     - *Create & Update* – neue Datensätze werden erstellt und die bestehenden Datensätze werden aktualisiert.


### Dateieigenschaften 

Die Parameter der Importdatei werden im Panel `FILE PROPERTIES` konfiguriert:

![Feed-CFG-Datei importieren](_assets/import-feeds-create-file-properties.png)

- **Datei** – hier können Sie die zu importierende Datei oder deren gekürzte Version hochladen, die für die Konfiguration verwendet wird. Die Datei sollte UTF-8-kodiert sein.
- **Kopfzeile** – Aktivieren Sie die Checkbox, wenn die Importdatei Spaltennamen hat oder lassen Sie diese leer, wenn die zu importierende Datei keine Kopfzeile mit Spaltennamen hat.
- **Tausender-Trennzeichen** – Definieren Sie das Symbol, das als Tausender-Trennzeichen verwendet wird. Dieser Parameter ist optional. Es werden auch die Zahlenwerte ohne Tausendertrennzeichen importiert (zB beide Werte 1234,34 und 1.234,34 werden importiert, wenn "." als Tausendertrennzeichen definiert ist).
- **Dezimalzeichen** – Wählen Sie das verwendete Dezimalzeichen aus, normalerweise sollte hier `.` oder `,` gesetzt werden.
- **Feldtrennzeichen** – Wählen Sie das bevorzugte Feldtrennzeichen, das in der CSV-Importdatei verwendet werden soll, mögliche Werte sind `,`, `;`,`\t`, `|`.
- **Textqualifizierer** – Wählen Sie das Trennzeichen der Werte innerhalb einer Zelle: Es können einfache oder doppelte Anführungszeichen ausgewählt werden.





### Einfache Typ-Einstellungen
Um die Bearbeitung der Parameter im Panel `EINFACHE TYP-EINSTELLUNGEN` zu ermöglichen, klicken Sie auf den `Bearbeiten`-Button auf der Detail-Ansichtsseite des aktuellen Import Feeds und konfigurieren Sie folgende Einstellungen:

![Simple type settings](_assets/simple-type-settings.jpg)

- **Entität** – wählen Sie aus der Drop-down-Liste der im System verfügbaren Entitäten den gewünschten Entitätstyp aus, für den dieser Import Feed verwendet werden soll.
- **Feldwert Begrenzer** – geben Sie das bevorzugte Trennzeichen der Werte im Feld ein. Das Standardsymbol ist `;` (Semikolon).
- **ID** – wählen Sie den Namen des Datenfeldes aus, das als Identifikator für die Aktualisierung im angegebenen Import Feed verwendet wird. Der `ID`-Parameter wird hier angezeigt, wenn entweder die Aktion `Nur aktualisieren` oder `Aktualisieren und erstellen` im Panel [`ÜBERBLICK`](#überblick)  definiert ist. Außerdem ist das das Pflichtfeld für die Aktion `Nur aktualisieren`.  

Bitte beachten Sie, dass `Feld-Trennzeichen` und `Feldwert-Begrenzer` verschieden sein sollen. 

### Konfigurator

Die Konfiguration der Entitätsfelder wird im Panel `KONFIGURATOR` entweder auf der Detail-Ansichtsseite  oder auf der Seite der Bearbeitungsansicht des Import Feeds durchgeführt. Standardmäßig werden dort die Pflichtfelder des Entitätstyps angezeigt, der im Panel `EINFACHE TYP-EINSTELLUNGEN` definiert ist. Abhängig von dieser Auswahl enthält das Panel `KONFIGURATOR` verschiedene Felder. Für [Produkte](https://atropim.com/help/products) sieht dieses Panel wie folgt aus:

![Configurator panel](_assets/configurator-panel.jpg)

Um das im Panel `KONFIGURATOR` angezeigte Entitätsfeld zu ändern, verwenden Sie die Option `Bearbeiten` aus dem Menü für einzelne Datensatzaktionen und nehmen Sie die gewünschten Änderungen im Bearbeitungs-Pop-up vor, das für das entsprechende Entitätsfeld angezeigt wird:

![Field editing](_assets/field-editing.jpg) 

Bitte beachten Sie, dass der Import nach ID und Code eine empfohlene Einstellung für das Entitätsfeld ist, das im Import Feed verwendet werden soll.

#### Hinzufügen von Entitätsfeldern 
Es ist möglich, weitere *Entitätsfelder* für den Import hinzuzufügen. Wählen Sie dazu die Option `Entitätsfeld hinzufügen` aus dem Drop-down-Menü zum Hinzufügen. Folgendes Erstellungs-Pop-up wird angezeigt:

![Entity field adding](_assets/add-field.jpg)

Wählen Sie hier das Feld aus der Drop-down-Liste aller Felder, die im System für die angegebene Entität verfügbar sind, definieren Sie seinen Standardwert und/oder wählen Sie seine Datei-Spalte aus.  Bestimmen Sie zudem, ob der Datenimport nach ID, Name oder Code durchgeführt werden soll (für die Felder, wo diese Auswahl verfügbar ist).

Bitte beachten Sie, dass entweder das Feld `Datei-Spalte` oder ` Standardwert` ausgefüllt werden soll. Andernfalls kann das aktuelle Entitätsfeld nicht erstellt werden:

![Field creation error](_assets/field-creation-error.jpg)

Entitätsfelder der Typen `Currency` und `Unit` sowie [mehrsprachige Felder](https://atropim.com/de/shop/multi-languages) können auch zu Import Feeds hinzugefügt werden:

![Field creation unit](_assets/field-creation-unit.jpg)

Für das Feld `Product categories` können Sie auch die Umfangsebene auswählen:

![Product category scope](_assets/product-category-scope.jpg)

Wenn die `Channel`-Umfangsebene definiert ist, sollten Sie auch im entsprechenden Feld den benötigten Kanal auswählen, der für die Produktkategorien verwendet werden soll. 

Bitte beachten Sie, dass Entitätsfelder nur einmal zum Import-Feed-Datensatz hinzugefügt werden können. Eine Ausnahme bildet einzig das Feld `Product categories`. Dies kann so oft wie nötig hinzugefügt werden, aber mit unterschiedlichen Umfangsebenen und unterschiedlichen Kanälen:

![Product categories](_assets/product-categories.jpg)

Wenn das Entitätsfeld zu dem Konfigurator hinzugefügt wurde, wird es zu der Drop-down-Liste `ID` im Panel [`EINFACHE TYP-ENEINSTELLUNGEN`](#einfache-typ-einstellungen) hinzugefügt.

#### Hinzufügen von Produktattributen 
Das Modul „Import Feeds“ ermöglicht es ebenfalls, [Produktattribut](https://atropim.com/help/products)-Werte zu importieren, darunter auch [mehrsprachige Attribute](https://atropim.com/de/shop/multi-languages#mehrsprachige-attribute). Sie können zum Import Feed im `KONFIGURATOR`-Panel mit Hilfe der Option `Produktattribut hinzufügen` aus dem Drop-down-Menü hinzugefügt werden:

![Product attribute adding](_assets/add-attribute.jpg)

Bitte beachten Sie, dass diese Funktion nur dann verfügbar ist, wenn das [AtroPIM-Modul](https://atropim.com/help/what-is-atropim) zusammen mit dem Modul "Import Feeds" installiert ist.

Wählen Sie im angezeigten Erstellungs-Pop-up das Attribut aus der Liste der vorhandenen Attribute aus, definieren Sie dessen Standardwert und/oder wählen Sie dessen Datei-Spalte aus. Außerdem definieren Sie die Umfangsebene des Attributes - `Global` oder `Channel`. 

Vergewissern Sie sich, dass das Feld `Datei-spalte` oder `Standardwert` ausgefüllt ist. Andernfalls wird das nötige Produktattribut nicht erstellt.

Bitte beachten Sie, dass dasselbe Produktattribut mehrmals zum Import-Feed-Datensatz hinzugefügt werden kann, aber mit unterschiedlichen Umfangsebenen (`Global` oder `Channel`) und unterschiedlichen Kanälen.

#### Hinzufügen von Produktbildern 
Mit Hilfe des Moduls "Import Feeds" können des Weiteren *Produktbilder* zum Import hinzugefügt werden. Wählen Sie dazu im Panel `Konfigurator` die Option `Produktbild hinzufügen` aus dem Drop-down-Menü : 

![Product image adding](_assets/add-product-image.jpg)

Bitte beachten Sie, dass diese Funktion nur dann verfügbar ist, wenn das AtroPIM-Modul zusammen mit dem Modul "Import Feeds" installiert ist.

Wählen Sie im angezeigten Erstellungs-Pop-up die Datei-Spalte für den Bildimport aus und/oder hängen Sie  die lokal gespeicherte Bilddatei an, die als Standardwert für Import verwendet werden soll. Definieren Sie auch die Umfangsebene für das Bild - `Global` oder `Channel`. 

Stellen Sie sicher, dass mindestens das Feld `Datei-spalte` ausgefüllt ist. Ansonsten wird das nötige Produktbild nicht erstellt.

Nachdem Sie die Datensätze [Entitätsfeld](#hinzufügen-der-entitätsfelder), [Produktattribut](#hinzufügen-der-produktattribute) und [Produktbild](#hinzufügen-der-produktbilder) zum Import Feed hinzugefügt haben, können Sie diese über die entsprechenden Optionen im Menü für einzelne Datensatzaktionen bearbeiten oder entfernen:

![Single record menu](_assets/single-record-menu.jpg)

Wenn Sie weitere Fragen zur Konfiguration des Import Feeds haben, können Sie gerne jederzeit mit uns [Kontakt aufnehmen](https://atropim.com/de/kontakt).

## Import-Feed-Ausführung  
Um den Import der Daten über den aktiven Import Feed zu starten, wählen Sie die Option `Import durchführen` im Menü "Aktionen" auf der Detail-Ansichtsseite des Import Feeds oder im Menü für einzelne Datensatzaktionen auf der Seite der Listenansicht von "Import Feeds":

![Run import option](_assets/run-import-option.jpg)

Hängen Sie im angezeigten Pop-up die CSV-Datei mit den zu importierenden Daten an und klicken Sie auf den Button `Import durchführen`, um den Vorgang zu starten:

![Run import pop-up](_assets/run-import-popup.jpg)

Achten Sie bitte darauf, dass die zu importierende Datei mit der Beispieldatei übereinstimmen muss, die für den bestimmten Import Feed definiert ist. Andernfalls wird die folgende Fehlermeldung angezeigt:

![Wrong file error](_assets/wrong-file-error.jpg) 

Wenn der Import gestartet ist, werden seine Details und aktueller Status im Pop-up des Queue Managers angezeigt, das automatisch erscheint:

![Queue manager](_assets/queue-manager.jpg)

Bitte beachten Sie, dass wenn die Anzahl der Datensätze in der zu importierenden Datei den [Grenzwert](#konfiguration-von-import-feeds) überschreitet, der im Panel `ÜBERBLICK` des entsprechenden Import Feeds definiert ist, der Import Job gemäß dem Grenzwert in entsprechende Teile aufgeteilt wird.

### Importergebnisse
Informationen über abgeschlossene Import Jobs werden im Panel `IMPORT RESULTS` angezeigt. Dieses Panel ist  während des Vorgangs der [Erstellung](#erstellung-von-import-feeds) eines Import Feeds zunächst leer, wird jedoch ausgefüllt, nachdem der Datenimport über den bestimmten Import Feed durchgeführt wurde.

Die Ergebnisse der Daten-Importvorgänge können auf zwei Arten angezeigt werden:

- im **Panel `IMPORT RESULTS` ** des Import Feeds - die Details zu den Importvorgängen, die über den aktuell geöffneten Import Feed durchgeführt werden: 

  ![Import results panel](_assets/import-results-panel.jpg)


- auf der **Seite der Listenansicht von Importergebnissen** - Details zu allen Importvorgängen, die im System über Import Feeds durchgeführt werden:

  ![Import results list](_assets/import-results-list.jpg)

Die Details der Importergebnisse enthalten folgende Informationen: 

- **Name** – der Name des Datensatzes mit den Importergebnissen, der basierend auf dem Datum und der Zeit des Starts des Importvorgangs automatisch generiert wird. Klicken Sie auf den Namen des Datensatzes mit den Importergebnissen, um dessen Detail-Ansichtsseite zu öffnen.
- **Import Feed** – der Name des Import Feeds, der für den Importvorgang verwendet wird.
- **Importierte Datei** – der Name der Datei (CSV), die für den Importvorgang verwendet wird. 
- **Status** – der aktuelle Status des Importvorgangs. 
- **Wiederhergestellt** – die Angabe, ob der bestimmte Datensatz mit dem Importergebniss wiederhergestellt wurde (die Checkbox ist gesetzt) oder nicht. Verfügbar nur auf der Seite der Listenansicht  der Importergebnisse. 
- **Start** – das Datum und die Zeit des Starts des Importvorgangs. 
- **Ende** – das Datum und die Zeit der Beendigung des Importvorgangs.
- **Erstellt** – die Anzahl der Datensätze, die als Ergebnis des durchgeführten Importvorgangs erstellt wurden. Klicken Sie auf diesen Wert für das gewünschte Importergebnis, um die Seite mit der Listenansicht der entsprechenden Entitätsdatensätze zu öffnen, welche nach dem angegebenen Importergebnis gefiltert sind, d.h. mit dem angewendeten Filter `Erstellt durch Import`. 
- **Aktualisiert** – die Anzahl der Datensätze, die als Ergebnis des durchgeführten Importvorgangs aktualisiert wurden. Klicken Sie auf diesen Wert für das gewünschte Importergebnis, um die Seite mit der Listenansicht der entsprechenden Entitätsdatensätze zu öffnen, welche nach dem angegebenen Importergebnis gefiltert sind, d.h. mit dem angewendeten Filter `Aktualisiert durch Import`.
- **Fehler** - die Anzahl der Fehler, soweit vorhanden, die während des Importvorgangs aufgetreten sind. 
- **[Fehlerdatei](#fehlerdatei)** – der Name der CSV-Datei, die nur Zeilen mit Fehlern enthält. Die Fehlerdatei wird auf Grund des Namens der importierten Datei automatisch generiert. 

Folgende Statusmeldungen können auftreten: 

- **Running** – für den aktuell laufenden Import Job. 


- **Pending** –  für den Import Job, der als nächster ausgeführt werden soll. 
- **Success** – für den erfolgreich abgeschlossenen Import Job (unabhängig davon, ob er Fehler enthält). 
- **Failed** – für den Import Job, der wegen einiger technischer Probleme nicht ausgeführt werden konnte. 

#### Details

Um die Details des Datensatzes mit den Importergebnissen anzusehen, klicken Sie auf dessen Namen im Panel `IMPORT RESULTS` auf der Detail-Ansichtsseite des gewünschten Import Feeds oder in der Liste der Importergebnisse. Es wird die Detail-Ansichtsseite des entsprechenden Datensatzes angezeigt:

![Import result details](_assets/import-result-details.jpg)

Die Fehlermeldungen, falls solche vorhanden sind, werden im Panel `ERRORS LOG` auf dieser Seite angezeigt:  

![Errors log](_assets/errors-log.jpg)

Um die vollständige Liste der Fehlerdatensätze anzuschauen, nutzen Sie den Befehl `Vollständige Liste anzeigen` im Aktionsmenü. Die Liste der Logs der Importergebnisse wird mit Datensätzen angezeigt, die nach dem angegebenen Importergebnis gefiltert sind:

![Import result logs](_assets/import-result-logs.jpg)

Alternativ können Sie die Details des Datensatzes mit den Importergebnissen in dem Pop-up ansehen, welches angezeigt wird, wenn Sie die Option `Ansehen` aus dem Menü für einzelne Datensatzaktionen für den gewünschten Datensatz auf der Seite der Listenansicht  von Importergebnissen nutzen. Das Gleiche gilt für das Panel `IMPORT RESULTS`  des aktuell geöffneten Import Feeds:
![Import result pop-up](_assets/import-result-popup.jpg)
Bitte beachten Sie, dass Sie die importierte Datei von jeder [Interface-Seite](https://atropim.com/help/views-and-panels) herunterladen können, wo ihr Name anklickbar ist.

#### Fehlerdatei
Alle Daten der erforderlichen Entitätsfelder werden zu der Importdatei hinzugefügt. Falls ein Pflichtfeld in dem zum Import Feed hinzugefügten Entitätsdatensatz leer (d. h. nicht ausgefüllt) ist oder die eingegebenen Daten nicht validiert werden (z. B. stimmen die eingegebenen Daten mit dem Feldtyp nicht überein, beispielsweise Text anstelle von numerischen Werten in den Feldern von Typen `Boolean`,` Currency`, `Float`,` Unit`, sodass der eingegebene Link im System nicht existiert usw.), wird dieser Datensatz nicht importiert. Stattdessen wird er zu der Fehlerdatei hinzugefügt - einer separat generierten CSV-Datei, die nur Zeilen der Datensätze mit Fehlern enthält.

Sie können die Fehlerdatei von jeder [Interface-Seite](https://atropim.com/help/views-and-panels) herunterladen, wo ihr Name anklickbar ist, (z.B. Seite der Listenansicht von Importergebnissen, Seite der Detailansicht/ schnellen Detailansicht von Importergebnissen usw.), die Daten in den definierten Zeilen korrigieren und den Importvorgang erneut durchführen. Dabei sollen Sie die korrigierte Fehlerdatei als die zu importierende Datei nutzen. 

#### Datenwiederherstellung 
Das Modul "Import Feeds" unterstützt die Wiederherstellung für einzelne Datensätze der Importergebnisse in den Vor-Import-Zustand. Dafür wählen Sie die Option `Wiederherstellen` aus dem Menü für einzelne Datensatzaktionen für den gewünschten Datensatz mit den Importergebnissen auf der Detail-Ansichtsseite des Import Feeds:
![Restore option](_assets/restore-option.jpg)

Klicken Sie in der angezeigten Bestätigungsnachricht auf den Button `Wiederherstellen`, um den Vorgang zu starten, oder auf `Abbrechen`, um den Vorgang abzubrechen. Das Pop-up des Queue Managers wird automatisch angezeigt:
![Restore process](_assets/queue-manager-restore.jpg)

Infolgedessen verschwindet der 'wiederhergestellte' Datensatz mit den Importergebnissen aus dem Panel `IMPORT RESULTS`. Auf der Seite der Listenansicht  von Importergebnissen wird dabei die Checkbox  `Wiederhergestellt` für den Datensatz mit 'ursprünglichen' Importergebnissen aktiviert:
![Restored checkbox](_assets/restored-checkbox.jpg)

Bitte beachten Sie, dass die Datenwiederherstellung nur für das neueste Importergebnis durchgeführt wird. Die Zurücksetzung der Importergebnisse erfolgt schrittweise, d.h., dass das neueste Importergebnis nur zur vorherigen Version zurückgesetzt werden kann.

## Vorgänge und Aktionen des Moduls “Import Feeds”
Import Feed Datensätze können bei Bedarf dupliziert und entfernt werden.
Um den vorhandenen Import-Feed-Datensatz zu *duplizieren*, nutzen Sie die entsprechende Option aus dem Aktionsmenü auf der gewünschten Detail-Ansichtsseite des Import-Feed-Datensatzes:
![Duplicate feed](_assets/duplicate-feed.jpg)

Sie werden zur Import Feed Erstellungsseite weitergeleitet und alle Werte des zuletzt ausgewählten Import Feed Datensatzes werden in die leeren Felder des neu zu erstellenden Feed Datensatzes kopiert.

Um den Import Feed Datensatz zu *entfernen*, nutzen Sie die entsprechende Option aus dem Aktionsmenü auf der gewünschten Detail-Ansichtsseite des Import Feed Datensatzes oder aus dem Menü für einzelne Datensatzaktionen auf der Ansichtsseite der Import Feeds Liste:
![Remove feed](_assets/remove-import-feed.jpg)

Klicken Sie in der angezeigten Bestätigungsnachricht auf den Button `Entfernen`, um den Vorgang abzuschließen.

Das Modul "Import Feeds" unterstützt auch allgemeine AtroCore-Massenaktionen, die für mehrere ausgewählte Import Feed Datensätze gelten, d.h. Datensätze mit festgelegten Checkboxen. Diese Aktionen finden Sie im entsprechenden Menü auf der Ansichtsseite der Import Feeds Liste:
![Mass actions](_assets/mass-actions.jpg)

- **Löschen** – um die ausgewählten Import Feed Datensätze zu entfernen (Mehrfachlöschung).
- **Zusammenführen** – um die ausgewählten Import Feed Datensätze zusammenzuführen.
- **Massenänderung** – um mehrere ausgewählte Import Feed Datensätze gleichzeitig zu ändern. Um eine längere Liste von Feldern für die Massenänderung zu erhalten, wenden Sie sich an Ihren Administrator.
- **Exportieren** – um die gewünschten Datenfelder der ausgewählten Import Feed Datensätze im XLSX- oder CSV-Format zu exportieren.
- **Beziehung hinzufügen** – um die ausgewählten Import Feed Datensätze mit anderen Import-Ergebnisdatensätzen zu verknüpfen.
- **Beziehung entfernen** – um die Beziehungen zu entfernen, die den ausgewählten Import Feed Datensätzen hinzugefügt wurden.
## Konfiguration von Import Cron Job
Um den Import automatisch gemäß einem festgelegten Zeitplan auszuführen, können Sie die **Import Cron Jobs** konfigurieren. Um einen neuen Job-Datensatz zu erstellen, klicken Sie im Navigationsmenü auf `Import Cron Jobs`, um zur Listenansicht von Import Cron Jobs zu übergehen, und klicken Sie dann auf den Button `Import Cron Job erstellen`.

> Wenn es im Navigationsmenü keine `Import Cron Jobs` Option angezeigt wird, wenden Sie sich an Ihren Administrator.

Das allgemeine Fenster zur Erstellung wird geöffnet:

![Cron job creation](_assets/cron-job-create.jpg)

Definieren Sie hier die folgenden Parameter für den zu erstellenden Import Cron Job:

- **Aktiv** – setzen Sie die Checkbox, um den Job zu aktivieren.
- **Name** – geben Sie den Namen des Import Cron Jobs ein. 
- **Import Feed** – wählen Sie den gewünschten Import Feed aus, anhand dessen die Daten importiert werden.
- **Link** – geben Sie einen direkten Link zu der Beispieldatei (CSV) ein, aus der die Daten importiert werden.
- **Scheduling (Zeitplanung) ** – geben Sie einen gewünschten Cron Job Zeitplan ein, d.h. die Häufigkeit von Cron Jobs, und nutzen Sie dabei allgemeine Syntaxregeln.
  Wenn ein Import-Cron-Job gestartet wird, werden dessen Details im Queue Manager Popup angezeigt.

Die Ergebnisse des ausgeführten Import Cron Jobs werden im Panel `LOGS` des entsprechenden Job-Datensatzes angezeigt:

![Cron job logs](_assets/cron-job-logs.jpg)

Um den Import Cron Jobs Queue und die Statusdetails anzusehen, gehen Sie zu `Administration> Schedule Jobs` und klicken Sie auf den Button `Jobs`:

![Cron jobs queue](_assets/cron-jobs-queue.jpg)

## Besondere Importfälle

### Produktvarianten 

Wenn das Modul ["Product Variants"](https://atropim.com/de/shop/product-variants) in Ihrem [AtroPIM-System]((https://atropim.com/help/what-is-atropim))  installiert ist,  können Produktvarianten über Import Feeds importiert werden. Fügen Sie dazu das Entitätsfeld `Konfigurierbares Produkt` auf die [oben beschriebene Weise](#hinzufügen-der-entitätsfelder) hinzu :

![Create configurable product](_assets/create-configurable-product.jpg)

Stellen Sie sicher, dass das konfigurierbare Produkt und seine Variante(-n) zu derselben [Produktfamilie](https://atropim.com/help/product-families) und demselben [Katalog](https://atropim.com/help/catalogs) gehören. Andernfalls werden diese Datensätze nicht importiert, sondern zu der [Fehlerdatei](#fehlerdatei) hinzugefügt.

Die Einrichtung anderer Felder und Attribute für den Datensatz des konfigurierbaren Produkts ist gleich wie bei dem [einfachen Produkt](#konfiguration-von-import-feeds).

Alle Attribute vom Typ `Enum` sowie [mehrsprachige Attribute](https://atropim.com/de/shop/multi-languages#mehrsprachige-attribute), die im Panel `Konfigurator` auf der Detailansichtsseite des gewünschten Import-Feed-Datensatzes hinzugefügt wurden, werden zu *variantenbildenden*, während Attribute von anderen Typen als einfache Attribute der Produktvarianten importiert werden.

Vor der Durchführung des Importvorgangs für Produktvarianten wird die Datenvalidierung aufgrund des Entitätsfelds `Konfigurierbares Produkt` vorgenommen. Abhängig von den Validierungsergebnissen kann es verschiedene Szenarien geben:

- das Feld `Konfigurierbares Produkt` ist bei einigen Datensätzen **leer**: diese Produktdatensätze werden als Datensätze vom Typ `Einfaches Produkt` importiert, d. h. Sie können gleichzeitig nicht nur die Datensätze der Produktvarianten, sondern auch Produktdatensätze von andere Typen importieren;
- das konfigurierbare Produkt, das zur Importdatei hinzugefügt ist, ist schon im System **vorhanden**: als Ergebnis des Importvorgangs werden die Datensätze der Produktvarianten aufgrund des angegebenen konfigurierbaren Produkt erstellt; 
- das  konfigurierbare Produkt, das in der Importdatei angegeben ist, **fehlt** im System:  wenn der Importvorgang durchgeführt wird, wird der Datensatz dieses konfigurierbaren Produkts aufgrund der entsprechenden Zeile der Importdatei erstellt. 


In allen anderen Fällen, wird, neben den beschriebenen Szenarien, der Import der Produktvarianten über Import Feeds genauso wie der [Import der einfachen Produkte](#import-feed-ausführung) durchgeführt. Bitte beachten Sie, dass alle Felder und Panels für die importierten Produktvarianten nach dem Abschluss des Importvorgangs automatisch entsperrt werden.

## Anpassung
Das Modul kann an Ihre Bedürfnisse angepasst werden – zusätzliche Funktionen können hinzu programmiert werden, vorhandene Funktionen können geändert werden. Bitte kontaktieren Sie uns diesbezüglich. Es gelten unsere AGB (Allgemeine Geschäftsbedingungen).

## Demo
https://demo.atropim.com/

### Installation

The Installation Guide is available [here](https://github.com/atrocore/atrocore-docs/blob/master/en/administration/installation.md).

## Lizenz

Dieses Modul wird unter der GNU GPLv3 [Lizenz](https://www.gnu.org/licenses/gpl-3.0.en.html) veröffentlicht.
