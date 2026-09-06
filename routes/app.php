<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

/*
| The user app. Inertia pages on the `web` session guard. A route returns a page and
| a write returns a redirect, so nothing here carries an `/api` prefix.
*/

Route::get('/', fn () => Inertia::render('welcome/Index'))->name('app.home');
