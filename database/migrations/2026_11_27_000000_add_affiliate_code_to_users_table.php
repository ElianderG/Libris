<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('affiliate_code', 8)->nullable()->unique()->after('email');
        });

        User::query()->whereNull('affiliate_code')->each(function (User $user): void {
            $user->affiliate_code = User::newAffiliateCode();
            $user->save();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['affiliate_code']);
            $table->dropColumn('affiliate_code');
        });
    }
};
