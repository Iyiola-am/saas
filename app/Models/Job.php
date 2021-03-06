<?php

namespace App\Models;

use App\Models\User;
use App\Models\JobSkill;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Job extends Model
{
    use SoftDeletes;

    /** @var array */
    protected $dates = ['created_at', 'updated_at', 'deleted_at'];

    /** @var array */
    protected $hidden = ['updated_at', 'deleted_at', 'user_id'];

    /**
     * Get the organization that posted this job.
     *
     * @return BelongsTo
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    /**
     * Get the skills that this job requires.
     *
     * @return HasMany
     */
    public function skills(): HasMany
    {
        return $this->hasMany(JobSkill::class, 'job_id', 'id');
    }

    /**
     * Get all job applications.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function applications(): HasMany
    {
        return $this->hasMany(JobApplication::class, 'job_id', 'id');
    }
}
