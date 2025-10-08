<?php

namespace App\Services;

use App\Models\Job\Job;

class JobNumberService
{
    public function next(): string
    {
        $lastId = (int) (Job::max('id') ?? 0);
        return 'JOB-'.now()->format('Y').'-'.str_pad($lastId + 1, 6, '0', STR_PAD_LEFT);
    }
}
