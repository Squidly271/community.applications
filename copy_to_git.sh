#!/bin/bash

mkdir -p "/tmp/GitHub/community.applications/source/community.applications/usr/local/emhttp/plugins/community.applications/"

cp /usr/local/emhttp/plugins/community.applications/* /tmp/GitHub/community.applications/source/community.applications/usr/local/emhttp/plugins/community.applications -R -v -p
cd /tmp/GitHub/community.applications/source/community.applications/usr/local/emhttp/plugins/community.applications
rm -f  ca.md5
find . -type f -exec md5sum {} + > /tmp/ca.md5
mv /tmp/ca.md5 ca.md5


