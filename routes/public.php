<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

/*
| The apex: marketing, public walls, the jobs directory and policy pages. Inertia
| pages, server-rendered, no auth and no session. This is the only domain we render on
| the server, because it is the only one that gets crawled.
*/

Route::get('/', fn () => Inertia::render('home/Index'))->name('public.home');
