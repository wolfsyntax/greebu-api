#!/bin/bash
php artisan migrate:refresh --seed
php artisan passport:install --force
