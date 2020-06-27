#!/bin/bash

# Marktplaats requires all feeds in a single file

cd ../media/amfeed/feeds
rm -f combined_fixed.xml
touch combined_fixed.xml

cp export_5 _combine_5.tmp.xml
cp export_6 _combine_6.tmp.xml
cp export_7 _combine_7.tmp.xml
cp export_8 _combine_8.tmp.xml

# Remove first line
sed -i -e "1d" _combine_6.tmp.xml
sed -i -e "1d" _combine_7.tmp.xml
sed -i -e "1d" _combine_8.tmp.xml

# Remove last 2 closing tags
sed -i 's/<\/channel><\/rss>//g' _combine_5.tmp.xml
sed -i 's/<\/channel><\/rss>//g' _combine_6.tmp.xml
sed -i 's/<\/channel><\/rss>//g' _combine_7.tmp.xml

cat _combine_5.tmp.xml _combine_6.tmp.xml _combine_7.tmp.xml _combine_8.tmp.xml > combined_fixed.xml

rm _combine_*.tmp.xml
