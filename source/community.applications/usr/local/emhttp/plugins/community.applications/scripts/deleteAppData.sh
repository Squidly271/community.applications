#!/bin/bash

/usr/local/emhttp/plugins/community.applications/scripts/deleteAppData1.sh "$1" & > /dev/null | at NOW -M >/dev/null 2>&1

