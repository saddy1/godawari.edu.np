<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('library_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('description')->nullable();
            $table->timestamps();
        });

        Schema::create('library_books', function (Blueprint $table) {
            $table->id();
            $table->foreignId('library_category_id')->nullable()->constrained('library_categories')->nullOnDelete();
            $table->string('title');
            $table->string('author')->nullable();
            $table->string('isbn')->nullable()->unique();
            $table->string('publisher')->nullable();
            $table->unsignedSmallInteger('publication_year')->nullable();
            $table->string('edition')->nullable();
            $table->decimal('price', 10, 2)->nullable();
            $table->unsignedInteger('pages')->nullable();
            $table->text('description')->nullable();
            $table->string('source')->nullable();
            $table->string('shelf_location')->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['title', 'author']);
        });

        Schema::create('library_book_copies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('library_book_id')->constrained('library_books')->cascadeOnDelete();
            $table->string('accession_no')->unique();
            $table->string('barcode')->nullable()->unique();
            $table->enum('status', ['available', 'issued', 'lost', 'damaged', 'inactive'])->default('available');
            $table->string('condition')->nullable();
            $table->text('remarks')->nullable();
            $table->timestamps();

            $table->index(['library_book_id', 'status']);
        });

        Schema::create('library_loans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('library_book_copy_id')->constrained('library_book_copies')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('student_id')->nullable()->constrained('students')->nullOnDelete();
            $table->string('borrower_name');
            $table->string('borrower_identifier')->nullable();
            $table->string('borrower_type')->nullable();
            $table->date('issued_at');
            $table->date('due_date');
            $table->date('returned_at')->nullable();
            $table->enum('status', ['issued', 'returned', 'lost'])->default('issued');
            $table->decimal('fine_amount', 10, 2)->default(0);
            $table->decimal('fine_paid', 10, 2)->default(0);
            $table->text('remarks')->nullable();
            $table->foreignId('issued_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('returned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index(['student_id', 'status']);
            $table->index(['due_date', 'status']);
        });

        DB::table('library_categories')->insert([
            ['name' => 'General', 'description' => 'General library collection', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Textbook', 'description' => 'Class and subject textbooks', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Reference', 'description' => 'Reference-only books', 'created_at' => now(), 'updated_at' => now()],
        ]);

        DB::table('module_settings')->updateOrInsert(
            ['key' => 'library'],
            [
                'label' => 'Library',
                'description' => 'Books, allocation, return tracking, and fines using ERP HR people data',
                'group' => 'ERP',
                'is_enabled' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        $permissions = ['library.view', 'library.create', 'library.edit', 'library.issue', 'library.reports'];
        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        foreach (['super-admin', 'principal', 'administrator', 'librarian'] as $roleName) {
            $role = Role::where('name', $roleName)->where('guard_name', 'web')->first();
            if ($role) {
                $role->givePermissionTo($permissions);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('library_loans');
        Schema::dropIfExists('library_book_copies');
        Schema::dropIfExists('library_books');
        Schema::dropIfExists('library_categories');

        DB::table('module_settings')->where('key', 'library')->delete();
    }
};
