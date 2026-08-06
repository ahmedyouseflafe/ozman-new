<?php

return [
    'ffmpeg' => env('FFMPEG_BINARY', 'ffmpeg'),
    'ffprobe' => env('FFPROBE_BINARY', 'ffprobe'),
    'video_max_upload_kb' => (int) env('VIDEO_MAX_UPLOAD_KB', 25600),
    'video_height' => (int) env('VIDEO_OUTPUT_HEIGHT', 720),
    'video_crf' => (int) env('VIDEO_OUTPUT_CRF', 28),
];
