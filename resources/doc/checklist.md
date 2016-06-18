# QA checklist
1. upsert page
    php upsertFbPage.php -f inputfile --appId --appSecret
2. test exception
    php movePageToException.php --id MongoID --message TestMessage
2. crawler (client, worker)
3. report (timestamp, topN)
4. get last update time
php movePageToException.php --id 5725f1177f8b9aab0b8b4567 --message shouldBeRemoveAfterRecrawl