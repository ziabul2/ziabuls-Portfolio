<?php
$hash = password_hash('@Zim4080099#', PASSWORD_BCRYPT);
file_put_contents('hash.txt', $hash);
