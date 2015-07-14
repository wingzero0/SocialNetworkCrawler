use Mnemono;
db.FacebookPage.find(
    {mnemono:{$exists:false}}
).limit(10).forEach(function(e){
    print(e._id);
    e.mnemono = {
        "category": e.mnemonoCat,
        "location": {"city":"mo", "country":"cn"}
    };
    delete e.mnemonoCat;
    delete e.mnemonoLocation;
    db.FacebookPage.save(e);
});

use Mnemono;
db.FacebookPage.find({"mnemono.location.city":"mo"}).forEach(function(e){
    print(e._id);
    e.mnemono.crawlTime = [0, 6, 12, 18];
    db.FacebookPage.save(e);
});

use Mnemono;
db.FacebookPage.find({"mnemono.location.city":"hk"}).forEach(function(e){
    print(e._id);
    e.mnemono.crawlTime = [5, 11, 17, 23];
    db.FacebookPage.save(e);
});