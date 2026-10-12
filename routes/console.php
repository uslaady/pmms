<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('pmms:check-project-health')->dailyAt('06:00');
