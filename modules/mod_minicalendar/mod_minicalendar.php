<?php
/********************************************************************
Product    : Simple Calendar With Time
Author     : tamim84
Copyright  : Tamim Mostafa 2013
Licence    : GNU General Public License
Description: Displays a calendar in a given module position
*********************************************************************/

defined('_JEXEC') or die('Restricted access');

// make sure we only get one copy of the helper (allows multiple instances of the calendar)

require_once (dirname(__FILE__).'/helper.php');

// Get module parameters

$startyear 	     = trim($params->get('startyear'));
$startmonth	     = trim($params->get('startmonth'));
$numMonths 	     = trim($params->get('numMonths',1));
$numCols 	     = trim($params->get('numCols',1));
$links 		     = $params->get('links',0);
$timeZone	     = $params->get('timeZone',0);
$day_name_length = trim($params->get('dayLength',1));	// length of the day names
$start_day       = trim($params->get('firstDay',0));	// 0 for Sunday, 1 for Monday, etc
$weekHdr         = trim($params->get('weekHdr'));
$debug 		     = $params->get('debug',0);

if ($debug)
	mc_init_debug();
else
	@unlink(JPATH_ROOT.'/modules/mod_minicalendar/trace.txt');

// If any internal styles are defined, add them to the document head

$styles = '';
$style_table = trim($params->get('style_table'));
if ($style_table != '')
	$styles .= "\n.mod_minical_table {".$style_table.'}';
$style_head = trim($params->get('style_head'));
if ($style_head != '')
	$styles .= "\n.mod_minical_table th {".$style_head.'}';
$style_day = trim($params->get('style_day'));
if ($style_day != '')
	$styles .= "\n.mod_minical_table td {".$style_day.'}';
$style_today = trim($params->get('style_today'));
if ($style_today != '')
	$styles .= "\n.mod_minical_table td#mod_minical_today {".$style_today.'}';
$style_week = trim($params->get('style_week'));
if ($style_week != '')
	$styles .= "\n.mod_minical_weekno {".$style_week.'}';
$style_left = trim($params->get('style_left'));
if ($style_left != '')
	$styles .= "\n.mod_minical_left {".$style_left.'}';
$style_right = trim($params->get('style_right'));
if ($style_right != '')
	$styles .= "\n.mod_minical_right {".$style_right.'}';
$style_div = trim($params->get('style_div'));
if ($style_div != '')
	$styles .= "\n.mod_minical_div {".$style_div.'}';
if ($styles != '')
	{
	$style = "\n".'<style type="text/css">'.$styles."\n</style>\n";
	$document = JFactory::getDocument();
	$document->addCustomTag($style);
	}

if (($timeZone != '0') and (function_exists('date_default_timezone_set')))
	date_default_timezone_set($timeZone);

// if links are in use, create the link address and get our month offsetting parameter
// our parameter is &cal_offset=nnx 
// where nn is the current offset and x is 'p' for the previous month or 'n' for the next month

$link = '';
$current_offset = 0;
if ($links)
	{
	$uri = $_SERVER['REQUEST_URI'];
	$pos =  strpos($uri,'&cal_offset');				// we need the uri minus our parameter
	if ($pos)
		{
		$cal_offset = JRequest::getVar('cal_offset');
		$len = strlen($cal_offset);
		$more = substr($uri,$pos+strlen('&cal_offset=')+$len);	// could be more params after ours
		$link = substr($uri,0,$pos).$more;
		$command = $cal_offset{$len-1};					// get the p or the n
		$current_offset = substr($cal_offset,0,$len-1);	// strip off the p or the n
		if ($command == 'p')
			$current_offset -= 1;						// request the previous month
		if ($command == 'n')
			$current_offset += 1;						// request the next month
		}
	else
		{
		$link = $uri;
		$command = '';
		$current_offset = 0;
		}
	if (!strstr($uri,'&'))
		$link = $uri.'?';
	$link .= '&cal_offset='.$current_offset;		// make the link
	$link = htmlspecialchars($link);
	}

// Set the initial month and year, defaulting to the current month

if ($startyear)
	$year 	= $startyear;
else
	$year 	= date('Y');

if ($startmonth)
	$month 	= $startmonth;
else
	$month 	= date('m');

// Add in the current offset

$startdate = mktime(0,0,0,$month + $current_offset, 1, $year);
$month = date('m',$startdate);
$year = date('Y',$startdate);

// Draw the number of calendars requested in the module parameters

echo '<table align="center"><tr valign="top">';
$colcount = 0;
for ($monthcount = 1; $monthcount <= $numMonths ; $monthcount ++)
	{
	$colcount ++;
	echo '<td>';
	echo make_calendar($year, $month, $link, $day_name_length, $start_day, $weekHdr, $debug);
	$link = '';						// only draw links on first calendar
	echo '</td>';
	if (($colcount == $numCols) && ($monthcount < $numMonths))
		{
		echo '</tr><tr valign="top"><td colspan="2"><div class="mod_minical_div"></div></td></tr><tr valign="top">';
		$colcount = 0;
		}
	$month ++;
	if ($month > 12)
		{
		$month = 1;
		$year ++;
		}
	}
echo '</tr></table>';

?>
