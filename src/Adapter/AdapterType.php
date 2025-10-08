<?php

namespace aportela\DatabaseWrapper\Adapter;

enum AdapterType
{
    case NONE;
    case PDO_SQLite;
    case PDO_MariaDB;
    case PDO_PostgreSQL;
}
