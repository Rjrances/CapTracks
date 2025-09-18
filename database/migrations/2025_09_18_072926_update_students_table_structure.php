<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            // Add account_id foreign key
            $table->foreignId('account_id')->nullable()->after('id')->constrained()->onDelete('cascade');
            
            // Remove password fields
            $table->dropColumn(['password', 'must_change_password']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            // Add back password fields
            $table->string('password')->after('email');
            $table->boolean('must_change_password')->default(false)->after('password');
            
            // Remove account_id
            $table->dropForeign(['account_id']);
            $table->dropColumn('account_id');
        });
    }
};
