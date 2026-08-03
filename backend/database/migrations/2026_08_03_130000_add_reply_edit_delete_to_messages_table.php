<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->foreignId('parent_id')->nullable()->after('conversation_id')->constrained('messages')->nullOnDelete();
            $table->boolean('is_edited')->default(false)->after('type');
            $table->boolean('is_deleted')->default(false)->after('is_edited');
        });
    }

    public function down(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->dropForeign(['parent_id']);
            $table->dropColumn(['parent_id', 'is_edited', 'is_deleted']);
        });
    }
};
