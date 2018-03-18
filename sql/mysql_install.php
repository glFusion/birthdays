<?php
$_SQL = array(
'birthdays' => "CREATE TABLE gl_birthdays (
  uid int(10) NOT NULL,
  day int(2) default NULL,
  month int(2) default NULL,
  PRIMARY KEY  (uid)
) TYPE=MyISAM;",
);

?>
