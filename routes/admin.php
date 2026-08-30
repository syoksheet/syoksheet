<?php

use Illuminate\Support\Facades\Route;

/*
| The admin panel. Inertia pages on the `admin` session guard, isolated from the user
| guard in both directions.
*/

Route::get('/', fn () => response('admin'))->name('admin.home');
