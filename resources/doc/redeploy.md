# redeploy step if you configured supervisor

1. wait until gearman job finish (or backup current job queue)
```
watch -n 3 "gearadmin --status" #coding to viewing current job queue
```

2. stop worker by program supervisor with root permission
```
su
supervisorctl -i
stop mnemonoFbCrawler1:*
stop mnemonoFbCrawler2:*
exit # exit supervisor
exit # exit root permission
```

2. update repo by git with normal account permission
```
git checkout master
git fetch origin --prune
git merge origin/master
composer.phar install #install new dependent php package if need
```

3. start worker by program supervisor with root permission
```
su
supervisorctl -i
start mnemonoFbCrawler1:*
start mnemonoFbCrawler2:*
exit # exit supervisor
exit # exit root permission
```