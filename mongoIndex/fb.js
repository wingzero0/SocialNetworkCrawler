use Mnemono;
db.FacebookFeed.dropIndexes();
db.FacebookFeed.createIndex({ "fbID" : 1});
db.FacebookFeed.createIndex({ "created_time":1 })
db.FacebookFeed.createIndex({ "fbPage.$id" : 1, "created_time":1})

db.FacebookFeedTimestamp.dropIndexes();
db.FacebookFeedTimestamp.createIndex({ "fbPage.$id" : -1});
db.FacebookFeedTimestamp.createIndex({ "batchTime" : -1});
db.FacebookFeedTimestamp.createIndex({ "fbPage.$id" : -1 , "batchTime" : -1});
db.FacebookFeedTimestamp.createIndex({ "fbFeed.$id" : -1 , "batchTime" : -1});

db.FacebookPage.dropIndexes();
db.FacebookPage.createIndex({ "fbID" : 1});
db.FacebookPage.createIndex({ "exception" : 1});

db.FacebookPageTimestamp.dropIndexes();
db.FacebookPageTimestamp.createIndex({ "fbPage.$id" : -1});
db.FacebookPageTimestamp.createIndex({ "batchTime" : -1});
db.FacebookPageTimestamp.createIndex({ "fbPage.$id" : -1 , "batchTime" : -1});

db.FacebookExceptionPage.dropIndexes();
db.FacebookExceptionPage.createIndex({ "fbID" : 1});