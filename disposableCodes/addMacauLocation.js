use Mnemono;
db.FacebookPage.find({mnemonoLocation:{$exists:false}}).limit(10).forEach(function(e){
    print(e._id);
    e.mnemonoLocation = {"city":"mo", "country":"cn"};
    db.FacebookPage.save(e);
});