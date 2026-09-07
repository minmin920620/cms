<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'sqlite') {
            return;
        }

        DB::statement('PRAGMA foreign_keys = OFF');
        DB::transaction(function () {
            DB::statement(<<<'SQL'
                CREATE TABLE users_new (
                    id integer primary key autoincrement not null,
                    name varchar not null,
                    email varchar not null,
                    role varchar check ("role" in ('admin', 'police_officer', 'investigator', 'lgu')) not null default 'police_officer',
                    email_verified_at datetime,
                    password varchar not null,
                    phone varchar,
                    badge_number varchar,
                    avatar varchar,
                    is_active tinyint(1) not null default '1',
                    remember_token varchar,
                    created_at datetime,
                    updated_at datetime
                )
            SQL);

            DB::statement(<<<'SQL'
                INSERT INTO users_new (
                    id, name, email, role, email_verified_at, password, phone, badge_number,
                    avatar, is_active, remember_token, created_at, updated_at
                )
                SELECT
                    id, name, email, role, email_verified_at, password, phone, badge_number,
                    avatar, is_active, remember_token, created_at, updated_at
                FROM users
            SQL);

            DB::statement('DROP TABLE users');
            DB::statement('ALTER TABLE users_new RENAME TO users');
            DB::statement('CREATE UNIQUE INDEX users_email_unique ON users (email)');
            DB::statement('CREATE UNIQUE INDEX users_badge_number_unique ON users (badge_number)');
            DB::statement('CREATE INDEX users_role_idx ON users (role)');
            DB::statement('CREATE INDEX users_is_active_idx ON users (is_active)');
        });
        DB::statement('PRAGMA foreign_keys = ON');
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'sqlite') {
            return;
        }

        DB::table('users')->where('role', 'lgu')->update(['role' => 'police_officer']);
    }
};
