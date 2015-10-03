#!/bin/bash
BASEDIR=$(dirname $0)
cd $BASEDIR

echo "rm fbReport*.csv fbReport*.zip"
rm fbReport*.csv fbReport*.zip

DateStr=$(date +"%F")
for windowSize in 1 3 7
do
    for city in mo hk
    do
        echo "php runTimestampSeries.php $windowSize fbReport-$city-$windowSize-$DateStr.csv $city"
        php runTimestampSeries.php $windowSize "fbReport-$city-$windowSize-$DateStr.csv" $city
	done
done
zip "fbReport-$DateStr.zip" fbReport*.csv
echo "php mailAttachment.php 'fbReport with 1 3 7' fbReport-$DateStr.zip"
php mailAttachment.php 'fbReport with 1 3 7' fbReport-$DateStr.zip
