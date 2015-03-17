#
# Table structure for table `simplemail`
#

CREATE TABLE prefix_internalmail (
  id int(10) unsigned NOT NULL auto_increment,
  course int(10) unsigned NOT NULL default '0',
#  type enum('single','news','general','social','eachuser','teacher') NOT NULL default 'general',
  name varchar(255) NOT NULL default '',
  intro text NOT NULL DEFAULT '',
#  open tinyint(2) unsigned NOT NULL default '2',
#  assessed int(10) unsigned NOT NULL default '0',
#  assesspublic int(4) unsigned NOT NULL default '0',
#  assesstimestart int(10) unsigned NOT NULL default '0',
#  assesstimefinish int(10) unsigned NOT NULL default '0',
#  scale int(10) NOT NULL default '0',
  maxbytes int(10) unsigned NOT NULL default '0',
#  forcesubscribe tinyint(1) unsigned NOT NULL default '0',
#  rsstype tinyint(2) unsigned NOT NULL default '0',
#  rssarticles tinyint(2) unsigned NOT NULL default '0',
  timemodified int(10) unsigned NOT NULL default '0',
  PRIMARY KEY  (id),
  UNIQUE KEY id (id)
) COMMENT='Forums contain and structure discussion';
# --------------------------------------------------------

#
# Table structure for table `simplemail_discussions`
#

CREATE TABLE prefix_internalmail_discussions (
  id int(10) unsigned NOT NULL auto_increment,
  course int(10) unsigned NOT NULL default '0',
  simplemail int(10) unsigned NOT NULL default '0',
  name varchar(255) NOT NULL default '',
  firstpost int(10) unsigned NOT NULL default '0',
  userid int(10) unsigned NOT NULL default '0',
  groupid int(10) NOT NULL default '-1',
  assessed tinyint(1) NOT NULL default '1',
  timemodified int(10) unsigned NOT NULL default '0',
  usermodified int(10) unsigned NOT NULL default '0',
  PRIMARY KEY  (id)
) COMMENT='Forums are composed of discussions';
# --------------------------------------------------------

#
# Table structure for table `simplemail_posts`
#

CREATE TABLE prefix_internalmail_posts (
  id int(10) unsigned NOT NULL auto_increment,
  discussion int(10) unsigned NOT NULL default '0',
  parent int(10) unsigned NOT NULL default '0',
  oldparent int(10) unsigned NOT NULL default '0',  
  userid int(10) unsigned NOT NULL default '0',
  created int(10) unsigned NOT NULL default '0',
  modified int(10) unsigned NOT NULL default '0',
  mailed tinyint(2) unsigned NOT NULL default '0',
  subject varchar(255) NOT NULL default '',
  message text NOT NULL,
  format tinyint(2) NOT NULL default '0',
  attachment VARCHAR(100) NOT NULL default '',
  totalscore tinyint(4) NOT NULL default '0',
  course int(10) unsigned NOT NULL default '0',
  PRIMARY KEY  (id)
) COMMENT='All posts are stored in this table';
# --------------------------------------------------------

CREATE TABLE prefix_internalmail_history (
	id int(10) unsigned NOT NULL auto_increment PRIMARY KEY,
	mailid int(10) unsigned NOT NULL,
	time int(10) unsigned NOT NULL,
	event varchar(10) NOT NULL,
	userid int(10) unsigned NOT NULL,
	parent int(10) unsigned
) COMMENT='Keeps track of all actions related to a message';
														

CREATE TABLE prefix_internalmail_copiesenabled(
	id int(10) unsigned NOT NULL auto_increment PRIMARY KEY,
	userid int(10) unsigned NOT NULL,
	courseid int(10) unsigned NOT NULL,
	UNIQUE KEY (userid,courseid)
) COMMENT='Teachers that want copies of students mails';		


CREATE TABLE prefix_internalmail_subscriptions(
	id int(10) unsigned NOT NULL auto_increment PRIMARY KEY,
	userid int(10) unsigned NOT NULL,
	UNIQUE KEY (userid)
) COMMENT='Subscriptions for email reminder';		


CREATE TABLE prefix_internalmail_contacts(
	id int(10) unsigned NOT NULL auto_increment PRIMARY KEY,
	userid int(10) unsigned NOT NULL,
	address varchar(255) NOT NULL,
	firstname varchar(20) default '',
	lastname varchar(20) default '',
	format tinyint(2) NOT NULL default '0',
	description text,
	UNIQUE KEY(userid,address)
);


CREATE TABLE prefix_internalmail_groups(
	id int(10) unsigned NOT NULL auto_increment PRIMARY KEY,
	userid int(10) unsigned NOT NULL,
	groupname varchar(100) NOT NULL,
	contacts text NOT NULL,
	UNIQUE KEY(userid,groupname)
);

