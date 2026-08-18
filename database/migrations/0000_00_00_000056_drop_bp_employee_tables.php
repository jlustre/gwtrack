<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

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
    }

    public function down(): void
    {
        // Irreversible removal of legacy BP employee tables.
    }
};
