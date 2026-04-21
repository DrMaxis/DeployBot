<?php

declare(strict_types=1);

namespace Afria\Deploybot\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

/**
 * Eloquent model for the `deploybot_channel_subscriptions` table.
 *
 * A row is created when a user runs `/<command> follow <event>` in a
 * channel (Slice H3). Host apps query subscriptions when dispatching
 * a product event:
 *
 * ```php
 * ChannelSubscription::forEvent('alverium.release.published')
 *     ->get()
 *     ->each(fn ($sub) => $slackApi->postMessage($sub->channel_id, $text, $blocks));
 * ```
 *
 * The `event_type` column is intentionally untyped (no enum) because
 * the taxonomy is host-defined — alverium uses
 * `alverium.release.published`, a future product could use
 * `projectx.deploy.started`, etc. The library doesn't validate; host
 * apps declare their event types in their own registry if they need
 * central validation.
 */
final class ChannelSubscription extends Model
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
     * Subscriptions for a single event type across all channels/teams.
     *
     * @param  Builder<ChannelSubscription>  $query
     *
     * @return Builder<ChannelSubscription>
     */
    public function scopeForEvent(Builder $query, string $eventType): Builder
    {
        return $query->where('event_type', $eventType);
    }

    /**
     * Subscriptions in a specific workspace.
     *
     * @param  Builder<ChannelSubscription>  $query
     *
     * @return Builder<ChannelSubscription>
     */
    public function scopeForTeam(Builder $query, string $teamId): Builder
    {
        return $query->where('team_id', $teamId);
    }
}
