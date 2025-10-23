<?php declare(strict_types=1);

namespace Local\Api\Controllers\V1\Enum;

enum SkdSearchBy: string
{
	case Fio = 'fio';
	case Code = 'code';
}
