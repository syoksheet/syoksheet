<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

/*
| The admin panel. Inertia pages on the `admin` session guard, kept separate from the
| user guard both ways. No SSR: it is behind a login and never crawled.
*/

Route::get('/', fn () => Inertia::render('welcome/Index'))->name('admin.home');
