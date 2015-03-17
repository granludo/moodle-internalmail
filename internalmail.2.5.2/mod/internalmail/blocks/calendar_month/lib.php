<?php
function calendar_top_controls2($type, $data) {
    global $CFG;
    $content = '';
    if(!isset($data['d'])) {
        $data['d'] = 1;
    }

    if(!checkdate($data['m'], $data['d'], $data['y'])) {
        $time = time();
    }
    else {
        $time = make_timestamp($data['y'], $data['m'], $data['d']);
    }
    $date = usergetdate($time);
    
    $data['m'] = $date['mon'];
    $data['y'] = $date['year'];

    switch($type) {
        case 'frontpage':
            list($prevmonth, $prevyear) = calendar_sub_month($data['m'], $data['y']);
            list($nextmonth, $nextyear) = calendar_add_month($data['m'], $data['y']);
//            $nextlink = calendar_get_link_tag('&gt;&gt;', 'view.php?id=15', 0, $nextmonth, $nextyear); //crt
//            $prevlink = calendar_get_link_tag('&lt;&lt;', 'view.php?id=15', 0, $prevmonth, $prevyear);
            $content .= '<table class="calendar-controls"><tr>';
//            $content .= '<td class="previous">'.$prevlink."</td>\n";
            $content .= '<td class="current"><a href="'.calendar_get_link_href(CALENDAR_URL.'view.php?view=month&amp;', 1, $data['m'], $data['y']).'">'.userdate($time, get_string('strftimemonthyear')).'</a></td>';
//            $content .= '<td class="next">'.$nextlink."</td>\n";
            $content .= '</tr></table>';
        break;
        case 'course':
            list($prevmonth, $prevyear) = calendar_sub_month($data['m'], $data['y']);
            list($nextmonth, $nextyear) = calendar_add_month($data['m'], $data['y']);
            $nextlink = calendar_get_link_tag('&gt;&gt;', 'view.php?id='.$data['id'].'&amp;', 0, $nextmonth, $nextyear);
            $prevlink = calendar_get_link_tag('&lt;&lt;', 'view.php?id='.$data['id'].'&amp;', 0, $prevmonth, $prevyear);
            $content .= '<table class="calendar-controls"><tr>';
            $content .= '<td class="previous">'.$prevlink."</td>\n";
            $content .= '<td class="current"><a href="'.calendar_get_link_href(CALENDAR_URL.'view.php?view=month&amp;course='.$data['id'].'&amp;', 1, $data['m'], $data['y']).'">'.userdate($time, get_string('strftimemonthyear')).'</a></td>';
            $content .= '<td class="next">'.$nextlink."</td>\n";
            $content .= '</tr></table>';
        break;
        case 'upcoming':
            $content .= '<div style="text-align: center;"><a href="'.CALENDAR_URL.'view.php?view=upcoming">'.userdate($time, get_string('strftimemonthyear'))."</a></div>\n";
        break;
        case 'display':
            $content .= '<div style="text-align: center;"><a href="'.calendar_get_link_href(CALENDAR_URL.'view.php?view=month&amp;', 1, $data['m'], $data['y']).'">'.userdate($time, get_string('strftimemonthyear'))."</a></div>\n";
        break;
        case 'month':
            list($prevmonth, $prevyear) = calendar_sub_month($data['m'], $data['y']);
            list($nextmonth, $nextyear) = calendar_add_month($data['m'], $data['y']);
            $prevdate = make_timestamp($prevyear, $prevmonth, 1);
            $nextdate = make_timestamp($nextyear, $nextmonth, 1);
            $content .= '<table class="calendar-controls"><tr>';
            $content .= '<td class="previous"><a href="'.calendar_get_link_href('view.php?view=month&amp;', 1, $prevmonth, $prevyear).'">&lt;&lt; '.userdate($prevdate, get_string('strftimemonthyear')).'</a></td>';
            $content .= '<td class="current">'.userdate($time, get_string('strftimemonthyear'))."</td>\n";
            $content .= '<td class="next"><a href="'.calendar_get_link_href('view.php?view=month&amp;', 1, $nextmonth, $nextyear).'">'.userdate($nextdate, get_string('strftimemonthyear'))." &gt;&gt;</a></td>\n";
            $content .= "</tr></table>\n";
        break;
        case 'day':
            $data['d'] = $date['mday']; // Just for convenience
            $dayname = calendar_wday_name($date['weekday']);
            $prevdate = usergetdate(make_timestamp($data['y'], $data['m'], $data['d'] - 1));
            $nextdate = usergetdate(make_timestamp($data['y'], $data['m'], $data['d'] + 1));
            $prevname = calendar_wday_name($prevdate['weekday']);
            $nextname = calendar_wday_name($nextdate['weekday']);
            $content .= '<table class="calendar-controls"><tr>';
            $content .= '<td class="previous"><a href="'.calendar_get_link_href('view.php?view=day&amp;', $prevdate['mday'], $prevdate['mon'], $prevdate['year']).'">&lt;&lt; '.$prevname."</a></td>\n";

            // Get the format string
            $text = get_string('strftimedaydate');
            /*
            // Regexp hackery to make a link out of the month/year part
            $text = ereg_replace('(%B.+%Y|%Y.+%B|%Y.+%m[^ ]+)', '<a href="'.calendar_get_link_href('view.php?view=month&amp;', 1, $data['m'], $data['y']).'">\\1</a>', $text);
            $text = ereg_replace('(F.+Y|Y.+F|Y.+m[^ ]+)', '<a href="'.calendar_get_link_href('view.php?view=month&amp;', 1, $data['m'], $data['y']).'">\\1</a>', $text);
            */
            // Replace with actual values and lose any day leading zero
            $text = userdate($time, $text);
            // Print the actual thing
            $content .= '<td class="current">'.$text.'</td>';

            $content .= '<td class="next"><a href="'.calendar_get_link_href('view.php?view=day&amp;', $nextdate['mday'], $nextdate['mon'], $nextdate['year']).'">'.$nextname." &gt;&gt;</a></td>\n";
            $content .= '</tr></table>';
        break;
    }
    return $content;
}
?>
