#!/bin/bash
php artisan key:generate
php artisan optimize:clear
php artisan migrate:fresh --seed
php artisan passport:install --force
php artisan serve
