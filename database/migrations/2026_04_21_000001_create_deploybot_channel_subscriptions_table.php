<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * `deploybot_channel_subscriptions` — which Slack channels are opted in
 * to which event types.
 *
 * Populated by the `/<command> follow` subcommand that H3 will add (the
 * table exists now so H3 can land as a commands-only PR without
 * schema churn).
 *
 * Host apps dispatch their product-specific events (e.g.
 * `ReleasePublished`) to a listener provided by deploybot; the listener
 * queries this table for matching subscriptions and fans out via
 * `SlackApi::postMessage`.
 *
 * Unique on (team_id, channel_id, event_type) so a channel can't
 * double-subscribe to the same event. Cascade-indexed on team_id +
 * event_type for the common lookup pattern
 * (`where team=? and event_type=? get channels`).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('deploybot_channel_subscriptions', function (Blueprint $table): void {
            $table->ulid('id')->primary();

            // Slack IDs, not internal FKs — a Slack channel may be
            // subscribed before the host app has ever seen its team
            // recorded elsewhere.
            $table->string('team_id', 64);
            $table->string('channel_id', 64);
            $table->string('channel_name', 128)->nullable();

            // Domain-namespaced event identifiers, e.g.
            // `alverium.release.published`, `alverium.deploy.started`.
            // Host apps define the taxonomy; deploybot doesn't validate.
            $table->string('event_type', 128);

            // Who subscribed the channel, for audit.
            $table->string('subscribed_by_user_id', 64)->nullable();

            $table->timestamps();

            $table->unique(
                ['team_id', 'channel_id', 'event_type'],
                'deploybot_subs_unique',
            );
            $table->index(['team_id', 'event_type'], 'deploybot_subs_lookup');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('deploybot_channel_subscriptions');
    }
};
