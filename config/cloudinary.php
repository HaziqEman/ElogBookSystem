<?php

return [
    // Full "cloudinary://key:secret@cloud_name" string. Leave unset locally to keep using public/uploads.
    'url' => env('CLOUDINARY_URL'),

    // Folder inside your Cloudinary media library.
    'folder' => env('CLOUDINARY_FOLDER', 'elogbook'),
];