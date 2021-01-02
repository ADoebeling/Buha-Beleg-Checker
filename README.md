# Buha-Beleg-Checker

Dieses Script dient mir zur einfachen Prüfung der Vollständigkeit meiner Buchhaltung.
Es verarbeitet einen Kontoauszug (CSV aus WISO Mein Geld), mein Buchungsprotokoll (CSV aus WISO EÜR&Kasse) sowie einen Ordner mit allen Belegen (PDF-Rechnungen) und erstellt hieraus ein Protokoll im MD-Format.

## Ausgabe als Markdown

#### ToDo: Nicht erfasste Belege
Bei folgenden Belegen fehlt noch die Belegnummer im Dateinamen
* [ ] [All-Inkl - 1111.pdf](#)

#### TODO: Fehlende Belege
Folgende Fälle haben noch keinen Beleg
* [ ] [2222](#2222 "Zeige Datensatz #2222") über `-18,69`
* [ ] [3333](#3333 "Zeige Datensatz #2222") über `-28,29`

#### ToDo: Unvollständige Buchungen
Bei folgenden Buchungen ist keine Belegnummer angegeben
* [ ] `01.01.2020` | `854,53 €` von `Büroeinrichtung` an `Bank`

#### TODO: Verbuchung ausstehend
Folgende Fälle sind noch nicht verbucht
* [ ] [4444](#4444 "Zeige Datensatz #4444") über `-18,69`

#### #5555
| Beleg | Information | Bewertung |
|---|---|---|
| Datum: | `05.10.2020` | OK gem. `Export_Sparkasse.CSV` |
| Betrag: | `-18,69` | OK |
| Überweisung von/an: | `Domainfactory` | OK gem. `Export_Sparkasse.CSV` |
| Verwendungszweck: | `Rg. 456789 v. 30.09.20` | OK gem. `Export_Sparkasse.CSV` |
| Beleg: | [#5555_1 - df - 2020-09-30_RE456789.pdf](#) | Bitte Beleg prüfen |
| Beleg: | [#5555_2 - df - 2020-09-30_RE456789.pdf](#) | Bitte Beleg prüfen |

## Installation

* `git clone https://github.com/ADoebeling/Buha-Beleg-Checker.git`
* Script `example.php` als script.php duplizieren
* Pfade in `script.php` anpassen
* Ausführen von `script.php` (Idealerweise via php8-cgi, wobei es via Webserver genauso funktioniert)

## Konfiguration

```php
<?php
// example.php

require_once 'doebeling.buhaBelegChecker.checker.class.php';

$buha2020   = new \DOEBELING\BuhaBelegChecker\Checker();
$buha2020   -> parseBelege('../2020/ER - Eingangsrechnungen/')
            -> parseBelege('../2020/AR - Ausgangsrechnungen/')
            -> parseKontoauszuege('../2020/KA - Kontoauszug/')
            -> parseBuchungen('../2020/BP - Buchungsprotokoll/')
            -> writeMdReport('../2020/README.md');
            //-> printDebug();
```

## Support & Bugs

* Bitte via Issue-Tracker

## Lizenz

* CC BY SA

## Kontakt

* Andreas Döbeling
https://www.Doebeling.de





