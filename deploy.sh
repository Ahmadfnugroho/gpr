#!/bin/bash

# Instal dependensi PHP
composer install --no-dev --optimize-autoloader

# Instal dependensi Node.js
npm install

# Bangun aset produksi
npm run prod
