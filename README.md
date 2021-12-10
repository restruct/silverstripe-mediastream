SilverStripe MediaStream module
===============================

Add a timeline of combined cross-platform updates (e.g. combine your Facebook + Instagram + News updates into one timeline).

[Works, but work in progress]

Includes code/fragments from:
https://github.com/tractorcow/silverstripe-twitter


## Requirements
SilverStripe 4.1 or higher

##Setup
When creating a Facebook app, the TYPE must be NONE
"Create an app with combinations of consumer and business permissions and products."

For Instagram add the "Instagram Basic Display" product

## For emoticons work charset must be set to utf8mb4:

SilverStripe\ORM\Connect\MySQLDatabase:
connection_charset: utf8mb4
connection_collation: utf8mb4_general_ci
charset: utf8mb4
collation: utf8mb4_general_ci