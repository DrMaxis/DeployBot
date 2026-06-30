<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('deploybot_channel_subscriptions', function (Blueprint $table): void {
            $table->ulid('id')->primary();

            $table->string('team_id', 64);
            $table->string('channel_id', 64);
            $table->string('channel_name', 128)->nullable();
            $table->string('event_type', 128);
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
