# Arbeitsplaner (block_workplanner)

Ein Block für das Dashboard der Lernenden. Er sammelt die Aktivitäten aus allen Kursen
und hilft, die Arbeit einzuteilen.

- **Meine Aufgaben** in drei Ansichten: *Nach Datum* (überfällig, diese Woche, nächste
  Woche, später), *Lernpfad* (Schritte in Reihenfolge, der nächste Schritt hervorgehoben)
  und *Frei* (Materialien ohne Termin und ohne Lernpfad).
- **Mein Wochenplan**: sieben Tage mit den Abgabeterminen der Lehrkräfte. Lernende ziehen
  Aktivitäten auf einen Tag oder nutzen das Menü „Einplanen“, das auch mit der Tastatur
  und auf Touch-Geräten funktioniert.
- **Später erledigen**: ein Ablageort für Aktivitäten ohne festen Tag.
- **Mein Lernfortschritt** je Kurs, aus dem Aktivitätsabschluss.
- **Meine Kompetenzen** und **Infos deiner Lehrkräfte**, geliefert von anderen Plugins
  über einen Hook (siehe unten).

**Rückweg:** Öffnet eine lernende Person eine Aktivität aus dem Arbeitsplaner, zeigen die
Seiten dieses Kurses einen Hinweis mit dem Knopf zurück zum Arbeitsplaner. Landet sie aus
einer Aktivität auf der Kursseite (z. B. nach „Speichern und zum Kurs“), geht es direkt
zurück in den Arbeitsplaner. Die Merkung liegt nur im Browser-Tab (sessionStorage) und
läuft nach drei Stunden ab.

Die Planung sieht nur die lernende Person selbst. Kurse, die die Person bearbeiten darf,
erscheinen nicht; Lehrkräfte sehen ihre eigenen Kurse also nicht als Aufgaben.

## Voraussetzungen

- Moodle 5.0 oder neuer
- PHP 8.2, 8.3 oder 8.4
- MariaDB, MySQL oder PostgreSQL

## Installation

1. Plugin nach `blocks/workplanner` entpacken (ab Moodle 5.1:
   `public/blocks/workplanner`).
2. *Website-Administration > Mitteilungen* aufrufen und die Installation abschließen.
3. Den Block auf dem Standard-Dashboard im mittleren Inhaltsbereich hinzufügen
   (*Website-Administration > Darstellung > Standard-Dashboard*) und die Dashboards für
   alle Nutzer/innen zurücksetzen, wenn er bei allen erscheinen soll.

## Woher die Daten kommen

| Bereich | Quelle |
|---|---|
| Termine | Termine der Aktivität (z. B. Fälligkeit, Schließung), sonst das erwartete Abschlussdatum |
| Lernpfade | Voraussetzungen „Abschluss von“ oder „Bewertung in“ einer anderen Aktivität |
| Frei | Aktivitäten ohne Termin und außerhalb eines Lernpfads |
| Fortschritt | Aktivitätsabschluss |
| Kompetenzen, Infos, Hinweise | andere Plugins über den Hook |

## Hook für andere Plugins

Plugins liefern Daten über `\block_workplanner\hook\extend_planner`. Die Anbindung steht
in `db/hooks.php` des liefernden Plugins (Beispiel im englischen README). Der Callback
erhält `$hook->userid` und `$hook->courseids` und ruft `add_info()`, `add_note()` und
`add_competency()` auf. Alle Texte sind reiner Text. Der Vertrag steht in
`docs/adr/0001-planner-hook.md`.

## Datenschutz

Gespeichert wird je geplanter Aktivität ein Datensatz: Person, Kurs, Aktivität, Tag oder
„später“ und Zeitstempel. Export und Löschung sind über die Privacy-API umgesetzt.
Planungen werden gelöscht, wenn die Aktivität, der Kurs oder die Person gelöscht wird.

## Lizenz

2026 Moodle in Niedersachsen e. V., GNU GPL v3 oder später.
