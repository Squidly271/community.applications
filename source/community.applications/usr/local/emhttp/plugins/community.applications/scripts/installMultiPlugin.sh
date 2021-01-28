#!/bin/bash
IFS="*" read -r -a array <<< "$1"
for element in "${array[@]}"
do
  /usr/local/sbin/plugin install "$element"
done
/usr/local/emhttp/plugins/community.applications/scripts/updatePluginSupport.php

