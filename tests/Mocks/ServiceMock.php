<?php

namespace Tests\Mocks;

class ServiceMock
{
    public static function mockS3Upload(): array
    {
        return [
            'success' => true,
            'path' => 'test/' . uniqid() . '.jpg',
            'url' => 'https://fake-s3.test/file.jpg'
        ];
    }
    
    public static function mockEmail(): array
    {
        return [
            'sent' => true,
            'to' => 'test@gmail.com',
            'subject' => 'Test Email'
        ];
    }
}