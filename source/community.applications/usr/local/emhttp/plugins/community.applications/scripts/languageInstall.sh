#!/bin/bash
if [ "$1" = "update" ]; then
  /usr/local/emhttp/plugins/dynamix.plugin.manager/scripts/language check $2
fi
/usr/local/emhttp/plugins/dynamix.plugin.manager/scripts/language $1 $2
