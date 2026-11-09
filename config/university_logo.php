<?php

/*
 | Cloudinary settings used only for university logos.
 | Set CLOUDINARY_URL (cloudinary://API_KEY:API_SECRET@CLOUD_NAME) in .env,
 | or the three separate values below. Works without any extra composer package.
 */
return [
    'cloud_url' => env('CLOUDINARY_URL'),
    'cloud_name' => env('CLOUDINARY_CLOUD_NAME'),
    'api_key' => env('CLOUDINARY_API_KEY'),
    'api_secret' => env('CLOUDINARY_API_SECRET'),
    'folder' => env('CLOUDINARY_LOGO_FOLDER', 'elogbook/university-logos'),
];
