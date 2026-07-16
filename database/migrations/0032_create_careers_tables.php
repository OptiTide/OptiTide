<?php

use App\Core\Blueprint;
use App\Core\Schema;

return new class {
    public function up(): void
    {
        // Job openings are admin-managed rather than hard-coded, so roles are
        // posted and closed in-app with zero code change.
        Schema::create('job_openings', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug');
            $table->string('department', 120, true);
            $table->string('location', 160, false, 'Australia — Remote');
            $table->string('employment_type', 30, false, 'full_time');
            $table->string('workplace_type', 20, false, 'remote');
            $table->text('summary', true);
            $table->text('description', true);
            $table->text('responsibilities', true);
            $table->text('requirements', true);
            $table->text('benefits', true);

            // Money follows the house rule: integer minor units + a currency.
            $table->integer('salary_min_cents', true);
            $table->integer('salary_max_cents', true);
            $table->string('salary_currency', 3, false, 'AUD');
            $table->string('salary_period', 20, false, 'year');
            $table->boolean('salary_visible', false, false);

            $table->string('status', 20, false, 'draft');
            $table->integer('sort_order', false, 0);
            $table->timestamp('posted_at', true);
            $table->timestamp('closes_at', true);
            $table->timestamps();

            $table->unique('slug');
            $table->index('status');
        });

        Schema::create('job_applications', function (Blueprint $table) {
            $table->id();
            // Nullable + SET NULL: a general expression of interest has no role,
            // and deleting a closed opening must never destroy the applications
            // (people's personal data + the audit trail) attached to it.
            $table->foreignId('job_opening_id', 'job_openings', 'set null', true);
            // Snapshot of the role title so an application still reads correctly
            // after its opening is deleted or renamed.
            $table->string('role_title', 200, false, 'General application');

            $table->string('name', 160);
            $table->string('email', 200);
            $table->string('phone', 60, true);
            $table->string('location', 160, true);
            $table->string('linkedin_url', 300, true);
            $table->string('portfolio_url', 300, true);
            $table->text('cover_letter', true);

            // Resume lives OUTSIDE the webroot under storage/careers. The path is
            // a server-generated random basename — never anything client-supplied.
            $table->string('resume_path', 200, true);
            $table->string('resume_name', 200, true);
            $table->integer('resume_size', true);

            $table->string('status', 20, false, 'new');
            $table->text('staff_notes', true);
            $table->string('ip', 45, true);
            $table->timestamps();

            $table->index('status');
            $table->index('job_opening_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('job_applications');
        Schema::dropIfExists('job_openings');
    }
};
