#
# Table structure for table `prefix_internalmail`
#

CREATE TABLE prefix_internalmail (
  id bigserial NOT NULL,
  course numeric(10) NOT NULL CHECK (course >=0)DEFAULT 0,
  notext VARCHAR(8) NOT NULL CHECK (notext IN('single','news','general','social','eachuser','teacher')) DEFAULT 'general',
  name VARCHAR(255) NOT NULL DEFAULT '',
  intro text NOT NULL DEFAULT '',
  open numeric(2) NOT NULL CHECK (open >=0) DEFAULT 2,
  assessed numeric(10) NOT NULL CHECK(assessed >=0) DEFAULT 0,
  assesspublic numeric(4) NOT NULL CHECK(assesspublic >=0) DEFAULT 0,
  assesstimestart numeric(10) NOT NULL CHECK(assesstimestart >=0) DEFAULT 0,
  assesstimefinish numeric(10) NOT NULL CHECK (assesstimefinish >=0) DEFAULT 0,
  scale numeric(10) NOT NULL DEFAULT 0,
  maxbytes numeric(10) NOT NULL CHECK(maxbytes >=0) DEFAULT 0,
  forcesubscribe numeric(1) NOT NULL CHECK (forcesubscribe >=0) DEFAULT 0,
  rsstype numeric(2) NOT NULL CHECK(rsstype >=0) DEFAULT 0,
  rssarticles numeric(2) NOT NULL CHECK (rssarticles >=0) DEFAULT 0,
  timemodified numeric(10) NOT NULL CHECK (timemodified >=0) DEFAULT 0,
  CONSTRAINT internalmail_pkey PRIMARY KEY (id),
  CONSTRAINT internalmail_keyuniq UNIQUE (id));

#
# Table structure for table `prefix_internalmail_discussions`
#

CREATE TABLE prefix_internalmail_discussions (
  id bigserial NOT NULL,
  course numeric(10) NOT NULL CHECK(course>=0) DEFAULT 0,
  internalmail numeric(10) NOT NULL CHECK(internalmail>=0) DEFAULT 0,
  name VARCHAR(255) NOT NULL DEFAULT '',
  firstpost numeric(10) NOT NULL CHECK(firstpost>=0) DEFAULT 0,
  userid numeric(10) NOT NULL CHECK(userid>=0) DEFAULT 0,
  groupid numeric(10) NOT NULL DEFAULT -1,
  assessed numeric(1) NOT NULL DEFAULT 1,
  timemodified numeric(10) NOT NULL CHECK(timemodified>=0) DEFAULT 0,
  usermodified numeric(10) NOT NULL CHECK(usermodified>=0) DEFAULT 0,
  CONSTRAINT simple_discussion_pkey PRIMARY KEY(id));

#
# Table structure for table `prefix_internalmail_posts`
#

CREATE TABLE prefix_internalmail_posts (
  id bigserial NOT NULL,
  discussion numeric(10) NOT NULL CHECK(discussion>=0) DEFAULT 0,
  parent numeric (10) NOT NULL CHECK(parent>=0) DEFAULT 0,
  oldparent numeric (10) NOT NULL CHECK(oldparent>=0) DEFAULT 0,
  userid numeric(10) NOT NULL CHECK(userid>=0) DEFAULT 0,
  created numeric(10) NOT NULL CHECK (created>=0) DEFAULT 0,
  modified numeric(10) NOT NULL CHECK (modified>=0) DEFAULT 0,
  mailed numeric(2) NOT NULL CHECK (mailed>=0) DEFAULT 0,
  subject VARCHAR(255) NOT NULL DEFAULT '',
  message text NOT NULL,
  format numeric(2) NOT NULL DEFAULT 0,
  attachment VARCHAR(100) NOT NULL DEFAULT '',
  totalscore numeric(4) NOT NULL DEFAULT 0,
  course numeric(10) DEFAULT 0,
  CONSTRAINT simple_posts_pkey PRIMARY KEY (id));    

#
# Table structure for table `prefix_internalmail_history`
#


CREATE TABLE prefix_internalmail_history (

  id bigserial NOT NULL,
  mailid numeric(10) NOT NULL CHECK(mailid>=0),
  time numeric(10) NOT NULL CHECK (time >=0),
  event VARCHAR(10) NOT NULL,
  userid numeric(10) NOT NULL CHECK (userid>=0),
  parent numeric(10) CHECK (parent >=0),
  CONSTRAINT simple_history_pkey PRIMARY KEY(id));

#
# Table structure for table `prefix_internalmail_aliases`
#


CREATE TABLE prefix_internalmail_aliases(
  id bigserial NOT NULL,
  mailid numeric(10) NOT NULL CHECK(mailid>=0),
  CONSTRAINT simple_aliases_pkey PRIMARY KEY(id));													

#
# Table structure for table `prefix_internalmail_copiesenabled`
#


CREATE TABLE prefix_internalmail_copiesenabled(
  userid numeric(10) NOT NULL CHECK(userid>=0),
  courseid numeric(10) NOT NULL CHECK (courseid>=0),
  PRIMARY KEY(userid,courseid));

