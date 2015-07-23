#!/bin/bash
rm -rf MnemonoMongo/
php dumpDB.php -s 2015-07-22T00:00:00+0000 -e 2015-07-23T00:00:00+0000
mongodump --db MnemonoDump --collection FacebookPage -o MnemonoMongo
mongodump --db MnemonoDump --collection FacebookExceptionPage -o MnemonoMongo
mongodump --db MnemonoDump --collection FacebookFeed -o MnemonoMongo
mongodump --db MnemonoDump --collection FacebookFeedTimestamp -o MnemonoMongo
tar zcvf MnemonoMongo.tgz MnemonoMongo/
