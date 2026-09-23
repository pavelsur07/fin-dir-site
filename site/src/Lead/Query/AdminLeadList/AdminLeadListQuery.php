<?php

declare(strict_types=1);

namespace App\Lead\Query\AdminLeadList;

use App\Lead\Entity\Lead;
use App\Lead\ValueObject\NormalizedContact;
use Doctrine\ORM\EntityManagerInterface;
use Pagerfanta\Doctrine\ORM\QueryAdapter;
use Pagerfanta\Pagerfanta;
use Pagerfanta\PagerfantaInterface;

final class AdminLeadListQuery
{
    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    /**
     * @return PagerfantaInterface<AdminLeadListItem>
     */
    public function paginate(AdminLeadListCriteria $criteria): PagerfantaInterface
    {
        $queryBuilder = $this->entityManager->createQueryBuilder()
            ->select(\sprintf(
                'NEW %s(l.id, l.createdAt, l.formKey, l.name, l.contact, l.status, l.nextContactAt, l.notifiedAt)',
                AdminLeadListItem::class,
            ))
            ->from(Lead::class, 'l')
            ->orderBy('l.createdAt', 'DESC')
            ->addOrderBy('l.id', 'DESC');

        if (null !== $criteria->status) {
            $queryBuilder->andWhere('l.status = :status')->setParameter('status', $criteria->status);
        }
        if (null !== $criteria->form) {
            $queryBuilder->andWhere('l.formKey = :form')->setParameter('form', $criteria->form);
        }
        if (null !== $criteria->search && '' !== trim($criteria->search)) {
            // Номер ищем по цифрам ("900 111 22" найдёт "+79001112233"), остальное --
            // по нормализованному виду ("Owner@Mail.ru" найдёт "owner@mail.ru").
            $search = trim($criteria->search);
            $digits = (string) preg_replace('/\D+/', '', $search);
            if (\strlen($digits) >= 4 && str_starts_with($digits, '8') && !str_starts_with($search, '+')) {
                $digits = '7'.substr($digits, 1); // "8 900 111" -- российский номер, хранится как +7900111...
            }
            $needle = 1 === preg_match('/^[\d\s()+\-]+$/', $search) && \strlen($digits) >= 3
                ? $digits
                : NormalizedContact::fromRaw($search)->value;
            $queryBuilder
                ->andWhere('l.contactNormalized LIKE :needle OR LOWER(l.name) LIKE :name')
                ->setParameter('needle', '%'.addcslashes($needle, '%_\\').'%')
                ->setParameter('name', '%'.addcslashes(mb_strtolower($search), '%_\\').'%');
        }

        /** @var PagerfantaInterface<AdminLeadListItem> $pager */
        $pager = Pagerfanta::createForCurrentPageWithMaxPerPage(
            new QueryAdapter($queryBuilder, fetchJoinCollection: false, useOutputWalkers: false),
            $criteria->page,
            AdminLeadListCriteria::PER_PAGE,
        );

        return $pager;
    }
}
