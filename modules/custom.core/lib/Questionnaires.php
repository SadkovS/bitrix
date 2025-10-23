<?php

namespace Custom\Core;

use Bitrix\Main\Application;
use Bitrix\Main\ORM;

class Questionnaires {
    protected array $defaultFields = [
        'full_name' => ['name' => 'ФИО', 'type' => 'string', 'required' => true],
        'phone'     => ['name' => 'Телефон', 'type' => 'phone', 'required' => true],
        'email'     => ['name' => 'Email', 'type' => 'string', 'required' => true],
    ];
    protected array $fieldsTypes = [
        'string'  => 'Короткий произвольный текст',
        'text'    => 'Длинный произвольный текст',
        'list'    => 'Выбор одного варианта',
        'm_list'  => 'Выбор нескольких вариантов',
        'file'    => 'Прикрепление файла',
        'phone'   => 'Номер телефона',
        'boolean' => 'Да / Нет',
    ];
    protected array $extensions = [
        'jpg',
        'jpeg',
        'png',
        'pdf',
        'heic',
        'doc',
        'docx',
        'xls',
        'xlsx'
    ];
    protected int $eventID;
    protected array $userFields;
    protected string $description;
    protected int $descriptionLength = 2000;
    protected int $fileSize = 5242880; // 5 mb
    protected int $questionnaireActive;
    protected bool $forEachTickets;

    public function __construct(object $request = null)
    {
        $this->eventID    = (int)$request['EVENT'];
        $this->userFields = $request['Q_FIELDS'] ?? [];
        if (isset($request['UF_QUESTIONNAIRE_DESCRIPTION']))
            $this->setDescription($request['UF_QUESTIONNAIRE_DESCRIPTION']);

        $this->setQuestionnaireActive($request['UF_QUESTIONNAIRE_ACTIVE']);
        $this->setForEachTickets($request['UF_QUESTIONNAIRE_FOREACH_TICKETS']);
    }

    public function __get($property)
    {
        return $this->$property;
    }

    public function getFields(): array
    {
        $arFields = $this->prepareUserFields($this->userFields);
        return $arFields;
    }

    public function isFilled(int $eventID): bool
    {
        if($eventID < 1) return false;
        $eventEntity = new ORM\Query\Query('Custom\Core\Events\EventsQuestionnaireTable');
        $query = $eventEntity
            ->setSelect(['ID'])
            ->setFilter(['UF_EVENT_ID' => $eventID])
            ->countTotal(true)
            ->setLimit(1)
            ->exec();
        if($query->getCount() < 1) return false;
        return true;
    }

    public function prepareUserFields(array $userFields): array
    {
        $arFields = [];

        $pattern = '/^[a-f0-9]{8}\-[a-f0-9]{4}\-[a-f0-9]{4}\-[a-f0-9]{4}\-[a-f0-9]{12}$/';

        foreach ($userFields as $key => $value) {

            if (preg_match($pattern, $key)) {

                if (!key_exists($value['type'], $this->fieldsTypes) || !isset($value['type'])) continue;

                $value          = $this->validateRequired($value);
                $value          = $this->validateListOptions($value);
                $value          = $this->validateFiles($value);
                $arFields[$key] = $value;
            }
        }

        return $arFields;
    }

    private function validateRequired(array $value): array
    {
        $value['required'] = (bool)$value['required'];
        return $value;
    }

    private function validateListOptions(array $value): array
    {
        if (in_array($value['type'], ['list', 'm_list']) && isset($value['options'])) {

            foreach ($value['options'] as $k => $v) {
                if (trim($v) == '') unset($value['options'][$k]);
                else $v = htmlspecialchars(strip_tags(trim($v)));
            }
            unset($k, $v);
        } else {
            unset($value['options']);
        }

        return $value;
    }

    private function validateFiles(array $value): array
    {
        $size = $this->fileSize / 1048576;

        if (in_array($value['type'], ['file'])) {
            $value['size'] = $size;
            $value['extensions'] = $this->extensions;
        } else
            unset($value['size'], $value['extensions']);

        return $value;
    }

    public function getEventID(): int
    {
        return $this->eventID;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getQuestionnaireActive(): int
    {
        return $this->questionnaireActive;
    }

    public function getForEachTickets(): int
    {
        return $this->forEachTickets;
    }

    public function setEventID(int $eventID): void
    {
        $this->eventID = $eventID;
    }

    public function setDescription(string $text = ''): void
    {
        if (strlen($text) > 0) {
            $text = strip_tags(trim($text));
        }

        if (strlen($text) > $this->descriptionLength) {
            $text = substr($text, 0, $this->descriptionLength);
        }

        $this->description = $text;
    }

    public function setQuestionnaireActive($state = 0): void
    {
        $this->questionnaireActive = (int)$state;
    }

    public function setForEachTickets($state = 0): void
    {
        $this->forEachTickets = (int)$state;
    }

}