<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * German language strings for block_workplanner.
 *
 * @package    block_workplanner
 * @copyright  2026 Moodle in Niedersachsen e. V.
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['activitynotavailable'] = 'Diese Aktivität steht dir nicht zur Verfügung.';
$string['allcourses'] = 'Alle Kurse';
$string['competencies'] = 'Meine Kompetenzen';
$string['competencies_help'] = 'Deine Lehrkräfte haben Aktivitäten Kompetenzen zugeordnet. Eine Kompetenz ist erreicht, wenn du alle ihre Aktivitäten abgeschlossen hast, und in Arbeit, wenn du mindestens eine davon abgeschlossen oder begonnen hast.';
$string['competencyachieved'] = 'Erreicht: {$a->done} von {$a->total} Aktivitäten';
$string['competencycount'] = '{$a} Kompetenzen';
$string['competencycountone'] = '1 Kompetenz';
$string['competencyinprogress'] = 'In Arbeit: {$a->done} von {$a->total} Aktivitäten';
$string['competencyopen'] = 'Offen: {$a->done} von {$a->total} Aktivitäten';
$string['competencysummary'] = '{$a->achieved} erreicht · {$a->inprogress} in Arbeit · {$a->open} offen';
$string['coursefilter'] = 'Kurs';
$string['courseloaded'] = 'Der Arbeitsplaner zeigt jetzt den gewählten Kurs.';
$string['deadlineat'] = 'Abgabe {$a}';
$string['dueon'] = 'Abgabe: {$a}';
$string['eventplanupdated'] = 'Eintrag im Arbeitsplaner geändert';
$string['freeintro'] = 'Materialien ohne Termin und ohne Lernpfad. Nimm dir, was du brauchst, oder plane es ein.';
$string['grouplater'] = 'Später';
$string['groupnextweek'] = 'Nächste Woche';
$string['groupoverdue'] = 'Überfällig';
$string['groupthisweek'] = 'Diese Woche';
$string['infos'] = 'Infos deiner Lehrkräfte';
$string['infos_help'] = 'Deine Lehrkräfte schreiben diese Infos in ihren Kursen. Du siehst nur Infos aus deinen eigenen Kursen.';
$string['later'] = 'Später erledigen';
$string['later_help'] = 'Lege hier Aktivitäten ab, die du erledigen willst, ohne schon einen Tag festzulegen. Abgabetermine deiner Lehrkräfte gelten trotzdem.';
$string['legenddeadline'] = 'Abgabe (Termin der Lehrkraft)';
$string['legendplan'] = 'Von dir geplant, Farbe = Kurs';
$string['nextstep'] = 'Dein nächster Schritt';
$string['nextweek'] = 'Nächste Woche';
$string['nocompetencies'] = 'Deinen Aktivitäten sind keine Kompetenzen zugeordnet.';
$string['nodated'] = 'Keine offenen Aufgaben mit Termin.';
$string['nofree'] = 'Keine frei verfügbaren Materialien.';
$string['nolater'] = 'Ziehe Aktivitäten hierher oder wähle im Menü „Einplanen“ den Eintrag „Später erledigen“.';
$string['nopaths'] = 'In deinen Kursen gibt es keine Lernpfade.';
$string['noprogress'] = 'Noch kein Lernfortschritt vorhanden.';
$string['overduecount'] = '{$a} überfällig';
$string['pathcount'] = '{$a->done} von {$a->total} Schritten erledigt';
$string['pathname'] = '{$a->course}: {$a->section}';
$string['plan'] = 'Einplanen';
$string['planactivity'] = '{$a} einplanen';
$string['plannedfor'] = 'Geplant: {$a}';
$string['plannedlater'] = 'Geplant: später';
$string['planupdated'] = 'Dein Plan wurde aktualisiert.';
$string['pluginname'] = 'Arbeitsplaner';
$string['previousweek'] = 'Vorherige Woche';
$string['privacy:metadata:plan'] = 'Die Planung, die eine Person im Arbeitsplaner für ihre Aktivitäten anlegt.';
$string['privacy:metadata:plan:cmid'] = 'Die geplante Aktivität.';
$string['privacy:metadata:plan:courseid'] = 'Der Kurs der geplanten Aktivität.';
$string['privacy:metadata:plan:plandate'] = 'Der Tag, für den die Aktivität geplant ist.';
$string['privacy:metadata:plan:slot'] = 'Ob die Aktivität für einen Tag oder für später geplant ist.';
$string['privacy:metadata:plan:timecreated'] = 'Der Zeitpunkt, zu dem die Planung angelegt wurde.';
$string['privacy:metadata:plan:timemodified'] = 'Der Zeitpunkt der letzten Änderung der Planung.';
$string['privacy:metadata:plan:userid'] = 'Die Person, die die Planung angelegt hat.';
$string['privacy:metadata:preference:course'] = 'Der in der Kursauswahl des Arbeitsplaners gewählte Kurs, 0 für alle Kurse.';
$string['progress'] = 'Mein Lernfortschritt';
$string['progress_help'] = 'Abgeschlossene Aktivitäten von allen Aktivitäten mit Abschlussverfolgung je Kurs. Unter „Bewertungen anzeigen“ siehst du deine eigenen Bewertungen, soweit deine Lehrkräfte sie im Bewertungsbuch freigegeben haben.';
$string['progresscount'] = '{$a->done} von {$a->total}';
$string['removeplan'] = 'Aus dem Plan nehmen';
$string['removeplanfor'] = '{$a} aus dem Plan nehmen';
$string['returnnotice'] = 'Du hast diese Aktivität aus deinem Arbeitsplaner geöffnet.';
$string['returntoplanner'] = 'Zurück zum Arbeitsplaner';
$string['showgrades'] = 'Bewertungen anzeigen';
$string['statedone'] = 'Erledigt';
$string['stateinprogress'] = 'In Arbeit';
$string['statelocked'] = 'Gesperrt';
$string['tasks'] = 'Meine Aufgaben';
$string['tasks_help'] = 'Nach Datum: Aktivitäten mit Termin. Lernpfad: Schritte in fester Reihenfolge. Frei: Materialien ohne Termin und ohne Lernpfad. Ziehe eine Aufgabe auf einen Tag deines Wochenplans oder nutze ihr Menü „Einplanen“.';
$string['teachernote'] = 'Hinweis deiner Lehrkraft:';
$string['today'] = 'Heute';
$string['viewdated'] = 'Nach Datum';
$string['viewfree'] = 'Frei';
$string['viewpath'] = 'Lernpfad';
$string['week'] = 'Mein Wochenplan';
$string['week_help'] = 'Rote Einträge sind Abgabetermine deiner Lehrkräfte. Farbige Einträge hast du selbst geplant. Deine Planung siehst nur du.';
$string['weeklabel'] = '{$a->from} – {$a->to}';
$string['weekloaded'] = 'Der Wochenplan wurde aktualisiert.';
$string['workplanner:addinstance'] = 'Neuen Block Arbeitsplaner hinzufügen';
$string['workplanner:myaddinstance'] = 'Neuen Block Arbeitsplaner zum Dashboard hinzufügen';
$string['workplanner:plan'] = 'Eigene Aktivitäten im Arbeitsplaner planen';
$string['workplanner:view'] = 'Eigenen Arbeitsplaner ansehen';
