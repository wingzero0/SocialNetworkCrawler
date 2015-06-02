#!/bin/bash
rm -rf FacebookMongo/
mongodump --db directory --collection Facebook -o FacebookMongo
mongodump --db directory --collection FacebookFeed -o FacebookMongo
mongodump --db directory --collection FacebookTimestampRecord -o FacebookMongo
tar zcvf FacebookMongo.tgz FacebookMongo/
