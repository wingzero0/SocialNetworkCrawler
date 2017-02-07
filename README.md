# Fb crawler
it a background process to crawl fb public page data

## install dependence
- mongodb && php extension
- gearmand && php extension
- supervisord
- composer.phar

### update php library
- composer.phar install


## Process
0. add page, config crawling frequency, category (skip this step if you have page in db)
1. routine job - gearman client
2. crawler - gearman worker
3. sync data to mnemono
4. ~~run report if necessary~~ (only work in old fb api)

### add page

    /* php upsertFbPage.php -f INPUT_FILE_WITH_CONFIG --appId YOUR_APP_ID --appSecret YOUR_APP_SECRET */
    > php upsertFbPage.php -f fbId.sample.txt --appId xxxxxxx --appSecret xxxxxxxxxxxxxx

### routine job - gearman client

create job by command line

    > php /PATH_TO_PROJECT/crawlPageFeedQueue.php

run job each hour. it will create job for each page which marked should be crawled at that moment (marked with hour in raw data).

    /* open cron job config */
    > crontab -e 
    /* paste following line into config file */
    0 */1 * * * php /PATH_TO_PROJECT/crawlPageFeedQueue.php 2>> /PATH_TO_PROJECT/stderr.err

### crawler
start crawler in background.

    > php /PATH_TO_PROJECT/crawlPageFeedWorker.php --appId xxxxxxx --appSecret xxxxxxxxxxxxxx

or 

start the worker by supervisor (set supervisor conf)

    command=php crawlPageFeedWorker.php --appId xxxxxxx --appSecret xxxxxxxxxxxxxx
    numprocs=2
    directory=/PATH_TO_PROJECT/
    autostart=true
    autorestart=true
    stopsignal=KILL
    process_name=%(program_name)s_%(process_num)02d
    stdout_logfile=/home/webmaster/backend/crawler/fbCrawler/%(program_name)s_%(process_num)02d.log
    stdout_logfile_maxbytes=1MB
    stderr_logfile=/home/webmaster/backend/crawler/fbCrawler/%(program_name)s_%(process_num)02d.err
    stderr_logfile_maxbytes=1MB

### run report
run report to show records of N day ago

    /* php /PATH_TO_PROJECT/runTimestampSeries.php N_DAY_AGO OUTPUT_FILE_NAME.csv COUNTRY_CODE */
    > php /PATH_TO_PROJECT/runTimestampSeries.php 7 fbReport.csv mo

### QA checklist
[checklist](/resources/doc)
