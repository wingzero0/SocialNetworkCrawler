# Environment file `.env`

### `.env` vs `.env.example`
* `.env` is the environment file used in the application. It is planned to store sensitive data (e.g., fb app id, fb app secret) and is excluded in git repository.

* `.env.example` is the example of environment file. It should be with the same structure of the latest version of `.env` but no sensitive data inside. Remember to update `.env.example` and push to remote repository, so that others can pull and edit local `.env` accordingly.

### How to use environment file?

`config.php` parses `.env` and assigns all parameters in desired section to global variable `$_ENV`, which will be used in the application.

We first edit `.env` and add these 2 lines in production/development environment,

    require_once(__DIR__ . '/config.php');
    setDefaultConfig();

Or add these 2 lines instead in testing environment

    require_once(__DIR__ . '/config.php');
    setTestingConfig();
