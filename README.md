Riak file storage extension for live helper chat
=================

This extension enables cloud storage using Riak as file storage service. It's very easy to setup and enables horizontal scalability regarding chat files storage.

See:
http://basho.com/riak/
http://littleriakbook.com/

How to install?
1. Edit settings/settings.ini.php and activate extension. Add it as the last one
'extensions' => 
      array (
        0 => 'riakfilestorage',
),

2. Rename extension/riakfilestorage/settings/settings.ini.default.php to extension/riakfilestorage/settings/settings.ini.php
Edit settings
extension/riakfilestorage/settings/settings.ini.php

3. Edit nginx configuration to proxy images request to riak directly. Example of that in nginx case see
nginx.conf.example