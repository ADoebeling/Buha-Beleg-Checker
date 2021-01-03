# Buha-Beleg-Checker

Dieses Script dient mir zur einfachen Prüfung der Vollständigkeit meiner Buchhaltung.
Es verarbeitet einen Kontoauszug (CSV aus WISO Mein Geld), mein Buchungsprotokoll (CSV aus WISO EÜR&Kasse) sowie einen Ordner mit allen Belegen (PDF-Rechnungen) und erstellt hieraus Protokolle im MD-Format.

## Scriptausgabe (Markdown)

| #5555 | 28.01.2020 | -5,00 € |
|---|---|---|
| Kontobewegung: | `Sparkasse Sonstwo` *an*<br>`STRATO AG` *mit*<br>`-5,00 €` | [`Kontoauszug.CSV`](#) |
| Buchungssatz: | `4000 Hosting-Aufwendungen` *an*<br>`1200 Bank` *mit* <br>`5,00 €`  | [`Buchungsexport.csv`](#) |
| Verwendungszweck: | `RE 123456 v. 15.01.2020` | [`Kontoauszug.CSV`](#) |
| Rechnungen: | Sind abgelegt | [`#5555 - Strato - RE123456.pdf`](#) |
| Eigenbeleg: | Vorlage wurde erstellt | [`#5555 - Eigenbeleg.md`](#)<br> |

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

            -> writeMdReportEigenbeleg('../2020/EB - Eigenbelege/')

            -> writeMdReport('../2020/README.md')
            -> writeMdReportBelegerfassung('../2020/Belegerfassung.md')
            -> writeMdReportBuchungen('../2020/Buchungen.md')
            -> writeMdReportKontobewegung('../2020/Kontobewegung.md');

            //-> printDebug();

```

## Support & Bugs

* Bitte via Issue-Tracker

## Lizenz

* CC BY SA

## Kontakt

* Andreas Döbeling  
https://www.Doebeling.de





