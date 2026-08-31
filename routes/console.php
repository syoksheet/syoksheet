<?php

use Illuminate\Support\Facades\Schedule;

/*
| Horizon's metrics dashboard is populated by snapshots and by nothing else. Without
| this the dashboard stays permanently blank, which reads as "no traffic" rather than
| "no snapshots" and is exactly the kind of thing nobody notices until they need it.
|
| Everything else in docs/scheduled-jobs.md belongs to a later phase and is registered
| by the feature that owns it.
*/
Schedule::command('horizon:snapshot')->everyFiveMinutes();
