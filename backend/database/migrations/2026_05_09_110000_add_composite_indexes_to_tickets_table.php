<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->index(['due_date', 'status'], 'tickets_due_date_status_idx');
            $table->index(['creator_id', 'created_at'], 'tickets_creator_created_idx');
            $table->index(['assigned_to', 'created_at'], 'tickets_assigned_created_idx');
            $table->index(['status', 'created_at'], 'tickets_status_created_idx');
            $table->index(['priority', 'created_at'], 'tickets_priority_created_idx');
        });
    }

    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropIndex('tickets_due_date_status_idx');
            $table->dropIndex('tickets_creator_created_idx');
            $table->dropIndex('tickets_assigned_created_idx');
            $table->dropIndex('tickets_status_created_idx');
            $table->dropIndex('tickets_priority_created_idx');
        });
    }
};
