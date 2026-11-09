<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workspaces', function (Blueprint $table): void {
            $table->id();
            $table->uuid('owner_token')->unique();
            $table->string('name');
            $table->timestamps();
        });
        Schema::create('documents', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->string('title', 120);
            $table->unsignedInteger('version')->default(1);
            $table->timestamps();
        });
        Schema::create('document_versions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('document_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('number');
            $table->string('title', 120);
            $table->text('content');
            $table->string('checksum', 64);
            $table->timestamp('created_at');
            $table->unique(['document_id', 'number']);
        });
        Schema::create('passages', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->foreignId('document_id')->constrained()->cascadeOnDelete();
            $table->foreignId('document_version_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('position');
            $table->string('title', 120);
            $table->text('body');
            $table->unique(['document_id', 'position']);
        });
        DB::statement("CREATE VIRTUAL TABLE passage_search USING fts5(title, body, content='passages', content_rowid='id', tokenize='porter unicode61')");
        DB::unprepared('CREATE TRIGGER passage_insert AFTER INSERT ON passages BEGIN INSERT INTO passage_search(rowid, title, body) VALUES (new.id, new.title, new.body); END');
        DB::unprepared("CREATE TRIGGER passage_delete AFTER DELETE ON passages BEGIN INSERT INTO passage_search(passage_search, rowid, title, body) VALUES ('delete', old.id, old.title, old.body); END");
        DB::unprepared("CREATE TRIGGER passage_update AFTER UPDATE ON passages BEGIN INSERT INTO passage_search(passage_search, rowid, title, body) VALUES ('delete', old.id, old.title, old.body); INSERT INTO passage_search(rowid, title, body) VALUES (new.id, new.title, new.body); END");
    }

    public function down(): void
    {
        DB::unprepared('DROP TRIGGER IF EXISTS passage_insert; DROP TRIGGER IF EXISTS passage_delete; DROP TRIGGER IF EXISTS passage_update;');
        DB::statement('DROP TABLE IF EXISTS passage_search');
        Schema::dropIfExists('passages');
        Schema::dropIfExists('document_versions');
        Schema::dropIfExists('documents');
        Schema::dropIfExists('workspaces');
    }
};
