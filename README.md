mv .env.example .env

// Update the details for database please with yours 


// Database Strcture is defined in Migration files under /database/migrations folder


composer install

php artisan migrate 

php artisan serve 

