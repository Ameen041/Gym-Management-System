<?php

return [

    /*
    | Which proxies may tell the app the visitor's address and https (X-Forwarded-* headers).
    | Empty: none, the visitor connects to the web server directly (shared hosting).
    | "*": whatever connects to the app is a proxy; use it only when that proxy replaces the
    | headers it receives, like the Caddy in deploy/, or anyone could fake their address and
    | get around the login limits. A list of addresses ("10.0.0.1,10.0.0.2") also works.
    */
    'proxies' => env('TRUSTED_PROXIES') ?: null,

];
