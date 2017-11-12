# QA checklist
1. upsert page
```
php upsertFbPage.php -f INPUT_FILE_NAME --appId xxx --appSecret xxx
```
2. test exception
```
php movePageToException.php --id MONGO_ID --message TEST_MESSAGE
```
3. crawler (client, worker)
```
php crawlPageFeedQueue.php
php crawlPageFeedWorker.php --appId xxx --appSecret xxx
```
4. report (timestamp, topN)
```
php runTimestampSeries.php WINDOW_SIZE OUTPUT_FILE_NAME CITY
php runTopNReport.php
```
5. dumpDB.php
```
php dumpDB.php -s 2015-07-22T00:00:00+0000 -e 2015-07-23T00:00:00+0000
```
5. ~~get last update time~~