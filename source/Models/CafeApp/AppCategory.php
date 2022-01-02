<?php

namespace Source\Models\CafeApp;

use Source\Core\Model;

class AppCategory extends Model
{

    /**
     * Este PHP Class será a minha carteira de pagar e receber
     */

    public function __construct()
    {
        parent::__construct("app_categories", ["id"], ["name", "type"]);
    }

}