<?php

return [
    'url' => env('REPO_URL', 'https://api.github.com/repos/Disrupt-Social-Team/jubilee-cms/actions/workflows/deploy.yml/dispatches'),
    'ref' => env('REPO_REF','master'),
    'apiroot' => env('REPO_APIROOT','https://staging-api.jubileearb.app/api/v1/'),
    'token' => env('REPO_TOKEN', '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'),
];
