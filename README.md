# Fahrzeug-Schnellauswahl (Horizontal Vehicle Switcher)

Schlankes Shopware 6.6+ Plugin für eine globale, horizontale Fahrzeug-Schnellauswahl
im Storefront-Header. Kein Dropdown – Fahrzeuge werden als Kacheln / Pills nebeneinander
angezeigt. Single-Select per Klick, global über alle Kategorien.

> English version: [README.en.md](README.en.md)

## Funktionsweise

| Schritt | Was passiert |
|--------|--------------|
| Datenbasis | Ganz normale Shopware-**Eigenschaften** (Properties). Im Admin werden eine oder zwei „Filtergruppen" (= je eine Eigenschaftsgruppe) ausgewählt. Wie diese befüllt werden – manuell, per Import oder aus einem ERP – ist dem Plugin egal. |
| Anzeige | `HeaderPageletSubscriber` hängt die Optionen der Gruppen als Extension `vehicleSwitcher` an das Header-Pagelet. Twig rendert die Pill-Leiste unter dem Header. |
| Auswahl | JS-Plugin `VehicleSwitcher`: Klick → genau ein Fahrzeug aktiv (kein Stacking). Klick auf das aktive Fahrzeug oder auf „Alle Fahrzeuge" → Auswahl aufgehoben. Der Wert wird in ein **Cookie** (`vehicle-switcher-option`) geschrieben und in `localStorage` gespiegelt, dann ein Reload. Kein AJAX/Route – das Cookie ist beim nächsten Request sofort da. |
| Globaler Filter | `ProductListingSubscriber` hört auf `ProductListingCriteriaEvent` / `ProductSearchCriteriaEvent` / `ProductSuggestCriteriaEvent`, liest das Cookie und fügt bei aktivem Fahrzeug `EqualsFilter('product.properties.id', <optionId>)` zum `Criteria` hinzu – für **jede** Kategorie / Suche. |
| HTTP-Cache | `CacheKeySubscriber` nimmt die OptionId in den HTTP-Cache-Key auf (`HttpCacheKeyEvent` + `Product*RouteCacheKeyEvent`), damit gecachte Kategorie-/Suchseiten pro Fahrzeug unterschieden werden. |
| Multi-Shop | Feld **„In diesen Verkaufskanälen aktiv"** – eine Mehrfachauswahl. Leiste **und** Filter greifen nur in den gewählten Kanälen. Kein Vererbungs-Gefummel pro Kanal. |

## Verzeichnisstruktur

```
.
├── composer.json
└── src/
    ├── FahrzeugSchnellauswahl.php
    ├── Subscriber/
    │   ├── HeaderPageletSubscriber.php          # Optionen -> Template
    │   ├── ProductListingSubscriber.php         # globaler Criteria-Filter
    │   └── CacheKeySubscriber.php               # OptionId -> HTTP-Cache-Key
    ├── Service/
    │   ├── VehicleSwitcherConfig.php            # SystemConfig (pro SalesChannel)
    │   ├── VehicleSelectionStorage.php          # Cookie lesen (Single-Select)
    │   └── VehicleOptionLoader.php              # property_group_option laden
    ├── Struct/
    │   └── VehicleSwitcherStruct.php
    ├── Migration/
    │   └── …CreateVehicleDescriptionField.php   # Custom-Field an property_group_option
    └── Resources/
        ├── config/
        │   ├── config.xml                       # Admin-Konfiguration
        │   └── services.xml
        ├── snippet/
        │   ├── de_DE/messages.de-DE.json
        │   └── en_GB/messages.en-GB.json        # nur technischer Fallback
        ├── views/storefront/
        │   ├── layout/header/header.html.twig
        │   └── component/vehicle-switcher/vehicle-switcher.html.twig
        └── app/storefront/
            ├── src/
            │   ├── main.js
            │   ├── plugin/vehicle-switcher/vehicle-switcher.plugin.js
            │   └── scss/base.scss
            └── dist/…/fahrzeug-schnellauswahl.js   # kompiliert, mitgeliefert
```

## Installation

```bash
# Plugin nach custom/plugins/FahrzeugSchnellauswahl legen (Ordnername = Plugin-Klassenname)
bin/console plugin:refresh
bin/console plugin:install --activate FahrzeugSchnellauswahl
bin/console cache:clear

# Storefront-Assets bauen
bin/build-storefront.sh          # oder: bin/console theme:compile
```

## Konfiguration (Admin)

Einstellungen → System → Plugins → *Fahrzeug-Schnellauswahl* → *Konfiguration*

1. Bei **„Alle Verkaufskanäle"** (oben) bleiben.
2. Feld **„In diesen Verkaufskanälen aktiv"**: die Verkaufskanäle wählen, in denen die Schnellauswahl erscheinen soll. Leer = nirgends aktiv.
3. Karte **Filtergruppen**: „Filtergruppe 1 – Eigenschaft" wählen, optional „Filtergruppe 2".
4. Speichern.

Die **Beschriftungs- und Darstellungs-Einstellungen** kannst du zusätzlich pro Verkaufskanal überschreiben (oben den Kanal wählen, am Feld die Vererbung lösen). Für Text- und Auswahlfelder funktioniert das problemlos.

### Ausblenden

| Feld | Wirkung |
|---|---|
| **Einzelne Fahrzeuge ausblenden** | Mehrfachauswahl von Ausprägungen, die **nicht** in der Leiste erscheinen (z. B. noch nicht gepflegt). |
| **Nur Fahrzeuge mit Produkten anzeigen** | Blendet automatisch alle Ausprägungen aus, denen im Verkaufskanal kein sichtbares Produkt zugeordnet ist. Kostet pro Seitenaufruf eine zusätzliche (indizierte) Abfrage – mit aktivem HTTP-Cache vernachlässigbar. |

