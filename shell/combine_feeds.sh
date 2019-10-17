#!/bin/bash

cd ../media/amfeed/feeds
rm -f combined.xml
touch combined.xml

wget https://www.prokoeling.nl/amfeed/main/get/file/prokoeling_034FA_default_100less/?___store=default -O ->> combined.xml
wget https://www.prokoeling.nl/amfeed/main/get/file/prokoeling_034FA_default_100/?___store=default -O ->> combined.xml
wget https://www.prokoeling.nl/amfeed/main/get/file/prokoeling_034FA_other/?___store=default -O ->> combined.xml
wget https://www.prokoeling.nl/amfeed/main/get/file/prokoeling_034FA_koelingen/?___store=default -O ->> combined.xml
