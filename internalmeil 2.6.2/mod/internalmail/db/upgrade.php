<?php 

  // This file keeps track of upgrades to 
  // the forum module
  //
  // Sometimes, changes between versions involve
  // alterations to database structures and other
  // major things that may break installations.
  //
  // The upgrade function in this file will attempt
  // to perform all the necessary actions to upgrade
  // your older installtion to the current version.
  //
  // If there's something it cannot do itself, it
  // will tell you what you need to do.
  //
  // The commands in here will all be database-neutral,
  // using the functions defined in lib/ddllib.php

function xmldb_internalmail_upgrade($oldversion=0) {

    global $CFG, $THEME, $db;

    $result = true;

    if ( $oldversion < 2006060601 ) {

	// Fixed the prefix_internalmail_copiesenabled;
	$copiesenabled = get_records('internalmail_copiesenabled');
	drop_table(new XMLDBTable('internalmail_copiesenabled'));

	$field1 = new XMLDBField('id');
	$field1->setAttributes(XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null, null, null, null);
	$field2 = new XMLDBField('userid');
	$field2->setAttributes(XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, null, 'id');
	$field3 = new XMLDBField('course');
	$field3->setAttributes(XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, null, 'userid');

	$key1 = new XMLDBKey('primary');
	$key1->setAttributes(XMLDB_KEY_PRIMARY, array('id'), null, null);
	$key2 = new XMLDBKey('foreignkey1');
	$key2->setAttributes(XMLDB_KEY_FOREIGN, array('course'), 'course', array('id')); 

	$index1 = new XMLDBIndex('userid-course');
	$index1->setAttributes(XMLDB_INDEX_UNIQUE, array('userid','course'));

	$table = new XMLDBTable('internalmail_copiesenabled');
	$table->addField($field1);
	$table->addField($field2);
	$table->addField($field3);

	$table->addKey($key1);
	$table->addKey($key2);

	$table->addIndex($index1);

	$status = create_table($table);
	
	if ( $status && $copiesenabled ) {
	    foreach ($copiesenabled as $cc ) {
		$cc_new->course = $cc->course;
		$cc_new->userid = $cc->userid;
		 $cc_new->id = insert_record('internalmail_copiesenabled', $cc_new);
	    }
	}

	// Add some of the new internalmail blocks
	unset($blockdata);
	$set_search = 0;
	if ( !$search = get_record('internalmail_block', 'name', 'search') ) {
	    $blockdata->name = 'search';
	    $blockdata->version = '200504200';
	    $blockdata->cron = '0';
	    $blockdata->lastcron = '0';
	    $blockdata->visible = '1';
	    $blockdata->multiple = '0';
	    $set_search = insert_record('internalmail_block', $blockdata);
	}

	unset($blockdata);
	$set_search_contacts = 0;
	if ( !$search = get_record('internalmail_block', 'name', 'search_contacts') ) {
	    $blockdata->name = 'search_contacts';
	    $blockdata->version = '200603210';
	    $blockdata->cron = '0';
	    $blockdata->lastcron = '0';
	    $blockdata->visible = '1';
	    $blockdata->multiple = '0';
	    $set_search_contacts = insert_record('internalmail_block', $blockdata);
	}
	unset($blockdata);
	$set_courses_notify = 0;
	if ( !$search = get_record('internalmail_block', 'name', 'courses_notify') ) {
	    $blockdata->name = 'courses_notify';
	    $blockdata->version = '200603210';
	    $blockdata->cron = '0';
	    $blockdata->lastcron = '0';
	    $blockdata->visible = '1';
	    $blockdata->multiple = '0';
	    $set_courses_notify = insert_record('internalmail_block', $blockdata);
	}
	unset($blockdata);

	// get all the internalmail messages and change the location
	// of the files.
	$query = "SELECT DISTINCT attachment,course AS courseid
                    FROM {$CFG->prefix}internalmail_posts
                   WHERE attachment <> '' AND attachment IS NOT NULL";

	// Execute the query
	$results = get_records_sql($query);

	require_once($CFG->dirroot . '/mod/internalmail/lib.php');
	// Check if there are any file attachments
	if ( $results ) {
	    foreach ( $results as $result ) {
		// Change the file location now // $result->attachment
		$newlocation = internalmail_get_attachment_location($result->courseid);

		// Update all the internal mail post what have attachment
		$update_query = "SELECT id, course
                                   FROM {$CFG->prefix}internalmail_posts
                                  WHERE attachment = '" . $result->attachment . "'
                                    AND course = $result->courseid";		

		// Lets move the file to the new location
		$old_file_location = $CFG->dataroot .'/' . $result->courseid ."/$CFG->moddata/internalmail/" . $result->attachment;
		$new_file_dir = $CFG->dataroot .'/' . $result->courseid ."/$CFG->moddata/internalmail/" . $newlocation;

		$status = check_dir_exists($new_file_dir, true, true);

		$new_file_location = $new_file_dir . '/' . $result->attachment;

		if ( file_exists($old_file_location)) {
		    if ( !rename($old_file_location, $new_file_location) ) {
			continue;
		    }
		} else {
		    // Could not find the file. So update the post
		    // with no attachments
		    $new_file_location = NULL;
		    continue;
		}

		if ( $posts = get_records_sql($update_query) ) {
		    foreach ( $posts as $pp ) {
			set_field('internalmail_posts', 'attachment', $newlocation , 'id', $pp->id);
		    }
		}
	    }
	}
    }

    return $result;
}

?>
