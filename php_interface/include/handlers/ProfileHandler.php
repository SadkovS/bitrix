<?php
require_once($_SERVER["DOCUMENT_ROOT"] . '/local/crest/crest.php');

use Bitrix\Main\Entity\EventResult;
use Bitrix\Main\Event;
use Bitrix\Main\ORM;
use Bitrix\Main\UserTable as UTable;
use Custom\Core\Users\CompaniesTable;
use Custom\Core\Users\UserProfilesTable;
use Custom\Core\UUID;


class ProfileHandler {

    private const B24_OWNER_FIELD = 'UF_CRM_1733228023';

    public static function onBeforeAdd(Event $event): EventResult
    {
        $result = new EventResult();

        try {
            $fields = $event->getParameter('fields');
            if (empty($fields['UF_USER_ID'])) {
                throw new \RuntimeException('User ID is required');
            }

            $userId = $fields['UF_USER_ID'];
            $result->modifyFields(
                [
                    'UF_UUID' => UUID::uuid3(UUID::NAMESPACE_X500, $userId . microtime(true))
                ]
            );
        } catch (\Throwable $e) {
            self::logError('Failed to generate UUID: ' . $e->getMessage());
        }

        return $result;
    }


    public static function onAfterAdd(Event $event): void
    {
        try {
            $fields = $event->getParameter('fields');

            $user = self::getUserB24Contact($fields['UF_USER_ID']);
            if (!$user) {
                throw new \RuntimeException('User not found');
            }

            $b24CompanyId = self::getB24CompanyId($fields['UF_COMPANY_ID']);
            if (!$b24CompanyId) {
                throw new \RuntimeException('B24 company not found');
            }

            if (!self::shouldUpdateCompanyOwner($fields)) {
                self::updateB24CompanyOwner($b24CompanyId, $user['UF_B24_CONTACT_ID']);
            }

            self::B24CompanyContactAdd($b24CompanyId, $user['UF_B24_CONTACT_ID'], $fields['UF_USER_ID']);
            //B24CompanyContactAdd

        } catch (\Throwable $e) {
            self::logError('Failed to update B24 company manager: ' . $e->getMessage());
        }
    }


    private static function shouldUpdateCompanyOwner(array $fields): bool
    {
        return !empty($fields['UF_COMPANY_ID']) && !empty($fields['UF_IS_OWNER']);
    }


    private static function getUserB24Contact(int $userId): ?array
    {
        $result = UTable::getList(
            [
                "select" => ['ID', 'UF_B24_CONTACT_ID'],
                'filter' => ['ID' => $userId],
                'limit'  => 1
            ]
        );
        return $result->fetch() ?: null;
    }


    private static function getB24CompanyId(int $companyId): ?int
    {
        $companyEntity = new ORM\Query\Query(CompaniesTable::class);
        $query         = $companyEntity
            ->setSelect(['UF_XML_ID'])
            ->setFilter(['ID' => $companyId])
            ->exec();

        $result = $query->fetch();
        return $result ? (int)$result['UF_XML_ID'] : null;
    }

    private static function is_primary_company($userID): bool
    {
        $companyEntity = new ORM\Query\Query(UserProfilesTable::class);
        $query         = $companyEntity
            ->setSelect(['ID'])
            ->setFilter(['UF_USER_ID' => $userID])
            ->countTotal(true)
            ->exec();
        return $query->getCount() > 1 ? false : true;
    }


    private static function updateB24CompanyOwner(int $b24CompanyId, string $b24ContactId): void
    {
        CRest::call(
            'crm.company.update',
            [
                'ID'     => $b24CompanyId,
                'FIELDS' => [
                    self::B24_OWNER_FIELD => $b24ContactId
                ]
            ]
        );
    }

    private static function B24CompanyContactAdd(int $b24CompanyId, string $b24ContactId, $userID): void
    {
        CRest::call(
            'crm.company.contact.add',
            [
                'ID'     => $b24CompanyId,
                'FIELDS' => [
                    'CONTACT_ID' => $b24ContactId,
                    'IS_PRIMARY' => self::is_primary_company($userID)
                ]
            ]
        );
    }

    private static function logError(string $message): void
    {
        \Bitrix\Main\Diag\Debug::writeToFile($message, '', '/log/b24errors.log');
    }
}