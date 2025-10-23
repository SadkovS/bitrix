<?php

namespace Custom\Core\Traits;

trait DateTimeFormatedTrait {
    protected function getFormatedDates(array $arDates = []): array
    {
        $result = [];
        $startDate = null;
        $prevDate = null;

        foreach ($arDates as $date) {
            $currentDate = new \DateTime($date);

            if ($startDate === null) {
                $startDate = $currentDate;
                $prevDate = $currentDate;
            } else {
                $diff = $currentDate->diff($prevDate);
                $sameTime = $currentDate->format('H:i:s') === $prevDate->format('H:i:s');

                if ($diff->days === 1 && $sameTime) {
                    $prevDate = $currentDate;
                } else {
                    if ($startDate === $prevDate) {
                        $result[] = FormatDate("d F Y", $startDate->getTimestamp()) . ' в ' . FormatDate("H:i", $startDate->getTimestamp());
                    } else {
                        $result[] = FormatDate("d F Y", $startDate->getTimestamp()) . " - " . FormatDate("d F Y", $prevDate->getTimestamp()) . ' в ' . FormatDate("H:i", $prevDate->getTimestamp());
                    }
                    $startDate = $currentDate;
                    $prevDate = $currentDate;
                }
            }
        }

        // Добавляем последний диапазон или дату
        if ($startDate === $prevDate) {
            $result[] = FormatDate("d F Y", $startDate->getTimestamp()) . ' в ' . FormatDate("H:i", $startDate->getTimestamp());
        } else {
            $result[] = FormatDate("d F Y", $startDate->getTimestamp()) . " - " . FormatDate("d F Y", $prevDate->getTimestamp()) . ' в ' . FormatDate("H:i", $prevDate->getTimestamp());
        }

        return $result;
    }
}