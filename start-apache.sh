#!/bin/bash

# Konfigurasi Apache untuk Laravel
export DOCUMENT_ROOT=/app/public
export PORT=${PORT:-8080}

# Jalankan Apache
exec apache2-foreground
