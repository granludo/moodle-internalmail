<?PHP // $Id: index.php,v 1.1 2003/09/30 02:45:19 moodler Exp $

  /// This page lists all the instances of internalmail in a particular course
  /// Replace internalmail with the name of your module

require_once("../../config.php");
require_once("lib.php");

//require_variable($id);   // course
$id = required_param('id', PARAM_INT);   // course

if (! $course = get_record("course", "id", $id)) {
    error("Course ID is incorrect");
}

require_login($course->id);

add_to_log($course->id, "internalmail", "view all", "index.php?id=$course->id", "");


/// Get all required strings

$strinternalmails = get_string("modulenameplural", "internalmail");
$strinternalmail  = get_string("modulename", "internalmail");


/// Print the header
$navigation = "";

if ($course->category) {
    $navigation = "<a href=\"../../course/view.php?id=$course->id\">$course->shortname</a> ->";
}

print_header("$course->shortname: $strinternalmails", "$course->fullname", "$navigation $strinternalmails", "", "", true, "", navmenu($course));

/// Get all the appropriate data

if (! $internalmails = get_all_instances_in_course("internalmail", $course)) {
    notice("There are no internalmails", "../../course/view.php?id=$course->id");
    die;
}

/// Print the list of instances (your module will probably extend this)

$timenow = time();
$strname  = get_string("name");
$strweek  = get_string("week");
$strtopic  = get_string("topic");

if ($course->format == "weeks") {
    $table->head  = array ($strweek, $strname);
    $table->align = array ("CENTER", "LEFT");
} else if ($course->format == "topics") {
    $table->head  = array ($strtopic, $strname);
    $table->align = array ("CENTER", "LEFT", "LEFT", "LEFT");
} else {
    $table->head  = array ($strname);
    $table->align = array ("LEFT", "LEFT", "LEFT");
}

foreach ($internalmails as $internalmail) {
    if (!$internalmail->visible) {
	//Show dimmed if the mod is hidden
	$link = "<a class=\"dimmed\" href=\"view.php?id=$internalmail->coursemodule\">$internalmail->name</a>";
    } else {
	//Show normal if the mod is visible
	$link = "<a href=\"view.php?id=$internalmail->coursemodule\">$internalmail->name</a>";
    }

    if ($course->format == "weeks" or $course->format == "topics") {
	$table->data[] = array ($internalmail->section, $link);
    } else {
	$table->data[] = array ($link);
    }
}

echo "<br />";

print_table($table);

/// Finish the page

print_footer($course);

?>
