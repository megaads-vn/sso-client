<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterUserTableAddColumnToken extends Migration
{
    protected $userTables;

    protected $ssoColumns = ['token'];

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

        if (Schema::hasTable($this->userTables) && count($ssoColumns) > 0) {
            Schema::table($this->userTables, function (Blueprint $table) use ($ssoColumns) {
                if (in_array("token", $ssoColumns)) {
                    $table->string('token', 150)->default(null);
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
