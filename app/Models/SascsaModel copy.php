<?php

namespace App\Models;
use App\Database\QueryBuilder;
use PDO;
use PDOException;
class SascsaModel extends QueryBuilder
{   
    protected string $table = 'view_dt_unidade_usuario';
    protected string $primaryKey = 'id_detalhe_unidade';


    public function __construct()
    {
        parent::__construct();
    }

    public function readAll(): array|string
    {
        $this->reset();
        $sql = $this->select("*")
                    ->from($this->table)
                    ->where("status_user_unidade = 1")
                    ->where("status_nome_unidade = 1")
                    ->where("nivel_acesso !='rh'")
                    ->getSelect();
        $stmt = $this->pdo->prepare($sql);

        try {
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_OBJ);
        } catch (PDOException $e) {
            //throw $th;
            return $e->getMessage();
        } 
        
    }
}