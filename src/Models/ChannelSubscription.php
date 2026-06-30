<?php

declare(strict_types=1);

namespace DrMaxis\Deploybot\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

class ChannelSubscription extends Model
{
    use HasUlids;

    protected $table = 'deploybot_channel_subscriptions';

    protected $fillable = [
        'team_id',
        'channel_id',
        'channel_name',
        'event_type',
        'subscribed_by_user_id',
    ];

    /**
     * @param  Builder<ChannelSubscription>  $query
     *
     * @return Builder<ChannelSubscription>
     */
    protected function scopeForEvent(Builder $query, string $eventType): Builder
    {
        return $query->where('event_type', $eventType);
    }

    /**
     * @param  Builder<ChannelSubscription>  $query
     *
     * @return Builder<ChannelSubscription>
     */
    protected function scopeForTeam(Builder $query, string $teamId): Builder
    {
        return $query->where('team_id', $teamId);
    }
}
