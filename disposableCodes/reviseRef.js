use Mnemono;
db.FacebookFeed.find().snapshot().forEach(
  function (e) {
  	//e.pageID = e.fbPage.$id;
  	e.fbPage.$ref = "FacebookPage";
  	db.FacebookFeed.save(e);
  }
)

db.FacebookFeedTimestamp.find().snapshot().forEach(
  function (e) {
  	//e.pageID = e.fbPage.$id;
  	e.fbPage.$ref = "FacebookPage";
  	db.FacebookFeedTimestamp.save(e);
  }
)