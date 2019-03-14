#!/bin/bash
echo "Checking for upgrade";
/usr/local/emhttp/plugins/dynamix.plugin.manager/scripts/plugin check $1
echo "Installing update";
/usr/local/emhttp/plugins/dynamix.plugin.manager/scripts/plugin update $1

