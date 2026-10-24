# 1.8.4
- „Nur Fahrzeuge mit Produkten anzeigen“: Findet die Prüfung gar keine Zuordnung (meist veralteter Suchindex nach einer Massenbearbeitung), wird nichts mehr ausgeblendet – die Leiste verschwindet nicht komplett. Der Status-Kommentar weist darauf hin

# 1.8.3
- Neu: Status als HTML-Kommentar im Header („Nova Fahrzeugauswahl: aktiv, 15 Fahrzeuge“ bzw. der Grund, warum keine Kachel-Leiste erscheint) – im Seitenquelltext sichtbar, für Kunden unsichtbar

# 1.8.2
- Fehler behoben: Die Kachel-Leiste fehlte, wenn beim Verkaufskanal das Feld „In diesen Verkaufskanälen aktiv“ von der Vererbung getrennt (z. B. leer) gespeichert war. Maßgeblich ist jetzt immer auch die Liste bei „Alle Verkaufskanäle“

# 1.8.1
- Fehler behoben: Nach dem Update auf 1.8.0 ließ sich das Theme nicht kompilieren („Incompatible units vw and rem“) – die Schriftgröße der Kategorie-Überschrift wird jetzt unverändert an den Browser gegeben

# 1.8.0
- Neu: **Kategorie-Überschrift** (optional): Kategoriename als Überschrift, bei gewähltem Fahrzeug ergänzt – z. B. „Federkugeln für Citroën XM“. Verbindungswort einstellbar
- Neu: **CSS-Klasse der Fahrzeug-Beschreibung** – z. B. `nova-fitment`, damit Passtabellen im Stil des Nova Themes erscheinen

# 1.7.1
- Neues Plugin-Icon im Nova-Stil (Erweiterungen → Meine Erweiterungen)
- Neuer Anzeigename „Nova Fahrzeugauswahl“ (technischer Name bleibt – keine Neuinstallation nötig)
- Repository umbenannt in SW6-Nova-Fahrzeugauswahl – alte Adresse leitet weiter
