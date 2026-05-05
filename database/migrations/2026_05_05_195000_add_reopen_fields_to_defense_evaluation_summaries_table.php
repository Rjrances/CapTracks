<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('defense_evaluation_summaries', function (Blueprint $table) {
            $table->foreignId('reopened_by')->nullable()->after('finalized_by')->constrained('users')->nullOnDelete();
            $table->timestamp('reopened_at')->nullable()->after('finalized_at');
            $table->text('reopen_reason')->nullable()->after('final_notes');
        });
    }

    public function down(): void
    {
        Schema::table('defense_evaluation_summaries', function (Blueprint $table) {
            $table->dropConstrainedForeignId('reopened_by');
            $table->dropColumn(['reopened_at', 'reopen_reason']);
        });
    }
};

