#!/bin/sh
# Test file to check init scripts
uname -a

if [ -n "$EMAIL" ] && [ -n "$PASSWORD" ] && [ -n "$NAME" ] && [ -n "$DB_NAME" ]; then
    echo "<?php" > /var/www/html/config.php
    echo "\$name = \"$NAME\";" >> /var/www/html/config.php
    echo "\$email = \"$EMAIL\";" >> /var/www/html/config.php
    echo "\$password = \"$PASSWORD\";" >> /var/www/html/config.php
    echo "\$table = \"$DB_NAME\";" >> /var/www/html/config.php
    echo "?>" >> /var/www/html/config.php
fi

