#!/bin/bash

/usr/local/emhttp/plugins/dynamix/scripts/notify -e "Community Applications" -s "Deleting AppData" -d "Deleting $1" -i "normal"

rm -r -f "$1" >/dev/null 2>&1

/usr/local/emhttp/plugins/dynamix/scripts/notify -e "Community Applications" -s "AppData Deleted" -d "Deleted $1" -i "normal"

