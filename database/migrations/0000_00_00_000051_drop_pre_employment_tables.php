<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

/**
 * Legacy pre-employment (pe_*) and BP employee (bp_*) tables were removed.
 * Retained as a no-op migration to preserve migration order.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('bp_emp_credentials');
        Schema::dropIfExists('bp_emp_documents');
        Schema::dropIfExists('bp_emp_tax_data');
        Schema::dropIfExists('bp_job_data');
        Schema::dropIfExists('bp_emp_phones');
        Schema::dropIfExists('bp_emp_addresses');
        Schema::dropIfExists('bp_employees');

        Schema::dropIfExists('pe_emp_credentials');
        Schema::dropIfExists('pe_emp_documents');
        Schema::dropIfExists('pe_emp_tax_data');
        Schema::dropIfExists('pe_job_data');
        Schema::dropIfExists('pe_emp_phones');
        Schema::dropIfExists('pe_emp_addresses');
        Schema::dropIfExists('pe_employees');
    }

    public function down(): void
    {
        //
    }
};
