<?php

use Illuminate\Database\Capsule\Manager as DB;

DB::schema()->create('email_queue', function ($table) {
    $table->id();
    $table->string('recipient_email');
    $table->string('recipient_name');
    $table->string('subject');
    $table->string('template');
    $table->json('data');
    $table->integer('priority')->default(1);
    $table->enum('status', ['pending', 'processing', 'completed', 'failed'])
          ->default('pending');
    $table->integer('attempts')->default(0);
    $table->text('error')->nullable();
    $table->timestamp('scheduled_for')->nullable();
    $table->timestamp('last_attempt')->nullable();
    $table->timestamp('completed_at')->nullable();
    $table->timestamps();
    
    $table->index(['status', 'scheduled_for']);
    $table->index(['priority', 'created_at']);
}); 