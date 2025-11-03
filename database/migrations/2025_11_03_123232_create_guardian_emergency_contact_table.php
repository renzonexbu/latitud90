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
        Schema::create('guardian_emergency_contact', function (Blueprint $table) {
            $table->id();

            // Relaciones
            $table->foreignId('guardian_user_id')->nullable()->constrained('guardian_users')->onDelete('cascade');
            $table->foreignId('emergency_contact_id')->constrained('emergency_contact')->onDelete('cascade');

            // Control de invitación
            $table->string('invitation_code', 8)->unique();
            $table->enum('invitation_status', ['pending', 'accepted', 'expired'])->default('pending');
            $table->timestamp('invitation_sent_at')->nullable();
            $table->timestamp('invitation_accepted_at')->nullable();
            $table->timestamp('invitation_expires_at')->nullable();

            // Jerarquía
            $table->boolean('is_primary')->default(false);

            // Permisos granulares
            $table->boolean('can_pay')->default(true);
            $table->boolean('can_view_documents')->default(true);
            $table->boolean('can_view_itinerary')->default(true);
            $table->boolean('can_receive_notifications')->default(true);
            $table->boolean('can_update_emergency_contact')->default(false);

            // Auditoría (quién del admin creó esta invitación)
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');

            $table->timestamps();

            // Constraints
            $table->unique(['guardian_user_id', 'emergency_contact_id'], 'unique_guardian_contact');

            // Índices
            $table->index('invitation_code');
            $table->index('invitation_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('guardian_emergency_contact');
    }
};
