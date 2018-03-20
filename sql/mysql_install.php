<?php
$_SQL = array(
'birthdays' => "CREATE TABLE gl_birthdays (
  uid int(10) NOT NULL,
  month int(2) default NULL,
  day int(2) default NULL,
  PRIMARY KEY  (uid)
) TYPE=MyISAM;",
);

?>
