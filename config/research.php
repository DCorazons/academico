<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Research API token
    |--------------------------------------------------------------------------
    |
    | Stored on the admin user by ResearchSeeder. The load tests send it in the
    | Authorization header of every request to the API routes under test.
    |
    */

    'api_token' => env('RESEARCH_API_TOKEN', 'research-load-test-token'),

];
