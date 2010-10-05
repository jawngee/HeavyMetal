<?
function date_fromstamp($timestamp, $format="n/j/o")
{
	if($timestamp==null)return;
		return date($format,strtotime($timestamp));
}

function datetime_fromstamp($timestamp, $format="n/j/o g:i A")
{
	if($timestamp==null)return;
	if(!is_numeric($timestamp))
		$timestamp=strtotime($timestamp);
	return date($format,$timestamp);
}

function dateattime_fromstamp($timestamp){
	$time_format = "g:i A";
	$date_format = "n/j/o";
	if($timestamp==null){
		return;
	}
	if(!is_numeric($timestamp)){
		$timestamp=strtotime($timestamp);
	}
	$time = date($time_format,$timestamp);
	$date = date($date_format,$timestamp);
	return $date." at ".$time;
}

function dateortime_fromstamp($timestamp){
	$time_format = "g:i A";
	$date_format = "n/j/o";
	if($timestamp==null){
		return;
	}
	if(!is_numeric($timestamp)){
		$timestamp=strtotime($timestamp);
	}
	$time = date($time_format,$timestamp);
	$date = date($date_format,$timestamp);
	if (date($date_format,time ()) == $date) {
		return $time;
	} else {
		return $date;
	}
}

function legal_datetime_fromstamp($timestamp)
{
    return datetime_fromstamp($timestamp, 'F j, Y, g:iA T');
}


function format_runtime($millis)
{
	return sprintf("%02d", round($millis/1000/60)) . ":" .  sprintf("%02d", $millis/1000 % 60);
}


function days_from($date)
{
	if(!is_numeric($date))
		$subject_date=strtotime($date);   
    
    $todays_date = time();
    
    $delta = $subject_date - $todays_date;
    
    return ceil($delta/(60*60*24));
}

function days_since($date)
{
	if(!is_numeric($date))
		$subject_date=strtotime($date);   
    
    $todays_date = time();
    
    $delta = $todays_date - $subject_date;
    
    return ceil($delta/(60*60*24));
}

function get_date_delta($lastdate, $show_date=false, $show_time_today=false, $just_days=false, $format="m/d/y")
{
	if(!is_numeric($lastdate))
		$lastdate=strtotime($lastdate);
	$last_date=getdate($lastdate);
	$current_date=getdate(time());
	$delta=($current_date['yday']+($current_date['year']*365))-($last_date['yday']+($last_date['year']*365));

	if ($delta==0)
    {
        if ($show_time_today)
        {
            $minutes_since = floor(seconds_since($lastdate, 60));
            
            $hour_part = floor($minutes_since/60);
            $hour_string = $hour_part;
            $hour_string .= ($hour_part==1)?' hour':' hours';
            $minute_part = $minutes_since%60;
            $minute_string = $minute_part;
            $minute_string .= ($minute_part==1)?' minute':' minutes';
            
            $time_string = ($hour_part > 0)? $hour_string :'';
            if ($minute_part > 0)
                $time_string .= ($time_string=='')?$minute_string.' ago':' and '.$minute_string.' ago';
                            
                         
            if($minutes_since < 2)
                return 'Just now!';
            else if($minutes_since < 240)
                return $time_string;
            else 
                return 'Earlier Today';
        }
        else
        {
            return 'Today';
        }
    }
	else if ($delta==1)
		return 'Yesterday';
	else if ($delta<7 || $just_days)
		return "$delta Days Ago";
	else if (($delta>=7) && ($show_date))
	{
		return date($format,$lastdate);
	}
	else if (($delta>=7) && ($delta<30))
	{
		return "More than a week ago";
	}
	else if (($delta>30) && ($delta<=365))
		return "More than a month ago";
	else if ($delta>365)
		return "More than a year ago";
}

function get_date_breakdown_delta($timestamp){
	$one_min_secs = 60;
	$one_hour_secs = $one_min_secs*60;
	$one_day_secs = $one_hour_secs*24;
	$one_week_secs = $one_day_secs*7;
	//months kinda suck, would like to do this accurately
	$one_month_secs = $one_week_secs*4;
	$one_year_secs = $one_week_secs*52;
	$breakdown = "";
	
	$difference = seconds_since($timestamp);
	
	if ($difference<60)
		return $difference." ".pluralize($difference,"second","seconds");
	
	$years_diff = floor($difference/$one_year_secs);
	if($years_diff>0){
		$difference = $difference - ($years_diff*$one_year_secs);
		$breakdown = $years_diff." ".pluralize($years_diff,"year","years").", ";
	}
	$months_diff = floor($difference/$one_month_secs);
	if($months_diff>0){
		$difference = $difference - ($months_diff*$one_month_secs);
		$breakdown = $breakdown." ".$months_diff." ".pluralize($months_diff,"month","months").", ";
	}
	$weeks_diff = floor($difference/$one_week_secs);
	if($weeks_diff>0){
		$difference = $difference - ($weeks_diff*$one_week_secs);
		$breakdown = $breakdown." ".$weeks_diff." ".pluralize($weeks_diff,"week","weeks").", ";
	}
	$days_diff = floor($difference/$one_day_secs);
	if($days_diff>0){
		$difference = $difference - ($days_diff*$one_day_secs);
		$breakdown = $breakdown." ".$days_diff." ".pluralize($days_diff,"day","days").", ";
	}
	$hours_diff = floor($difference/$one_hour_secs);
	if($hours_diff>0){
		$difference = $difference - ($hours_diff*$one_hour_secs);
		$breakdown = $breakdown." ".$hours_diff." ".pluralize($hours_diff,"hour","hours").", and ";
	}
	$minutes_diff = floor($difference/$one_min_secs);
	if($minutes_diff>0){
		$difference = $difference - ($minutes_diff*$one_min_secs);
		$breakdown = $breakdown." ".$minutes_diff." ".pluralize($minutes_diff,"minute","minutes");
	}
	return $breakdown;
}

function seconds_since($created, $timepart=null)
{
	if(!is_numeric($created))
		$created=strtotime($created);
	$since_created=time()-$created;
	if($timepart!=null)
		$since_created=$since_created/$timepart;
	return $since_created;
}

/*
 * @created 	- timestamp or date
 * @timepart 	- optional divisor (60 for mins, 60*60 for hours, etc)
 * @return  `	- ec/min/whatever from now (time())
 **/
function seconds_until($created, $timepart=null)
{
	if(!is_numeric($created))
		$created=strtotime($created);
	$since_created=$created-time();
	if($timepart!=null)
		$since_created=$since_created/$timepart;
	return $since_created;
}

function time_to_pgtime($epoch=null)
{
    if (!$epoch)
        $epoch = time();
        
    return date('Y-m-d H:i:s', $epoch);
}

function start_of_day($days)
{
	// returns a unix timestamp $days ago, beginning at midnight
	$now =  date('Y-m-d',time()-$days*24*60*60 );
	$now = explode ('-', $now);
	$round =  mktime('0', '0', '0', $now[1], $now[2], $now[0]);
	return $round;
}
function end_of_day($days)
{
	// returns a unix timestamp $days ago, end at 23:59
	$now =  date('Y-m-d',time()-$days*24*60*60 );
	$now = explode ('-', $now);
	$round =  mktime('23', '59', '59', $now[1], $now[2], $now[0]);
	return $round;
}

function deadline_date($timestamp, $format="F j, Y")
{
	return date($format,strtotime($timestamp));
}

function strip_seconds($timestamp)
{
	$sec_pattern = '/:[0-9]{1,2}:[0-9]{3}/';
	return preg_replace($sec_pattern,'',$timestamp);
}

