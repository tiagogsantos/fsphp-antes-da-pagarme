<?php

namespace Source\Models;

use Source\Core\Model;

class Category extends Model
{
    public function __construct()
    {
        parent::__construct("categories", ["id"], ["title", "id"]);
    }

    public function findByUri(string $uri, string $columns = "*"): ?Category
    {
        $find = $this->find("uri = :uri", "uri={$uri}", $columns);
        return $find->fetch();
    }
}