#!/bin/bash

echo Getting update information...
/usr/local/emhttp/plugins/dynamix.plugin.manager/scripts/plugin check "$1" > /dev/null 2>&1
echo Installing update...
/usr/local/emhttp/plugins/dynamix.plugin.manager/scripts/plugin update "$1"

