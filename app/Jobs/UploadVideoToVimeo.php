<?php

namespace App\Jobs;

use App\Models\Material;
use Illuminate\Bus\Queueable;
use App\Services\VimeoService;
use Illuminate\Support\Facades\Log;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class UploadVideoToVimeo implements ShouldQueue
{
    use Dispatchable, Queueable, SerializesModels;

    protected $videoPath;
    protected $material;

    /**
     * Create a new job instance.
     *
     * @param string $videoPath
     * @param \App\Models\Material $material
     */
    public function __construct($videoPath, $material)
    {
        $this->videoPath = $videoPath;
        $this->material = $material;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $vimeoService = new VimeoService();
        $vimeoResponse = $vimeoService->uploadVideo($this->videoPath);

        // Update the material with the Vimeo response (video URL or video ID)
        $this->material->update(['video_path' => $vimeoResponse]);

        // Optionally, return the Vimeo response if needed
        return $vimeoResponse;
    }

    // public function handle()
    // {
    //     // Start tracking time before the upload process
    //     $startTime = microtime(true);

    //     $vimeoService = new VimeoService();
    //     $vimeoResponse = $vimeoService->uploadVideo($this->videoPath);

    //     // Calculate the time taken to upload the video
    //     $endTime = microtime(true);
    //     $uploadDuration = $endTime - $startTime; // Time in seconds

    //     // Optionally, log the time taken for the upload (you can store or display this as needed)
    //     Log::info('Video uploaded in ' . $uploadDuration . ' seconds.');

    //     // Update the material with the Vimeo response (video URL or video ID)
    //     $this->material->update(['video_path' => $vimeoResponse]);

    //     // Optionally, return the Vimeo response and the upload duration
    //     return [
    //         'vimeo_response' => $vimeoResponse,
    //         'upload_duration' => $uploadDuration,
    //     ];
    // }
}
