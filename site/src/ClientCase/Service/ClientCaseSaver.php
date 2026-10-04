<?php

declare(strict_types=1);

namespace App\ClientCase\Service;

use App\ClientCase\DTO\ClientCaseInput;
use App\ClientCase\Entity\ClientCase;
use App\ClientCase\Exception\CaseSlugAlreadyTaken;
use App\ClientCase\Exception\CaseWasModified;
use App\ClientCase\Repository\ClientCaseRepository;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\OptimisticLockException;
use Psr\Clock\ClockInterface;

/**
 * Создание и правка кейса из админки. Главным остаётся один кейс: при назначении нового
 * признак снимается с прежнего в той же транзакции.
 */
final class ClientCaseSaver
{
    public function __construct(
        private readonly ClientCaseRepository $cases,
        private readonly EntityManagerInterface $entityManager,
        private readonly ClockInterface $clock,
    ) {
    }

    /**
     * Создаёт черновик.
     *
     * @return int id нового кейса
     */
    public function create(ClientCaseInput $input): int
    {
        $industry = $input->industry ?? throw new \LogicException('Case industry is required.');
        $now = $this->clock->now();

        if ($this->cases->slugExists($input->slug)) {
            throw new CaseSlugAlreadyTaken($input->slug);
        }

        $case = new ClientCase($input->slug, $industry, $input->title, $input->problem, $input->resultValue, $input->resultLabel, $input->tags, $now);
        $case->describe($this->nullIfBlank($input->task), $input->steps, $input->metrics, $this->nullIfBlank($input->source), $now);
        $this->cases->save($case);
        $this->applyFeatured($case, $input->featured, $now);

        $this->flush($input->slug);

        return (int) $case->id();
    }

    public function edit(int $id, ClientCaseInput $input): void
    {
        $industry = $input->industry ?? throw new \LogicException('Case industry is required.');
        $case = $this->cases->get($id);
        $now = $this->clock->now();

        // Версия, с которой открыли форму, против текущей в базе. Без неё
        // правка молча перезаписала бы чужую -- поэтому она обязательна.
        try {
            $this->entityManager->lock($case, LockMode::OPTIMISTIC, $input->version ?? throw new \LogicException('Case version is required for editing.'));
        } catch (OptimisticLockException $e) {
            throw new CaseWasModified($id, $e);
        }

        $case->edit($industry, $input->title, $input->problem, $input->resultValue, $input->resultLabel, $input->tags, $now);

        if ($input->slug !== $case->slug()) {
            // Сначала правило Entity (адрес заблокирован), потом занятость.
            $case->changeSlug($input->slug, $now);
            if ($this->cases->slugExists($input->slug)) {
                throw new CaseSlugAlreadyTaken($input->slug);
            }
        }

        // Снимаем признак до describe(): иначе у главного кейса пустые шаги дали бы ошибку формы вместо снятия.
        if (!$input->featured) {
            $case->unmarkAsFeatured($now);
        }
        $case->describe($this->nullIfBlank($input->task), $input->steps, $input->metrics, $this->nullIfBlank($input->source), $now);
        $this->applyFeatured($case, $input->featured, $now);

        $this->flush($input->slug, $id);
    }

    private function applyFeatured(ClientCase $case, bool $featured, \DateTimeImmutable $now): void
    {
        if (!$featured) {
            return;
        }

        $case->markAsFeatured($now);
        foreach ($this->cases->featuredExcept($case) as $other) {
            $other->unmarkAsFeatured($now);
        }
    }

    private function flush(string $slug, ?int $editedId = null): void
    {
        try {
            $this->entityManager->flush();
        } catch (OptimisticLockException $e) {
            // Кейс сохранили между проверкой версии и flush -- #[Version] не даёт перезаписать.
            throw new CaseWasModified($editedId ?? 0, $e);
        } catch (UniqueConstraintViolationException $e) {
            // Гонка между проверкой slugExists() и вставкой -- ловит уникальный индекс.
            throw new CaseSlugAlreadyTaken($slug, $e);
        }
    }

    private function nullIfBlank(?string $value): ?string
    {
        $value = null === $value ? '' : trim($value);

        return '' === $value ? null : $value;
    }
}
