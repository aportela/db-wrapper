<?php

declare(strict_types=1);

namespace aportela\DatabaseWrapper\Exception;

enum DBExceptionCode: int
{
    case CONSTRUCTOR = 1;

    case BEGIN_TRANSACTION = 2;

    case COMMIT_TRANSACTION = 3;

    case ROLLBACK_TRANSACTION = 4;

    case EXECUTE = 5;

    case QUERY = 6;

    case INVALID_ADAPTER = 7;

    case DATABASE_NOT_FOUND = 8;

    case INVALID_BACKUP_PATH = 9;

    case IN_TRANSACTION_CHECK = 10;

    case EXEC = 11;
}
