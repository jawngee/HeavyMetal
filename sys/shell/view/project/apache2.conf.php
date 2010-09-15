<VirtualHost *:80>
        ServerName {{$domain}}

        DocumentRoot {{$path}}/pub

        <Directory {{$path}}/pub>
                Options Indexes FollowSymLinks
                AllowOverride None

                RewriteEngine On
                RewriteBase /

                RewriteCond %{REQUEST_FILENAME} !-f
                RewriteCond %{REQUEST_FILENAME} !-d
                RewriteRule ^(.*)$ index.php/$1 [L]

                php_flag magic_quotes_gpc off
                php_flag register_globals off
        </Directory>
</VirtualHost>