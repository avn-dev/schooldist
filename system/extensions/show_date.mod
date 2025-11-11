<?
/**
 * Dieses Modul gibt das aktuelle Datum aus
 * @author: Bastian Hübner
 * @copyright: plan-i GmbH
 * @package: keins
 * @name show_date
 * @tutorial: nur einfügen, dann tutst
 */

$aDeutscheTage=array(0=>"Sonntag",
1=>"Montag",
2=>"Dienstag",
3=>"Mittwoch",
4=>"Donnerstag",
5=>"Freitag",
6=>"Samstag");

echo "<b>".$aDeutscheTage[date("w")]."</b>".date(", d.m.Y");
?>