<?php
$_SQL = array(
'birthdays' => "CREATE TABLE {$_TABLES['birthdays']} (
  uid int(10) NOT NULL,
  month int(2) default NULL,
  day int(2) default NULL,
  PRIMARY KEY (`uid`),
  KEY `mon_day` (`month`,`day`)
) TYPE=MyISAM;",
);

?>
