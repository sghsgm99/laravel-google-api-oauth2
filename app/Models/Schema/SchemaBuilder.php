<?php

namespace App\Models\Schema;

use Illuminate\Database\Schema\Blueprint;

class SchemaBuilder
{
    public static function TimestampSchemaUp(Blueprint $table)
    {
        $table->timestamps();
        $table->softDeletes();
    }

    public static function TimestampSchemaDown(Blueprint $table)
    {
        $table->dropTimestamps();
        $table->dropSoftDeletes();
    }

    public static function BelongsToAccountSchemaUp(Blueprint $table, bool $softDeletes = true)
    {
        $table->unsignedBigInteger('account_id')->index();
        $table->foreign('account_id')->references('id')->on('accounts')->onDelete('cascade');

        if ($softDeletes) {
            self::TimestampSchemaUp($table);
        }
    }

    public static function BelongsToAccountSchemaDown(Blueprint $table)
    {
        self::TimestampSchemaUp($table);
        $table->dropForeign(['account_id']);
        $table->dropColumn(['account_id']);
    }

    public static function BelongsToUserSchemaUp(Blueprint $table, bool $softDeletes = true)
    {
        $table->unsignedBigInteger('user_id')->index();
        $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');

        self::BelongsToAccountSchemaUp($table, $softDeletes);
    }

    public static function BelongsToUserSchemaDown(Blueprint $table)
    {
        self::BelongsToAccountSchemaDown($table);
        $table->dropForeign(['user_id']);
        $table->dropColumn(['user_id']);
    }
}
