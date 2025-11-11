<?php

namespace TsReporting\Generator\Groupings;

use TsReporting\Generator\ValueHandler;

/**
 * QueryableInterface: Temporäre MySQL-Tabellen auf PHP-Ebene gelöst
 *
 * Gruppierungen mit diesem Interface führen Querys pro Aufruf von $next() innerhalb von group() aus. Dies funktioniert
 * quasi wie eine Middleware, wo mit jedem Aufruf auch $values manipuliert werden muss. Die entsprechende Pipe ($next)
 * resultiert daraus, damit Queryables auch untereinander als kartesisches Produkt verknüpft werden, sonst wäre nur eine
 * einzige Ebene möglich.
 *
 * Beispiel Zeitraum: Damit für einen beliebigen Zeitraum auch alle einzelnen Zeiträume (z.B. pro Monat jeder Monat) in
 * der Statistik auftauchen, und die Zeiträume ohne Daten nicht einfach fehlen, müsste man entweder mit einer temporären
 * Tabelle arbeiten, die Daten manuell ergänzen oder den Query pro Zeitraum ausführen. Letzteres übernimmt QueryableIe
 * und merged die Daten anschließend, parallel zu allen weiteren Gruppierungen, die direkt über den Query laufen.
 */
interface QueryableInterface
{
	public function group(ValueHandler $values, \Closure $next): void;
}