#
# Table structure for table `prefix_internalmail_contacts`
#


CREATE TABLE prefix_internalmail_contacts(
  id bigserial NOT NULL,
  userid numeric(10) NOT NULL CHECK(userid>=0),
  address VARCHAR(255) NOT NULL,
  firstname VARCHAR(20) DEFAULT '',
  lastname VARCHAR(20) DEFAULT '',
  format numeric(2) NOT NULL DEFAULT 0,
  description text,
  PRIMARY KEY(id),
  UNIQUE(userid,address));

#
# Table structure for table `prefix_internalmail_groups`
#


CREATE TABLE prefix_internalmail_groups(
  id bigserial NOT NULL,
  userid numeric(10) NOT NULL CHECK(userid>=0),
  groupname VARCHAR(100) NOT NULL,
  contacts text NOT NULL,
  PRIMARY KEY(id),
  UNIQUE (userid,groupname));

#
# Table structure for table `prefix_internalmail_subscriptions`
#

CREATE TABLE prefix_internalmail_subscriptions(
  id bigserial NOT NULL,
  userid numeric(10) NOT NULL CHECK(userid>=0),
  PRIMARY KEY(id),
  UNIQUE(userid));


CREATE TABLE prefix_internalmail_block (
  id bigserial NOT NULL,
  name VARCHAR(40) NOT NULL default '',
  version numeric(10) NOT NULL,
  cron int4 default NULL,
  lastcron int4 NOT NULL,
  visible numeric(1) NOT NULL default '1',
  multiple numeric(1) NOT NULL default '0',
  PRIMARY KEY  (id));

CREATE TABLE prefix_internalmail_block_instance (
  id bigserial NOT NULL,
  blockid numeric(10) NOT NULL CHECK(blockid>=0),
  pageid numeric(10) NOT NULL CHECK(pageid>=0),
  pagetype VARCHAR(20) NOT NULL,
  position VARCHAR(10) NOT NULL,
  weight numeric(3) NOT NULL,
  visible numeric(1) NOT NULL,
  PRIMARY KEY  (id)
);
													
# --------------------------------------------------------
/*INSERT INTO prefix_internalmail_block VALUES ('0','unique_template',200504200,0,0,1,0);*/
/*INSERT INTO prefix_internalmail_block VALUES ('1','block_template',200504200,0,0,1,1);*/
INSERT INTO prefix_internalmail_block VALUES ('2','courses',200504200,0,0,1,0);
INSERT INTO prefix_internalmail_block VALUES ('3','contacts',200504200,0,0,1,0);
INSERT INTO prefix_internalmail_block VALUES ('4','search',200504200,0,0,1,0);

INSERT INTO prefix_internalmail_block VALUES ('5','search_contacts',200603210,0,0,1,0);
INSERT INTO prefix_internalmail_block VALUES ('6','courses_notify',200603210,0,0,1,0);

#/* INSERT MUST BE WITHOUT THE SERIAL PARAM. TO AUTO-INCREMENT*/

INSERT INTO prefix_internalmail_block_instance (blockid, pageid, pagetype, position, weight, visible)
 VALUES (2,1,'mod_view','l',0,1);
INSERT INTO prefix_internalmail_block_instance (blockid, pageid, pagetype, position, weight, visible)
 VALUES (3,1,'mod_view','r',0,1);
INSERT INTO prefix_internalmail_block_instance (blockid, pageid, pagetype, position, weight, visible)
VALUES (4,1,'mod_view','l',0,1);

INSERT INTO prefix_internalmail_block_instance (blockid, pageid, pagetype, position, weight, visible)
VALUES (5,1,'mod_view','r',0,1);
INSERT INTO prefix_internalmail_block_instance (blockid, pageid, pagetype, position, weight, visible)
VALUES (6,1,'mod_view','l',0,1);


#-------------------------------------------------
#
# Dumping data for table `log_display`
#

INSERT INTO prefix_log_display VALUES ('internalmail', 'add', 'internalmail', 'name');
INSERT INTO prefix_log_display VALUES ('internalmail', 'update', 'internalmail', 'name');
INSERT INTO prefix_log_display VALUES ('internalmail', 'add discussion', 'internalmail_discuss', 'name');
INSERT INTO prefix_log_display VALUES ('internalmail', 'add post', 'internalmail_posts', 'subject');
INSERT INTO prefix_log_display VALUES ('internalmail', 'update post', 'internalmail_posts', 'subject');
INSERT INTO prefix_log_display VALUES ('internalmail', 'move discussion', 'internalmail_discuss', 'name');
INSERT INTO prefix_log_display VALUES ('internalmail', 'view subscribers', 'internalmail', 'name');
INSERT INTO prefix_log_display VALUES ('internalmail', 'view discussion', 'internalmail_discuss', 'name');
INSERT INTO prefix_log_display VALUES ('internalmail', 'view internalmail', 'internalmail', 'name');
INSERT INTO prefix_log_display VALUES ('internalmail', 'subscribe', 'internalmail', 'name');
INSERT INTO prefix_log_display VALUES ('internalmail', 'unsubscribe', 'internalmail', 'name');
