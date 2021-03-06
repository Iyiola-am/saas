<?php

namespace App\Models;

use App\Models\User;
use App\Models\OrganizationCategory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Organization extends Model
{
    use SoftDeletes;

    protected $touches = ['user'];

    /** @var array */
    protected $dates = ['created_at', 'updated_at', 'deleted_at'];

    /** @var array */
    protected $hidden = ['id', 'updated_at', 'deleted_at', 'category_id'];

    /**
     * Get array representation of the organization.
     *
     * @return array
     */
    public function toArray(): array
    {
        $data = parent::toArray();
        $extraData = [
            'category' => OrganizationCategory::select(['id', 'name'])->where('id', $this->category_id)->first()->toArray()
        ];
        $data = array_merge($extraData, $data);

        return $data;
    }

    /**
     * Get the category for an organization.
     *
     * @return BelongsTo
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(OrganizationCategory::class, 'category_id', 'id');
    }

    /**
     * Get the user instance.
     *
     * @return MorphOne
     */
    public function user(): MorphOne
    {
        return $this->morphOne(User::class, 'userable');
    }

    /**
     * Get the jobs the organization has posted.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasManyThrough
     */
    public function jobs(): HasManyThrough
    {
        return $this->hasManyThrough(Job::class, User::class, 'userable_id', 'user_id');
    }
}
