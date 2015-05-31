#!/bin/bash

for windowSize in 1 3 7
do
	echo "php -d memory_limit=1500M runTimestampSeries.php $windowSize > fbReport$windowSize.csv"
	php -d memory_limit=1500M runTimestampSeries.php $windowSize > fbReport$windowSize.csv
done
zip fbReport.zip fbReport*.csv
echo "php mailAttachment.php 'fbReport with 1 3 7' fbReport.zip"
php mailAttachment.php 'fbReport with 1 3 7' fbReport.zip