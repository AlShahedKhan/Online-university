<?php

namespace App\Services;

use Vimeo\Vimeo;

class VimeoService
{
    protected $vimeo;

    public function __construct()
    {
        // Vimeo API setup
        $this->vimeo = new Vimeo(
            env('VIMEO_CLIENT_ID'),
            env('VIMEO_CLIENT_SECRET'),
            env('VIMEO_ACCESS_TOKEN')
        );
    }

    /**
     * Upload a video to Vimeo.
     */
    public function uploadVideo($filePath)
    {
        $response = $this->vimeo->upload($filePath, [
            "name" => "Uploaded Video",
            "description" => "Video uploaded from Laravel",
        ]);

        return $response; // Returns video URL or Vimeo video ID
    }
}
