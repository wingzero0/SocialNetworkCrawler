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

