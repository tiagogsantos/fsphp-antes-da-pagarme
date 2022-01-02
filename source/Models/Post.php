<?php

namespace Source\Models;

use Source\Core\Model;

/**
 * Class Post
 * @package Source\Models
 */
class Post extends Model
{
    /** * @var bool */
    private $all;

    /**
     * Post constructor.
     * @param bool $all = ignore status and post_at
     */
    public function __construct(bool $all = false)
    {
        $this->all = $all;
        parent::__construct("posts", ["id"], ["title", "uri", "subtitle", "content"]);
    }

    /**
     * @param null|string $terms
     * @param null|string $params
     * @param string $columns
     * @return mixed|Model
     */
    public function find(?string $terms = null, ?string $params = null, string $columns = "*")
    {
        if (!$this->all) {
        $terms = "status = :status AND post_at <= NOW()" . ($terms ? " AND {$terms}" : "");
        $params = "status=post" . ($params ? "&{$params}" : "");
        }
        return parent::find($terms, $params, $columns);
    }

    /**
     * @param string $uri
     * @param string $columns
     * @return null|Post
     */
    public function findByUri(string $uri, string $columns = "*"): ?Post
    {
        $find = $this->find("uri = :uri", "uri={$uri}", $columns);
        return $find->fetch();
    }

    /**
     * @return null|User
     */
    public function author(): ?User
    {
        if ($this->author) {
            return (new User())->findById($this->author);
        }
        return null;
    }

    /**
     * @return null|Category
     */
    public function category(): ?Category
    {
        if ($this->category) {
            return (new Category())->findById($this->category);
        }
        return null;
    }
}