#!/bin/bash
BASEDIR=$(dirname $0)
cd $BASEDIR

echo "rm fbReport*.csv"
rm fbReport*.csv

DateStr=$(date +"%F")
for windowSize in 1 3 7
do
    for city in mo hk
    do
        echo "php runTimestampSeries.php $windowSize fbReport-$city-$windowSize-$DateStr.csv $city"
        php runTimestampSeries.php $windowSize "fbReport-$city-$windowSize-$DateStr.csv" $city
	done
done
echo "zip fbReport-$DateStr.zip fbReport*.csv"
zip "fbReport-$DateStr.zip" fbReport*.csv
echo "mv fbReport-$DateStr.zip fbReport/"
mv "fbReport-$DateStr.zip" fbReport/
