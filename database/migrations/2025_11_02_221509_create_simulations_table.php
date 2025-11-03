<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('simulations', function (Blueprint $table) {
            $table->id();

            // Referência ao utilizador
            $table->foreignId('user_id')->constrained()->onDelete('cascade');

            // Dados do empréstimo
            $table->decimal('loan_amount', 10, 2);
            $table->integer('duration_months');
            $table->enum('rate_type', ['fixed', 'variable']);
            $table->decimal('annual_rate', 5, 2);

            // Apenas para taxa variável
            $table->decimal('index_rate', 5, 2)->nullable();
            $table->decimal('spread', 5, 2)->nullable();

            // Resultados
            $table->decimal('monthly_payment', 10, 2);
            $table->decimal('total_amount', 12, 2);
            $table->decimal('total_interest', 12, 2);

            $table->timestamps();

            // Indexes
            $table->index('user_id');
            $table->index('created_at');
            $table->index('rate_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('simulations');
    }
};
