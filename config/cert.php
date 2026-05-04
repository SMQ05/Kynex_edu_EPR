<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Cert listener URL
    |--------------------------------------------------------------------------
    | Reachable only on the kynex_kynex docker network — never host-exposed.
    | Default points at the kynex-app container's listener on port 9090.
    */
    'listener_url' => env('CERT_LISTENER_URL', 'http://kynex-app:9090'),

    /*
    |--------------------------------------------------------------------------
    | Shared secret with the in-container listener
    |--------------------------------------------------------------------------
    | Constant-time compared by cert-listener.php against the
    | X-Cert-Listener-Secret request header. Generate fresh via
    |   openssl rand -base64 32
    | and write the same value into BOTH /var/www/kynexedu/.env.production
    | AND /var/www/kynex/.env. Never commit a real value.
    */
    'listener_secret' => env('SHARED_CERT_LISTENER_SECRET'),

    /*
    |--------------------------------------------------------------------------
    | HTTP request timeout (seconds)
    |--------------------------------------------------------------------------
    | The listener forks provision-cert.sh which waits for certbot. Keep
    | this comfortably above the slowest expected certbot call.
    */
    'listener_timeout' => (int) env('CERT_LISTENER_TIMEOUT', 120),

    /*
    |--------------------------------------------------------------------------
    | Stub mode
    |--------------------------------------------------------------------------
    | When true, the ProvisionCustomDomainCertificate job logs the intended
    | listener call instead of making it. Sub-phase 3 ships with this true;
    | flipped to false at Sub-phase 5 cutover when the kynex-app listener
    | actually exists.
    */
    'stub_mode' => filter_var(env('CERT_STUB_MODE', true), FILTER_VALIDATE_BOOL),
];
