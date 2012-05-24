CREATE TABLE `tl_simpletipp` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `tstamp` int(10) unsigned NOT NULL default '0',
  `competition` varchar(64) NOT NULL default '',
  `matchgroup` varchar(64) NOT NULL default '',
  `deadline` int(10) unsigned NOT NULL default '0',
  `matches` text NULL,
  `teaser` text NULL, 
  `participants` text NULL,
  `published` char(1) NOT NULL default '',
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

CREATE TABLE `tl_simpletipp_tipps` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `tstamp` int(10) unsigned NOT NULL default '0',
  `member_id` int(10) unsigned NOT NULL default '0',
  `match_id` int(10) unsigned NOT NULL default '0',
  `tipp` varchar(5) NOT NULL default '',
  `perfect` int(10) unsigned NOT NULL default '0',
  `difference` int(10) unsigned NOT NULL default '0',
  `tendency` int(10) unsigned NOT NULL default '0',
  `wrong` int(10) unsigned NOT NULL default '0',
  PRIMARY KEY  (`id`),
  UNIQUE KEY `one_tipp_for_user_per_match` (`member_id`, `match_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

CREATE TABLE `tl_simpletipp_matches` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `tstamp` int(10) unsigned NOT NULL default '0',
  `competition` varchar(64) NOT NULL default '',
  `matchgroup` varchar(64) NOT NULL default '',
  `deadline` int(10) unsigned NOT NULL default '0',
  `title` varchar(128) NOT NULL default '',
  `result` varchar(5) NOT NULL default '',
  PRIMARY KEY  (`id`)  
) ENGINE=MyISAM DEFAULT CHARSET=utf8;


CREATE TABLE `tl_module` (
  `simpletipp_groups` text NULL,
  `simpletipp_match_page` int(10) unsigned NOT NULL default '0',
  `simpletipp_matches_page` int(10) unsigned NOT NULL default '0',
  `simpletipp_factor` varchar(16) NOT NULL default ''
  `simpletipp_template` varchar(64) NOT NULL default ''
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
