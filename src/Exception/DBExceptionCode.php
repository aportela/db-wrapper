<?php

namespace aportela\DatabaseWrapper\Exception;

enum DBExceptionCode: int
{
    case CONSTRUCTOR = 1;
    case BEGIN_TRANSACTION = 2;
    case COMMIT_TRANSACTION = 3;
    case ROLLBACK_TRANSACTION = 4;
    case EXECUTE = 5;
    case QUERY = 6;
}
