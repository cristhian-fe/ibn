<?php
/********************************************************************
Product    : Simple Calendar
Author     : tamim84
Copyright  : Tamim Mostafa 2013
Licence    : GNU General Public License
Description: Displays a calendar in a given module position
*********************************************************************/

defined('_JEXEC') or die('Restricted access');

//-------------------------------------------------------------------------------
// Define cal_days_in_month() in case server doesn't support it
//
if (!function_exists('cal_days_in_month')) 
{
	function cal_days_in_month($calendar,$month, $year)
		{ 
		return date('t', mktime(0, 0, 0, $month+1, 0, $year)); 
		}
}

//---------------------------------------------------------------------------------------------
// Get an array of day names in the current language
//
function get_day_names($start_day)
{
	$j_days = array(JText::_('SUNDAY'),JText::_('MONDAY'),JText::_('TUESDAY'),JText::_('WEDNESDAY'),JText::_('THURSDAY'),JText::_('FRIDAY'),JText::_('SATURDAY'));
	for ($i = 0; $i < 7; $i++)
		{
		$day = ($i + $start_day) % 7;
		$days[] = $j_days[$day];
		}
	return $days;
}

//---------------------------------------------------------------------------------------------
// Get a month name in the current language
//
function get_month_name($month)
{
	switch ($month)
		{
		case 1: return JText::_('JANUARY');
		case 2: return JText::_('FEBRUARY');
		case 3: return JText::_('MARCH');
		case 4: return JText::_('APRIL');
		case 5: return JText::_('MAY');
		case 6: return JText::_('JUNE');
		case 7: return JText::_('JULY');
		case 8: return JText::_('AUGUST');
		case 9: return JText::_('SEPTEMBER');
		case 10: return JText::_('OCTOBER');
		case 11: return JText::_('NOVEMBER');
		case 12: return JText::_('DECEMBER');
		}
}

//---------------------------------------------------------------------------------------------
// Draw a calendar for one month in any language
// $link is blank for no links, or a url ready to append 'p' for previous or 'n' for next
//
function make_calendar($year, $month, $link = '', $day_name_length, $start_day, $weekHdr, $debug=false)
{
	$current_year = date('Y');
	$current_month = date('m');
	$current_day = date('d');
	$num_columns = 7;										// without week numbers, we have 7 columns
	if (($weekHdr != '') and ($start_day == 1) and (!stristr(PHP_OS, 'WIN')))
		$num_columns = 8;
	else
		$weekHdr = '';										// if start day not Monday, or we are on Windows, don't do week numbers
	
	echo "\n".'<table class="mod_minical_table" align="center" cellspacing="0">'."\n";
	echo "\n<tr>";
	
// draw the month and year heading in the current language

	echo '<th colspan="'.$num_columns.'">';
	if ($link != '')
		echo '<a href="'.$link.'p"><span class="mod_minical_left">&laquo;</span></a>&nbsp;&nbsp;';
	$month_string = get_month_name($month).' '.$year;
	echo $month_string;
	if ($link != '')
		echo '&nbsp;&nbsp;<a href="'.$link.'n"><span class="mod_minical_right">&raquo;</span></a>';
	echo '</th>';
	echo '</tr>';
	
// draw the day names heading in the current language

	if ($day_name_length > 0)
		{
		echo "\n<tr>";
		if ($weekHdr != '')
			echo "<th>".$weekHdr."</th>";
		$days = get_day_names($start_day);
		for ($i = 0; $i < 7; $i++)
			{
			$day_name = $days[$i];
			if (function_exists('mb_substr'))
				$day_short_name = mb_substr($day_name,0,$day_name_length,'UTF-8');	// prefer this if available
			else
				$day_short_name = substr($day_name,0,$day_name_length);		// use this if no mbstring library
			echo "<th>$day_short_name</th>";
			}
		echo '</tr>';
		}
	
// draw the days

	$day_time = gmmktime(5,0,0,$month,1,$year);			// GMT of first day of month
	if ($debug)
		mc_trace("\nStart:      ".gmstrftime("%Y-%m-%d %H:%M (wk %V)",$day_time));
	$first_weekday = gmstrftime("%w",$day_time);		// 0 = Sunday ... 6 = Saturday
	$first_column = ($first_weekday + 7 - $start_day) % 7; 	// column for first day
	$days_in_month = cal_days_in_month(CAL_GREGORIAN,$month,$year);
	echo '<tr>';
	if ($weekHdr != '')
		{
		$weeknumber = gmstrftime("%V",$day_time);			// first week number (doesn't work on Windows)
		echo '<td class="mod_minical_weekno">'.$weeknumber.'</td>';
		}
	for ($i = 0; $i < $first_column; $i++)
		echo '<td></td>';
	$column_count = $first_column;
	for ($day = 1; $day <= $days_in_month; $day++)
		{
		if ($column_count == 7)
			{
			echo "</tr>\n<tr>";
			$column_count = 0;
			if ($weekHdr != '')
				{
				// $day_time = strtotime(strftime('%Y-%m-%d',$day_time).' + 1 week');
				$day_time += 604800; 		// add exactly one week
				if ($debug)
					mc_trace(" next week: ".gmstrftime("%Y-%m-%d %H:%M (wk %V)",$day_time));
				$weeknumber = gmstrftime("%V",$day_time);	// week number
				echo '<td class="mod_minical_weekno">'.$weeknumber.'</td>';
				}
			}
		if (($year == $current_year) and ($month == $current_month) and ($day == $current_day))
			echo '<td id="mod_minical_today"'.'>'.$day.'</td>';	// highlight today's date
		else
			echo  '<td>'.$day.'</td>';
		$column_count ++;
		}
	for ($i = $column_count; $i < 7; $i++)
		echo '<td></td>';
	echo "</tr>";
	echo "<tr><b>";
	echo date('H:i, jS F Y');
	echo "</b></tr></table>\n";
	
}

function mc_init_debug()
{
	$locale = setlocale(LC_ALL,0);
	$langObj = JFactory::getLanguage();
	$version = new JVersion();
	$xml_array = JApplicationHelper::parseXMLInstallFile(JPATH_ROOT.'/modules/mod_minicalendar/mod_minicalendar.xml');
	mc_trace("\nSimple Calendar ver : ".$xml_array['version']);
	mc_trace("PHP version      : ".phpversion());
	mc_trace("PHP Locale       : ".print_r($locale, true));
	mc_trace("Server           : ".PHP_OS);
	mc_trace("Joomla Version   : ".$version->RELEASE.".".$version->DEV_LEVEL);
	mc_trace("Joomla Language  : ".$langObj->get('tag'));
}

function mc_trace($data)
{
	@file_put_contents(JPATH_ROOT.'/modules/mod_minicalendar/trace.txt', $data."\n",FILE_APPEND);
}