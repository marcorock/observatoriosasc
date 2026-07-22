<?php

namespace App\Models;
use App\Core\QueryBuilder;
use PDO;
use PDOException;
class OscModel extends QueryBuilder
{   
    protected string $table = 'view_dt_unidade_usuario';


    public function __construct()
    {
        parent::__construct();
    }

    public function readAll(): array|string
    {
       
        return "MODEL DO BANCO OSC";
        
    }
}