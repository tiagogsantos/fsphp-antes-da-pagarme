<?php

namespace Source\Core;

use Source\Models\User;
use Source\Support\Message;

/**
 * FSPHP | Class Model Layer Supertype Pattern
 *
 * @author Robson V. Leite <cursos@upinside.com.br>
 * @package Source\Models
 */
abstract class Model
{
    /** @var object|null */
    protected $data;

    /** @var \PDOException|null */
    protected $fail;

    /** @var Message|null */
    protected $message;

    /** @var string */
    protected $query;

    /** @var string */
    protected $params;

    /** @var string */
    protected $order;

    /** @var int */

    protected $limit;

    /** @var int */

    protected $offset;

    /** @var string $entity database table */
    protected static $entity;

    /** @var array $protected no update or create */
    protected static $protected;

    /** @var array $entity database table */
    protected static $required;

    /**
     * Model constructor.
     * @param string $entity database table name
     * @param array $protected table protected columns
     * @param array $required table required columns
     */
    public function __construct(string $entity, array $protected, array $required)
    {
        self::$entity = $entity;
        self::$protected = array_merge($protected, ['created_at', "updated_at"]);
        self::$required = $required;

        $this->message = new Message();
    }

    /**
     * @param $name
     * @param $value
     */
    public function __set($name, $value)
    {
        if (empty($this->data)) {
            $this->data = new \stdClass();
        }

        $this->data->$name = $value;
    }

    /**
     * @param $name
     * @return bool
     */
    public function __isset($name)
    {
        return isset($this->data->$name);
    }

    /**
     * @param $name
     * @return null
     */
    public function __get($name)
    {
        return ($this->data->$name ?? null);
    }

    /**
     * @return null|object
     */
    public function data(): ?object
    {
        return $this->data;
    }

    /**
     * @return \PDOException
     */
    public function fail(): ?\PDOException
    {
        return $this->fail;
    }

    /**
     * @return Message|null
     */
    public function message(): ?Message
    {
        return $this->message;
    }

    // O find tem a função de me trazer os resultados com ou sem a condição Where

    /**
     * @param string|null $terms
     * @param string|null $params
     * @param string $columns
     * @return Model|mixed
     */
    public function find(?string $terms = null, ?string $params = null, string $columns = "*")
    {
        // se eu tiver a $terms quer dizer que tenho um consulta determinada
        if ($terms) {
            $this->query = "SELECT {$columns} FROM ". static::$entity." WHERE {$terms}";
            parse_str($params, $this->params);
            return $this;
        }

        // se não for a consulta acima me trara todos os resultados
        $this->query = "SELECT {$columns} FROM ". static::$entity;
        return $this;
    }


    /**
     * @param int $id
     * @param string $columns
     * @return null|mixed|Model
     */
    public function findById(int $id, string $columns = "*"): ?Model
    {
        // Estou retornando o usuário filtrado
        $find = $this->find("id = :id", "id={$id}", $columns);
        return $find->fetch();
    }


    /**
     * @param string $columnOrder
     * @return Model
     */
    public function order(string $columnOrder): Model
    {
        // Setando a ordem das colunas
        $this->order = " ORDER BY {$columnOrder}";
        return $this;
    }

    /**
     * @param int $limit
     * @return Model
     */
    public function limit(int $limit): Model
    {
        // Limit de consultas
        $this->limit = " LIMIT {$limit}";
        return $this;
    }

    /**
     * @param int $offset
     * @return Model
     */
    public function offset(int $offset): Model
    {
        $this->offset = " OFFSET {$offset}";
        return $this;
    }


    /**
     * @param bool
     * @return null|array|mixed|Model
     */
    public function fetch(bool $all = false)
    {
        try {
            $stmt = Connect::getInstance()->prepare($this->query. $this->order. $this->limit. $this->offset);
            $stmt->execute($this->params);

            // condição abaixo quer dizer que eu não tive nenhum resultado
            if (!$stmt->rowCount()) {
                return null;
            }

            // aqui eu trago um array da mesma classe, ou seja, posso trazer varios resultados
            if ($all) {
                return $stmt->fetchAll(\PDO::FETCH_CLASS, static::class);
            }

            // aqui estou trazendo um objeto da classe
            return $stmt->fetchObject(static::class);

        }catch (\PDOException $exception) {
            $this->fail = $exception;
            return null;
        }
    }

