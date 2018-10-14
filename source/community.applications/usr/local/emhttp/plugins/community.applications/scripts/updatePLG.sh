#!/bin/bash

echo Getting update information...
/usr/local/emhttp/plugins/dynamix.plugin.manager/scripts/plugin check "$1" > /dev/null 2>&1
UPDATEVER=$(/usr/local/emhttp/plugins/dynamix.plugin.manager/scripts/plugin version /tmp/plugins/$1)
NEWVER=$(/usr/local/emhttp/plugins/dynamix.plugin.manager/scripts/plugin version /var/log/plugins/$1)
if [ $UPDATEVER == $NEWVER ]; then
  echo "Not reinstalling same version"
else
  echo Installing update...
  /usr/local/emhttp/plugins/dynamix.plugin.manager/scripts/plugin update "$1"
fi
