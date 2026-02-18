<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('banned_companies', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            // We use string for added_by to match existing pattern (username), 
            // but ideally should be foreign key to users.id. 
            // Given the current extensive use of username in logs, we'll keep it as string 
            // or nullable foreign key if we want strictness.
            // Let's use string for now to avoid issues if users are deleted but logs remain.
            $table->string('added_by')->nullable();
            $table->text('reason')->nullable();
            $table->timestamps();

            // Foreign Key Constraint
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('banned_companies');
    }
};
