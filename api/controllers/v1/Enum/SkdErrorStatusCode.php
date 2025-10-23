<?php declare(strict_types=1);

namespace Local\Api\Controllers\V1\Enum;

enum SkdErrorStatusCode: string
{
	case AlreadyPassed = 'already-passed'; // Проход уже был
	case AlreadyOut = 'already-out'; // Выход уже был
	case NoValidationRights = 'no-validation-rights'; // Нет прав на валидацию
	case NoValidationRightsType = 'no-validation-rights_type'; // Нет прав на валидацию типа билета
	case NotFound = 'not-found'; // Билет не найден
	case IncorrectParameters = 'incorrect-parameters'; // некорректные параметры запроса
	
	case Error = 'error'; // прочие ошибки
}
