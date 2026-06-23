<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('maintenance_comments');
        Schema::dropIfExists('maintenance_requests');
    }

    public function down(): void
    {
        // Intentionally empty — old schema superseded by maintenance_tickets module
    }
};
