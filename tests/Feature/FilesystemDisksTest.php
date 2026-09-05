<?php

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

it('signs a temporary url for private objects', function () {
    $path = 'exports/'.uniqid().'.txt';

    Storage::disk('r2_private')->put($path, 'private');

    $url = Storage::disk('r2_private')->temporaryUrl($path, now()->addMinutes(5));

    expect($url)->toContain('X-Amz-Signature')->toContain($path);

    Storage::disk('r2_private')->delete($path);
});

it('serves a private object through a signed url', function () {
    $path = 'exports/'.uniqid().'.txt';

    Storage::disk('r2_private')->put($path, 'private');

    $url = Storage::disk('r2_private')->temporaryUrl($path, now()->addMinutes(5));

    $response = Http::get($url);

    expect($response->status())->toBe(200)
        ->and($response->body())->toBe('private');

    Storage::disk('r2_private')->delete($path);
});

it('refuses an unsigned request for a private object', function () {
    $path = 'exports/'.uniqid().'.txt';

    Storage::disk('r2_private')->put($path, 'private');

    $bucket = config('filesystems.disks.r2_private.bucket');
    $endpoint = config('filesystems.disks.r2_private.endpoint');

    expect(Http::get("{$endpoint}/{$bucket}/{$path}")->status())->toBe(403);

    Storage::disk('r2_private')->delete($path);
});

it('serves public objects without credentials', function () {
    $path = 'avatars/'.uniqid().'.txt';

    Storage::disk('r2_public')->put($path, 'public');

    $bucket = config('filesystems.disks.r2_public.bucket');
    $endpoint = config('filesystems.disks.r2_public.endpoint');

    $response = Http::get("{$endpoint}/{$bucket}/{$path}");

    expect($response->status())->toBe(200)
        ->and($response->body())->toBe('public');

    Storage::disk('r2_public')->delete($path);
});

it('builds public urls from the configured public domain', function () {
    $publicUrl = config('filesystems.disks.r2_public.url');

    assert(is_string($publicUrl) && $publicUrl !== '');

    expect(Storage::disk('r2_public')->url('avatars/example.png'))->toStartWith($publicUrl);
});
