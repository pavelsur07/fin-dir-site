<?php

declare(strict_types=1);

namespace App\ClientCase\DTO;

use App\ClientCase\Query\CaseEditData\CaseEditData;
use App\ClientCase\ValueObject\CaseIndustry;
use App\ClientCase\ValueObject\CaseSlug;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

/**
 * Вход формы создания/редактирования кейса. Не Entity: форма не пишет в модель напрямую.
 */
final class ClientCaseInput
{
    #[Assert\NotBlank(message: 'Укажите адрес: он станет частью ссылки /cases/адрес.')]
    #[Assert\Length(max: CaseSlug::MAX_LENGTH)]
    #[Assert\Regex(pattern: CaseSlug::PATTERN, message: 'Только латиница в нижнем регистре, цифры и дефисы.')]
    public string $slug = '';

    #[Assert\NotNull(message: 'Выберите отрасль.')]
    public ?CaseIndustry $industry = null;

    #[Assert\NotBlank]
    #[Assert\Length(max: 200)]
    public string $title = '';

    #[Assert\NotBlank]
    #[Assert\Length(max: 2000)]
    public string $problem = '';

    #[Assert\NotBlank]
    #[Assert\Length(max: 60)]
    public string $resultValue = '';

    #[Assert\NotBlank]
    #[Assert\Length(max: 160)]
    public string $resultLabel = '';

    /** @var list<string> */
    #[Assert\Count(max: 8, maxMessage: 'Не больше {{ limit }} тегов.')]
    #[Assert\All([new Assert\Length(max: 40)])]
    public array $tags = [];

    #[Assert\Length(max: 2000)]
    public ?string $task = null;

    /** @var list<string> */
    #[Assert\Count(max: 10, maxMessage: 'Не больше {{ limit }} шагов.')]
    #[Assert\All([new Assert\Length(max: 300)])]
    public array $steps = [];

    /** @var list<array{value: string, label: string}> */
    #[Assert\Count(max: 6, maxMessage: 'Не больше {{ limit }} метрик.')]
    public array $metrics = [];

    #[Assert\Length(max: 160)]
    public ?string $source = null;

    public bool $featured = false;

    /** Версия, с которой открыли форму: ловит одновременное редактирование. */
    public ?int $version = null;

    /** Главный кейс показывается развёрнуто: без задачи, шагов и метрик он выглядел бы пустым. */
    #[Assert\Callback]
    public function validateFeatured(ExecutionContextInterface $context): void
    {
        if ($this->featured && (null === $this->task || '' === trim($this->task) || [] === $this->steps || [] === $this->metrics)) {
            $context->buildViolation('Главному кейсу нужны задача, шаги и метрики.')->atPath('featured')->addViolation();
        }
    }

    public static function fromEditData(CaseEditData $data): self
    {
        $input = new self();
        $input->slug = $data->slug;
        $input->industry = $data->industry;
        $input->title = $data->title;
        $input->problem = $data->problem;
        $input->resultValue = $data->resultValue;
        $input->resultLabel = $data->resultLabel;
        $input->tags = $data->tags;
        $input->task = $data->task;
        $input->steps = $data->steps;
        $input->metrics = $data->metrics;
        $input->source = $data->source;
        $input->featured = $data->featured;
        $input->version = $data->version;

        return $input;
    }
}
