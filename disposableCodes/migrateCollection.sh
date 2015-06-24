#!/bin/bash
tar zxvf FacebookMongo.tgz
mongorestore --db Mnemono FacebookMongo/directory
mongo < renameCol.js
mongo < reviseRef.js 
