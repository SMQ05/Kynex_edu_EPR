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
    | HTTP request timeout for /provision and /reissue (seconds)
    |--------------------------------------------------------------------------
    | The listener forks provision-cert.sh which waits for certbot. Keep
    | this comfortably above the slowest expected certbot call.
    */
    'listener_timeout_seconds' => (int) env('CERT_LISTENER_TIMEOUT_SECONDS', 120),

    /*
    |--------------------------------------------------------------------------
    | HTTP request timeout for /remove (seconds)
    |--------------------------------------------------------------------------
    | Used by CustomDomainService::removeDomain. Was previously 60 with a
    | drift bug (Job used 120, Service used 60); unified at 120 to match
    | Job's value — `certbot delete` plus nginx-mutate lock acquisition
    | can occasionally take longer than 60s on a busy host.
    */
    'remove_timeout_seconds' => (int) env('CERT_REMOVE_TIMEOUT_SECONDS', 120),

    /*
    |--------------------------------------------------------------------------
    | Connect timeout for HTTP calls to the listener (seconds)
    |--------------------------------------------------------------------------
    | Independent of read timeout. Kept short — TCP connect over the
    | docker network completes in single-digit milliseconds when healthy.
    */
    'http_connect_timeout_seconds' => (int) env('CERT_HTTP_CONNECT_TIMEOUT_SECONDS', 5),

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

    /*
    |--------------------------------------------------------------------------
    | CNAME target shown to schools in DNS instructions
    |--------------------------------------------------------------------------
    | The hostname schools are told to CNAME their custom domain at. Must
    | resolve to a server that the kynex-app reverse proxy is fronting.
    | If the SaaS rebrands or migrates, change this in env without code.
    */
    'cname_target' => env('CERT_CNAME_TARGET', 'kynexedu.com'),

    /*
    |--------------------------------------------------------------------------
    | Renewal sweep window (days)
    |--------------------------------------------------------------------------
    | VerifyPendingCustomDomains re-dispatches provisioning for any
    | verified custom domain whose cert expires within this many days.
    | Belt-and-suspenders alongside the in-container certbot-renew cron.
    | Job's freshness check uses the same value (a cert is "fresh" if it
    | expires more than this many days from now).
    */
    'renewal_sweep_days' => (int) env('CERT_RENEWAL_SWEEP_DAYS', 14),

    /*
    |--------------------------------------------------------------------------
    | Verification token TTL (days)
    |--------------------------------------------------------------------------
    | VerifyPendingCustomDomains skips pending custom domains older than
    | this many days — the school never set the TXT record, so polling
    | DNS forever wastes resources. After this they must re-initiate.
    */
    'verification_token_ttl_days' => (int) env('CERT_VERIFICATION_TOKEN_TTL_DAYS', 7),

    /*
    |--------------------------------------------------------------------------
    | Job retry / timeout tunables
    |--------------------------------------------------------------------------
    | Read by ProvisionCustomDomainCertificate's constructor. Lower job_tries
    | for staging if you want fail-fast diagnostics; raise job_timeout_seconds
    | if a busy host needs more headroom for the listener call.
    */
    'job_tries'           => (int) env('CERT_JOB_TRIES', 3),
    'job_backoff_seconds' => (int) env('CERT_JOB_BACKOFF_SECONDS', 60),
    'job_timeout_seconds' => (int) env('CERT_JOB_TIMEOUT_SECONDS', 180),
];
