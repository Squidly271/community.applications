#!/bin/bash

mkdir -p "/tmp/GitHub/community.applications/source/community.applications/usr/local/emhttp/plugins/community.applications/"

cp /usr/local/emhttp/plugins/community.applications/* /tmp/GitHub/community.applications/source/community.applications/usr/local/emhttp/plugins/community.applications -R -v -p
cd /tmp/GitHub/community.applications/source/community.applications/usr/local/emhttp/plugins/community.applications
# Delete Apple Metadata files
find . -maxdepth 9999 -noleaf -type f -name "._*" -exec rm -v "{}" \;
rm -f  ca.md5
find . -type f -exec md5sum {} + > /tmp/ca.md5
mv /tmp/ca.md5 ca.md5