CREATE TABLE prefix_internalmail_aliases(
	id int(10) unsigned NOT NULL auto_increment PRIMARY KEY,
	mailid int(10) unsigned NOT NULL
) COMMENT='Relates copies to the mail that actually sees the recipient';


CREATE TABLE `prefix_internalmail_block` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `name` varchar(40) NOT NULL default '',
  `version` int(10) NOT NULL,
  `cron` integer default NULL,
  `lastcron` integer NOT NULL,
  `visible` tinyint(1) NOT NULL default '1',
  `multiple` tinyint(1) NOT NULL default '0',
  PRIMARY KEY  (`id`)
);

CREATE TABLE `prefix_internalmail_block_instance` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `blockid` int(10) unsigned NOT NULL,
  `pageid` int(10) unsigned NOT NULL,
  `pagetype` varchar(20) NOT NULL,
  `position` varchar(10) NOT NULL,
  `weight` tinyint(3) NOT NULL,
  `visible` tinyint(1) NOT NULL,
  PRIMARY KEY  (`id`)
);
													
# --------------------------------------------------------
#/*INSERT MUST BE WITHOUT THE SERIAL PARAM. TO AUTO-INCREMENT */
#INSERT INTO prefix_internalmail_block (name,version,cron,lastcron,visible,multiple) VALUES ('0','unique_template',200504200,0,0,1,0);
#INSERT INTO prefix_internalmail_block (name,version,cron,lastcron,visible,multiple) VALUES ('1','block_template',200504200,0,0,1,1);

# -- removed all this now. Not needed.
INSERT INTO prefix_internalmail_block (name,version,cron,lastcron,visible,multiple) VALUES ('courses',200504200,0,0,1,0);
INSERT INTO prefix_internalmail_block (name,version,cron,lastcron,visible,multiple) VALUES ('contacts',200504200,0,0,1,0);
INSERT INTO prefix_internalmail_block (name,version,cron,lastcron,visible,multiple) VALUES ('search',200504200,0,0,1,0);
INSERT INTO prefix_internalmail_block (name,version,cron,lastcron,visible,multiple) VALUES ('search_contacts',200603210,0,0,1,0);
INSERT INTO prefix_internalmail_block (name,version,cron,lastcron,visible,multiple) VALUES ('courses_notify',200603210,0,0,1,0);

#-INSERT INTO prefix_internalmail_block_instance (blockid,pageid,pagetype,position,weight,visible) VALUES ('1',1,'mod_view','l',0,1);
#-INSERT INTO prefix_internalmail_block_instance (blockid,pageid,pagetype,position,weight,visible) VALUES ('2',1,'mod_view','r',0,1);
#-INSERT INTO prefix_internalmail_block_instance (blockid,pageid,pagetype,position,weight,visible) VALUES ('3',1,'mod_view','l',0,1);
#-INSERT INTO prefix_internalmail_block_instance (blockid,pageid,pagetype,position,weight,visible) VALUES ('4',1,'mod_view','r',0,1);
#-INSERT INTO prefix_internalmail_block_instance (blockid,pageid,pagetype,position,weight,visible) VALUES ('5',1,'mod_view','l',0,1);


#-------------------------------------------------
#
# Dumping data for table `log_display`
#

INSERT INTO prefix_log_display (module, action, mtable, field) VALUES ('internalmail', 'add', 'internalmail', 'name');
INSERT INTO prefix_log_display (module, action, mtable, field) VALUES ('internalmail', 'update', 'internalmail', 'name');
INSERT INTO prefix_log_display (module, action, mtable, field) VALUES ('internalmail', 'add discussion', 'internalmail_discuss', 'name');
INSERT INTO prefix_log_display (module, action, mtable, field) VALUES ('internalmail', 'add post', 'internalmail_posts', 'subject');
INSERT INTO prefix_log_display (module, action, mtable, field) VALUES ('internalmail', 'update post', 'internalmail_posts', 'subject');
INSERT INTO prefix_log_display (module, action, mtable, field) VALUES ('internalmail', 'move discussion', 'internalmail_discuss', 'name');
INSERT INTO prefix_log_display (module, action, mtable, field) VALUES ('internalmail', 'view subscribers', 'internalmail', 'name');
INSERT INTO prefix_log_display (module, action, mtable, field) VALUES ('internalmail', 'view discussion', 'internalmail_discuss', 'name');
INSERT INTO prefix_log_display (module, action, mtable, field) VALUES ('internalmail', 'view internalmail', 'internalmail', 'name');
INSERT INTO prefix_log_display (module, action, mtable, field) VALUES ('internalmail', 'subscribe', 'internalmail', 'name');
INSERT INTO prefix_log_display (module, action, mtable, field) VALUES ('internalmail', 'unsubscribe', 'internalmail', 'name');
