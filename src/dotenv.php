<?php

if (is_readable(getcwd() . DIRECTORY_SEPARATOR . '.env.default')) {
    $dotenv = new Dotenv\Dotenv(getcwd(), '.env.default');
    $dotenv->overload();
}
if (is_readable(getcwd() . DIRECTORY_SEPARATOR . '.env')) {
    $dotenv = new Dotenv\Dotenv(getcwd());
    $dotenv->overload();
}

register_shutdown_function(function() {
    \AwsInspector\Ssh\Connection::closeMuxConnections();
});