### Filtergruppen

| Feld | Wirkung |
|---|---|
| **Filtergruppe 1 / 2 – Eigenschaft** | Die Shopware-Eigenschaft, deren Optionen als Kacheln erscheinen. **Filtergruppe 1 wird zuerst angezeigt, dann Filtergruppe 2** – unabhängig von der Sortierung der Eigenschaftsgruppen in Shopware. Für „zuerst die A-Typen" also die A-Typ-Eigenschaft in Filtergruppe 1 legen. |
| **Filtergruppe 1 / 2 – Überschrift** | Kleiner Text direkt vor den Kacheln dieser Gruppe. **Leer = keine Überschrift** (Standard – es stehen dann nur die Fahrzeugtypen da). Das Feld liegt direkt unter der jeweiligen Gruppe. |

### Darstellung

| Einstellung | Wirkung |
|---|---|
| **Anzeige** | `Alles in einem Balken` (Standard): eine Reihe, erst Gruppe 1, dann Gruppe 2. `Filtergruppen untereinander`: jede Gruppe in einer eigenen Zeile, „Alle"-Kachel in einer Zeile darüber. |
| **Sortierung der Fahrzeuge** | Innerhalb einer Gruppe: `Manuelle Reihenfolge` (Standard) = Drag-&-Drop-Reihenfolge aus der Eigenschaftsgruppe. Alternativ `A–Z` / `Z–A`. |
| **Maximale Anzahl Kacheln** | Obergrenze, falls eine Gruppe sehr viele Optionen hat. |
| **Präfix in den Kacheln ausblenden** | Text, der am Anfang jeder Kachel-Beschriftung entfernt wird – z. B. `Citroën`, sodass aus „Citroën 2CV6" nur „2CV6" wird. **Nur die Anzeige** – die Eigenschaft selbst (Filter, SEO, Produktdetails) bleibt voll erhalten, der Tooltip zeigt weiter den kompletten Namen. Mehrere Präfixe mit Komma. Kombiniere es mit der Gruppen-Überschrift „Citroën" → `Citroën: [2CV6] [2CV4] …`. |
| **„Alle"-Kachel anzeigen** | Kachel zum Aufheben des Filters. Aus = keine Reset-Kachel (Filter lässt sich weiter durch Klick auf die aktive Kachel aufheben). |
| **Fahrzeug-Beschreibung anzeigen** | Zeigt einen HTML-Block mit der Spezifikation des aktiven Fahrzeugs (Hubraum, Baujahre, Modellvarianten …). Gepflegt wird der Text **pro Ausprägung** im Custom-Field *„Fahrzeug-Beschreibung"* (Kataloge → Eigenschaften → Ausprägung öffnen → Reiter *Zusatzfelder*) – oder per ERP-Sync auf das Feld `vehicle_switcher_description`. |
| **Position der Fahrzeug-Beschreibung** | `Oben` (direkt unter der Kachel-Leiste) oder `Unten` (Seitenende über dem Footer – klassischer SEO-Text-Platz, stört die Produktliste nicht). |
| **Nur auf Kategorie-/Suchseiten** | Empfohlen (Default an): der Text erscheint dann nicht auf jeder Produkt-/Inhaltsseite → weniger doppelter Boilerplate-Content. |
| **Beschriftung der „Alle"-Kachel** | Freier Text, z. B. `Alle` oder `Zurücksetzen`. Leer = Standardtext. |

Die sichtbaren Kacheltexte selbst sind die **Namen der Eigenschafts-Optionen** – die änderst du direkt in Shopware unter *Kataloge → Eigenschaften* (bzw. mehrsprachig je Übersetzung).

## Eigenschaften befüllen

Das Plugin liest nur `property_group` / `property_group_option` und die Zuordnung
`product.properties`. Wer die Werte pflegt, ist offen:

- von Hand im Admin (Kataloge → Eigenschaften, dann am Produkt zuweisen),
- per Produkt-Import (CSV / API),
- aus einem ERP (z. B. ERPNext-Multiselect-Feld → Shopware-Eigenschaft) über den
  bestehenden Produkt-Sync.

Sobald die Optionen einer konfigurierten Gruppe an Produkten hängen, erscheinen sie
automatisch als Kacheln.

## Hinweise

- Der Filter greift serverseitig über das Cookie → nach einem Klick lädt die Seite einmal neu.
- Es ist immer **genau ein** Fahrzeug aktiv oder keins (kein Cookie = „Alle Fahrzeuge").
- Der Cookie-Wert wird serverseitig nur akzeptiert, wenn er eine gültige UUID ist (`VehicleSelectionStorage::sanitize`). Ungültige Werte werden ignoriert und gelangen weder in den HTTP-Cache-Key noch in den Produktfilter.
- Die Fahrzeug-Beschreibung (Zusatzfeld) wird mit `sw_sanitize` ausgegeben, nicht mit `raw`.
- Ist die im Cookie gespeicherte Option in den aktuell konfigurierten Gruppen nicht
  (mehr) vorhanden, wird sie ignoriert, damit der Kunde nicht in einem leeren Filter feststeckt.
- Das kompilierte Storefront-JS (`dist/`) ist **im Plugin enthalten** – auf Produktivsystemen
  reicht `bin/console theme:compile`, kein `bin/build-storefront.sh` / Node nötig.
- Damit die Kachel-Leiste unter einem **eigenen Theme** erscheint, muss dessen `theme.json`
  in `style` **und** `script` den Eintrag `@Plugins` enthalten.
