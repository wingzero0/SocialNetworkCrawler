<?php

const ENV_FILE = '.env';
const PROCESS_SECTIONS = true;
const REQUIRED_KEYS = [
    'GEARMAN_HOST',
    'GEARMAN_PORT',
    'MONGODB_URI',
    'MONGODB_DATABASE',
    'QUEUE_CRAWLER',
    'QUEUE_CREATE_BIZ',
    'QUEUE_UPDATE_BIZ',
    'QUEUE_CREATE_POST',
    'QUEUE_UPDATE_POST',
    'PAGE_CRAWL_TIME',
    'FEED_CRAWL_TIME',
    'POST_BREAKPOINTS',
    'POST_CRAWL_TIME_IN_PERIODS',
    'FB_DEFAULT_GRAPH_VERSION',
    'PAGE_SNAPSHOT_FORCED_SAVE',
    'POST_SNAPSHOT_FORCED_SAVE',
];

function _setConfig($section)
{
    $env = parse_ini_file(ENV_FILE, PROCESS_SECTIONS, INI_SCANNER_TYPED);
    if (false === $env)
    {
        exit('Unable to load environment file ' . ENV_FILE . "\n");
    }

    $config = $env[$section];
    if (empty($config))
    {
        exit('Unable to load section [' . $section . ']' . "\n");
    }
    $intersected = array_intersect(array_keys($config), REQUIRED_KEYS);
    if (count($intersected) !== count(REQUIRED_KEYS))
    {
        $diff = array_diff(REQUIRED_KEYS, $intersected);
        exit(
            '[' . $section . ']' . "\n" .
            'The following variables are required' . "\n" .
            implode(', ', $diff) . "\n"
        );
    }
    if (!is_array($config['POST_BREAKPOINTS']))
    {
        exit(
            '[' . $section . ']' . "\n" .
            'POST_BREAKPOINTS should be an array' . "\n"
        );
    }
    if (!is_array($config['POST_CRAWL_TIME_IN_PERIODS']))
    {
        exit(
            '[' . $section . ']' . "\n" .
            'POST_CRAWL_TIME_IN_PERIODS should be an array' . "\n"
        );
    }
    if (count($config['POST_BREAKPOINTS']) + 1 !==
        count($config['POST_CRAWL_TIME_IN_PERIODS']))
    {
        exit(
            '[' . $section . ']' . "\n" .
            'Make sure (POST_BREAKPOINTS length + 1) is same as ' .
            '(POST_CRAWL_TIME_IN_PERIODS length)' . "\n"
        );
    }

    foreach ($config as $key => $value)
    {
        $_ENV[$key] = $value;
    }
}

function setDefaultConfig()
{
    _setConfig('default');
}

function setTestingConfig()
{
    _setConfig('testing');
}