    /**
     * @param string $key
     * @return int
     */
    public function count(string $key = "id"): int
    {
        // Aqui eu trago o numero de linhas resultantes
        $stmt = Connect::getInstance()->prepare($this->query);
        $stmt->execute($this->params);
        return $stmt->rowCount();
    }

    /**
     * @param array $data
     * @return int|null
     */
    protected function create(array $data): ?int
    {
        try {
            $columns = implode(", ", array_keys($data));
            $values = ":" . implode(", :", array_keys($data));

            $stmt = Connect::getInstance()->prepare("INSERT INTO " . static::$entity . " ({$columns}) VALUES ({$values})");
            $stmt->execute($this->filter($data));

            return Connect::getInstance()->lastInsertId();
        } catch (\PDOException $exception) {
            $this->fail = $exception;
            return null;
        }
    }

    /**
     * @param array $data
     * @param string $terms
     * @param string $params
     * @return int|null
     */
    protected function update(array $data, string $terms, string $params): ?int
    {
        try {
            $dateSet = [];
            foreach ($data as $bind => $value) {
                $dateSet[] = "{$bind} = :{$bind}";
            }
            $dateSet = implode(", ", $dateSet);
            parse_str($params, $params);

            $stmt = Connect::getInstance()->prepare("UPDATE " .static::$entity. " SET {$dateSet} WHERE {$terms}");
            $stmt->execute($this->filter(array_merge($data, $params)));
            return ($stmt->rowCount() ?? 1);
        } catch (\PDOException $exception) {
            $this->fail = $exception;
            return null;
        }
    }

    /**
     * @return bool
     */
    public function save(): bool
    {
        if (!$this->required()) {
            $this->message->warning("Preencha todos os campos para continuar!");
            return false;
        }

        /** Update Access */
        if (!empty($this->id)) {
            $id = $this->id;
            $this->update($this->safe(), "id = :id", "id={$id}");
            if ($this->fail()) {
                $this->message->error("Erro ao atualizar, verifique os dados");
                return false;
            }
        }

        /** Create Access */
        if (empty($this->id)) {
            $id = $this->create($this->safe());
            if ($this->fail()) {
                $this->message->error("Erro ao cadastrar, verifique os dados");
                return false;
            }
        }

        $this->data = $this->findById($id)->data();
        return true;
    }

    /**
     * @param string $key
     * @param string $value
     * @return bool
     */
    public function delete(string $terms, ?string $params): bool
    {
        try {
            $stmt = Connect::getInstance()->prepare("DELETE FROM " . static::$entity . " WHERE {$terms}");
            if ($params) {
                parse_str($params, $params);
                $stmt->execute($params);
                return true;
            }

            $stmt->execute();
            return true;
        } catch (\PDOException $exception) {
            $this->fail = $exception;
            return false;
        }
    }

    /**
     * @return bool
     */
    public function destroy(): bool
    {
        if (empty($this->id)) {
            return false;
        }
        $destroy = $this->delete("id = :id", "id={$this->id}");
        return $destroy;

    }

    /**
     * @return array|null
     */
    protected function safe(): ?array
    {
        $safe = (array)$this->data;
        foreach (static::$protected as $unset) {
            unset($safe[$unset]);
        }
        return $safe;
    }

    /**
     * @param array $data
     * @return array|null
     */
    private function filter(array $data): ?array
    {
        $filter = [];
        foreach ($data as $key => $value) {
            $filter[$key] = (is_null($value) ? null : filter_var($value, FILTER_DEFAULT));
        }
        return $filter;
    }

    /**
     * @return bool
     */
    protected function required(): bool
    {
        $data = (array)$this->data();
        foreach (static::$required as $field) {
            if (empty($data[$field])) {
                return false;
            }
        }
        return true;
    }
}