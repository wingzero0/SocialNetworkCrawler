#!/bin/bash
BASEDIR=$(dirname $0)
cd $BASEDIR

echo "rm fbReport*.csv fbReport*.zip"
rm fbReport*.csv fbReport*.zip

DateStr=$(date +"%F")
for windowSize in 1 3 7
do
	echo "php -d memory_limit=1500M runTimestampSeries.php $windowSize fbReport$windowSize-$DateStr.csv"
	php -d memory_limit=1500M runTimestampSeries.php $windowSize "fbReport$windowSize-$DateStr.csv"
done
zip "fbReport-$DateStr.zip" fbReport*.csv
echo "php mailAttachment.php 'fbReport with 1 3 7' fbReport-$DateStr.zip"
php mailAttachment.php 'fbReport with 1 3 7' fbReport-$DateStr.zip
