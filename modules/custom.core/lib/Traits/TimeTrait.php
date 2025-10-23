<?php
namespace Custom\Core\Traits;
trait TimeTrait
{
	/**
	 * @param int $hours
	 *
	 * @return int
	 */
	protected function hoursToSeconds(int $hours): int
	{
		return $hours * 60 * 60;
	}
}