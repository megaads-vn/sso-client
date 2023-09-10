<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterUsersTable extends Migration
{
    protected $userTables;

    protected $ssoColumns = ['code','public_key','private_key','status'];

    public function __construct() {
        $this->userTables = config('sso.tables.users');
    }
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $ssoColumns = $this->ssoColumns;
        $userTableColumns = columnListing($this->userTables);
        foreach ($userTableColumns as $col) {
            if ($index = array_search($col, $ssoColumns)) {
                unset($ssoColumns[$index]);
            }
        }
        if (Schema::hasTable($this->userTables)) {
            Schema::table($this->userTables, function (Blueprint $table) use ($ssoColumns) {
                if (in_array("code", $ssoColumns)) {
                    $table->string('code', 50)->default(null);
                }
                if (in_array("public_key", $ssoColumns)) {
                    $table->text('public_key')->default(null);
                }
                if (in_array("private_key", $ssoColumns)) {
                    $table->text('private_key')->default(null);
                }
                if (in_array("status", $ssoColumns)) {
                    $table->enum('status', ['active','pending'])->default('active');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $userTableColumns = columnListing($this->userTables);
        foreach ($userTableColumns as $col) {
            if ($index = array_search($col, $this->ssoColumns)) {
                unset($this->ssoColumns[$index]);
            }
        }
        Schema::table($this->userTables, function (Blueprint $table) {
            $table->dropColumn($this->ssoColumns);
        });
    }
}
