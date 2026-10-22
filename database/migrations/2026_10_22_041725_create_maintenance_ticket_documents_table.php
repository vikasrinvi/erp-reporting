<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  
    public function up(): void
    {
        Schema::create('maintenance_ticket_documents', function (Blueprint $table) {
            $table->id();

            $table->foreignId('maintenance_ticket_id')
                ->constrained('maintenance_tickets')
                ->cascadeOnDelete();

            $table->string('stage', 50);
            $table->string('document_name', 255);
            $table->string('original_name');
            $table->string('file_path');
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('file_size')->default(0);
            $table->unsignedBigInteger('uploaded_by')->nullable();

            $table->timestamps();

            $table->index(['maintenance_ticket_id', 'stage']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('maintenance_ticket_documents');
    }

};




