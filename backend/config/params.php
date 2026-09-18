<?php

return [
    'jwtSecret' => getenv('JWT_SECRET') ?: 'dev-only-change-this-64-character-secret-before-production-12345678',
    'jwtTtl' => (int) (getenv('JWT_TTL') ?: 3600),
    'refreshTokenTtl' => (int) (getenv('REFRESH_TOKEN_TTL') ?: 604800),
    'refreshCookieName' => getenv('REFRESH_COOKIE_NAME') ?: 'book_catalog_refresh',
    'refreshCookieSecure' => filter_var(getenv('COOKIE_SECURE') ?: 'false', FILTER_VALIDATE_BOOL),
    'refreshCookieDomain' => getenv('COOKIE_DOMAIN') ?: '',
    'frontendOrigin' => getenv('FRONTEND_ORIGIN') ?: 'http://localhost:5173',
    'apiPublicUrl' => rtrim(getenv('API_PUBLIC_URL') ?: 'http://localhost:8080/api/v1', '/'),
    'smsPilotApiKey' => getenv('SMSPILOT_API_KEY') ?: '',
    'smsPilotSender' => getenv('SMSPILOT_SENDER') ?: '',
];
