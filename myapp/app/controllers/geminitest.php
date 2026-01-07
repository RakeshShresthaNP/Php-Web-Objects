<?php

/**
 # Copyright Rakesh Shrestha (rakesh.shrestha@gmail.com)
 # All rights reserved.
 #
 # Redistribution and use in source and binary forms, with or without
 # modification, are permitted provided that the following conditions are
 # met:
 #
 # Redistributions must retain the above copyright notice.
 */
use Gemini\Data\Blob;
use Gemini\Enums\MimeType;

final class cGeminiTest extends cController
{

    public function __construct()
    {
        parent::__construct();
    }

    public function index()
    {
        // https://aistudio.google.com/app/apikey get api key from here
        $client = Gemini::factory()->withApiKey(GEMINI_API_KEY)
            ->withHttpClient(new GuzzleHttp\Client([
            'timeout' => 3600
        ]))
            ->make();

        $result = $client->generativeModel(model: 'gemini-2.0-flash')->generateContent([
            'What is this picture?',
            new Blob(mimeType: MimeType::IMAGE_JPEG, data: base64_encode(file_get_contents('https://storage.googleapis.com/generativeai-downloads/images/scones.jpg')))
        ]);

        $result->text();
    }
}
