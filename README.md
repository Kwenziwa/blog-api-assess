## Installation

Please check the official laravel installation guide for server requirements before you start. [Official Documentation](https://laravel.com/docs/8.4/installation#installation)


Clone the repository

    git clone https://github.com/Kwenziwa/blog-api-assess.git


Install all the dependencies using composer

    composer install — optimize-autoloader — no-dev

Copy the example env file and make the required configuration changes in the .env file

    cp .env.example .env

Open your .env file and change the database name (DB_DATABASE) to whatever you have, username (DB_USERNAME) and password (DB_PASSWORD) field correspond to your configuration. 
Set APP_DEBUG to false and APP_ENV to production, and update the APP_NAME and APP_URL accordingly. 
If you leave APP_DEBUG as true, in the event of errors you’ll be displaying sensitive debug information.

    APP_NAME=Laravel
    APP_ENV=local
    APP_KEY=
    APP_DEBUG=true
    APP_URL=http://localhost

    LOG_CHANNEL=daily
    LOG_LEVEL=debug

    DB_CONNECTION=mysql
    DB_HOST=127.0.0.1
    DB_PORT=
    DB_DATABASE=
    DB_USERNAME=
    DB_PASSWORD=

    MAIL_MAILER=smtp
    MAIL_HOST=mailhog
    MAIL_PORT=1025
    MAIL_USERNAME=null
    MAIL_PASSWORD=null
    MAIL_ENCRYPTION=null
    MAIL_FROM_ADDRESS=null
    MAIL_FROM_NAME="${APP_NAME}"
    
    
   Generate a new application key

    php artisan key:generate


Run the database migrations with seed (**Set the database connection in .env before migrating**)

    php artisan migrate --seed
    
We need to set some folder permissions so they are writeable, specifically the /storage/ and /bootstrap/cache/ folders.
    
    chmod -R o+w storage
    chmod -R o+w bootstrap/cache
    
To make these files accessible from the web, you should create a symbolic link from public/storage to storage/app/public.

    php artisan storage:link
    
Three simple steps for improving performanc

    php artisan config:cache
    php artisan config:clear
    php artisan route:cache
    php artisan route:clear
    php artisan route:cache


Start the local development server

    php artisan serve

You can now access the server at http://127.0.0.1:8000/ or visit your url if its on a live server 

