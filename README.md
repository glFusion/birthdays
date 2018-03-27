# Birthday Plugin for glFusion
This plugin doesn't do much other than let members view/enter/maintain their own birthdates.
It was originally written for Geeklog as an exercise in learning to write plugins.

## Installation
Use the glFusion automated installation.

## Functionality
* Displays birthday records for members for a selected month or for all months.
* Shows a block listing member birthdays for the current and upcoming months(s).
Edit or duplicate the phpblock_birthdays block to add an argument for the desired
number of months. Default is 2.
* Shows a block with birthdays by week. Edit the &quot;birthdays_week&quot; block
and provide the number of weeks as a function argument.
* Birthday Editing is done via the Account Settings. A &quot;Birthday&quot;
field is shown under the &quot;About You&quot; tab.
* Display a &quot;Happy Birthday&quot; message when a user logs in on their birthday.
* Send an e-mail birthday card to users on their birthday.
* Users can subscribe to notifications for other users' birthdays (glFusion 1.7.4+)

## Configuration
* Date Format: Enter the PHP date format to use when displaying dates.
Note that the year is not supported. The global &quot;dateonly&quot; date
format is used if this is empty.
  * Default: M d (Short month, 2-digit day)
* Enable Login Greeting? Set to true to enable the popup &quot;Happy Birthday&quot; message at login.
  * Default: True
* Enable Subscriptions? Set to true to allow users to subscribe to email notificaions for
other users' birthdays.
  * Requires glFusion 1.7.4+
  * Default: true
* Enable Birthday Cards? Set to true to have a &quot;Happy Birthday&quot; message sent to
site users on their birthday.
  * Default: true

## Credits
* Original version for Geeklog (copyright 2003) by Mike Lynn (mike@mlynn.com).
* This plugin was written using the Universal Plugin and the Plugin Developers API.
* Thanks to Blaine Lang, Tom Willett and Vincent Furia for <a href=http://gplugs.sourceforge.net/pluginman/>this document</a>.
* Updated for glFusion 1.7.0+ (2018) by Lee Garner.

## License
This program is free software; you can redistribute it and/or modify it under
the terms of the GNU General Public License as published by the Free Software
Foundation; either version 2 of the License, or (at your option) any later
version.
