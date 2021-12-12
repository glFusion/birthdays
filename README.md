# Birthday Plugin for glFusion
This plugin doesn't do much other than let members view/enter/maintain their own birthdates.
It was originally written for Geeklog as an exercise in learning to write plugins.

Site members can edit their birthday from the main page (/birthdays/index.php) or
from the `About You` tab under Account Settings.

## Installation
Use the glFusion automated installation.

## Functionality
* Displays birthday records for members for a selected month or for all months.
* Site members can update their birthday on the plugin index page or from their
Account Settings page.
* Shows a block listing member birthdays for the current and upcoming months and weeks.
  * Edit or duplicate the phpblock_birthdays_month or phpblock_birthdays_week block
to add an argument for the desired number of months or weeks. Default is 2.
  * Note: If you add a parameter to these functions, they will not be deleted
when the plugin is deleted and must be deleted manually.
* Birthday submission and editing can be done from the plugin's homepage, or
in the Account Settings. A &quot;Birthday&quot; field is shown under the
&quot;About You&quot; tab.
* Display a &quot;Happy Birthday&quot; message when a user logs in on their birthday.
* Send an e-mail birthday card to users on their birthday.
* Users can subscribe to notifications for other users' birthdays (glFusion 1.7.4+)
* Calls PLG_itemSaved() to notify other plugins when a birthday is submitted.

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
* Group allowed to view birthdays. Set the group that can see the plugin homepage
and the birthday field in other users' profiles.
  * Default: All logged-in users

## Evlist Integration
If you wish to have birthdays appear in a separate calendar in Evlist, go into the Evlist
plugin configuration and add a new Plugin-Calendar Mapping under Integrations. Name the item "birthdays" and select the Evlist calendar to use for birthday events.

## Management
The only option in the Administration area is to sync all birthdays.
This simply re-saves all records, thus forcing other plugins to be
notified of the birthdays. You may wish to do this if you do not have Evlist
installed when you start using Birthdays but then wish to have existing
birthdays appear in the calendar.

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
