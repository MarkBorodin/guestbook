## INSTALL APP

## Guestbook

### Setup

First you need to clone the repository:
```
https://github.com/MarkBorodin/guestbook.git
```
And go to the directory with the project. The entire command must be executed in this directory:
```
cd guestbook
```

Postgresql is used as a database and it is started via docker-compose. To start the database, you need to run the command:
```
docker-compose up
```

Next, you need to install all the necessary packages, dependencies, and so on. This is done using the composer tool. To do this, you need to run the command:
```
composer install
```

The next step is to create all the required tables in the database. To do this, you need to perform a migration:
```
bin/console doctrine:migrations:migrate
```

In order to get into the admin panel of the site and start working on it, you need to create a super user. This is done using the following command:

php bin/console app:createsuperuser username password
(where:
username - your username;
password - your password)

Next, you need to install and configure Webpack.  
To do this, you need to run all the commands that are listed below:
```
symfony composer req encore
mv assets/styles/app.css assets/styles/app.scss
yarn add node-sass sass-loader --dev
yarn add bootstrap jquery popper.js bs-custom-file-input --dev
php -r "copy('https://symfony.com/uploads/assets/guestbook-5.2.zip', 'guestbook-5.2.zip');"
unzip -o guestbook-5.2.zip
rm guestbook-5.2.zip
```

Now, you need to assemble resources and run it in the background mode.  
To do this, you need to run the following commands:
```
symfony run yarn encore dev
symfony run -d yarn encore dev --watch
```

run server:
```
symfony server:start
```

run async workers:
```
symfony run -d --watch=config,src,templates,vendor symfony console messenger:consume async
```

run cron to complete cron tasks:
```
php bin/console cronos:replace
```

(open web email)
```
symfony open:local:webmail
```

********

if admin panel does not work:  
```
rm vendor/easycorp/easyadmin-bundle/src/Router/AdminUrlGenerator.php
cp store/AdminUrlGenerator.php vendor/easycorp/easyadmin-bundle/src/Router
```


The server will run on a free port. The server address will be displayed in the console.

### Finish
