# Buha-Beleg-Checker

Dieses Script dient mir zur einfachen Prüfung der Vollständigkeit meiner Buchhaltung.
Es verarbeitet einen QIF-Kontoauszug, mein Buchungsprotokoll als CSV sowie einen Ordner mit allen Belegen und erstellt hieraus ein Protokoll im MD-Format.

## Input QIF-Kontoauszug

Einlesen aller bereitgestellten Kontoauszüge im QIF-Format.

* `/input/qif/Export_Konto_1_Q1.qif`
* `/input/qif/Export_Konto_2.qif`
* `/input/qif/Export_Splitbuchungen.qif`

## Input CSV-Buchungsprotokoll

Einlesen meines Buchungsprotokolls aus BUHL EÜR&Kasse

* `/input/buchungen/buchungsprotokoll.csv`

Format:

`Buchungsnummer;Teilbuchung;Belegdatum;Buchungstext;Konto;Kontobezeichnung;Gegenkonto;Gegenkontobezeichnung;Soll;Haben;Steuerschluessel;Steuersatz;Steuerschluesselbezeichnung;Belegnummer;Buchungsdatum;UStID;Umsatzart`

Verwendet werden hiervon (bislang) nur die Spalten:
* Belegdatum
* Belegnummer
* Buchungstext
* Kontobezeichnung
* Gegenkontobezeichnung

## Input Belege

Einlesen aller abgelegten Belege aus dem Dateisystem

* `input/belege/2020/#123 - Anbieter XY - Rechnung 45824.pdf`
* `input/belege/2020/#124 - Rechnung 45825.pdf`
* `input/belege/2020/Rechnung blubb.pdf`

## Export Protokoll

### #123

| Beleg | Information | Bewertung    |
|-------|-------------|-----------|
| Datum | 01.01.2020  | OK gem. `Kontoauszug XXXX.qif` und `buchungsprotokoll.csv`, bitte mit RE abgleichen |
| Betrag |1234,56 €   | OK gem. `Kontoauszug XXXX.qif` und `buchungsprotokoll.csv`, bitte mit RE abgleichen |
| Kontobewegung: | Sparkasse an Amazon UK  | OK gem. `Kontoauszug XXXX.qif` und `buchungsprotokoll.csv`  |
| Verwendungszweck | sdf98z23jklsf893kjsdf  | OK gem. `Kontoauszug XXXX.qif` |
| Buchung: | *Bank* an *Hostingaufwendungen* | OK gem. `buchungsprotokoll.csv` |
| Belege : | `#1234 - df - invoice 1234.pdf`, `#1234 - df - invoice 1234.msg` |  |

### #124

| Beleg | Information | Bewertung    |
|-------|-------------|-----------|
| Kontobewegung: | **NICHT GEFUNDEN**  | **FEHLER**: Keine Kontobewegung zu `#123` gefunden |
| Buchung: |  **NICHT GEBUCHT** | **FEHLER**: Keine Buchung zu `#123` gefunden |
| Belege : | **NICHT ABGELEGT** | **FEHLER** Keine Belege zu `#123` gefunden





