<?php

declare(strict_types=1);

namespace App\Publication\DTO;

use App\Publication\Query\PostEditData\PostEditData;
use App\Publication\ValueObject\PostCover;
use App\Publication\ValueObject\PostRubric;
use App\Publication\ValueObject\PostSlug;
use App\Publication\ValueObject\PostTitle;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

/**
 * Вход формы создания/редактирования статьи. Не Entity: форма не пишет в модель напрямую.
 */
final class PostInput
{
    #[Assert\NotBlank]
    #[Assert\Length(max: PostTitle::MAX_LENGTH)]
    public string $title = '';

    /** Пусто -- slug генерируется из заголовка. */
    #[Assert\Length(max: PostSlug::MAX_LENGTH)]
    #[Assert\Regex(pattern: PostSlug::PATTERN, message: 'Только латиница в нижнем регистре, цифры и дефисы.')]
    public ?string $slug = null;

    #[Assert\Length(max: 300)]
    public ?string $excerpt = null;

    #[Assert\NotBlank]
    #[Assert\Length(max: 100000)]
    public string $body = '';

    #[Assert\Length(max: 70)]
    public ?string $metaTitle = null;

    #[Assert\Length(max: 170)]
    public ?string $metaDescription = null;

    /** Свой заголовок формы вопроса под статьёй. Пусто -- общий заголовок. */
    #[Assert\Length(max: 120)]
    public ?string $formTitle = null;

    /** Рубрика из фиксированного списка; пусто -- статья без рубрики. */
    public ?PostRubric $rubric = null;

    /** Новая обложка. Проверяется по содержимому файла, а не по имени и расширению. */
    #[Assert\Image(
        maxSize: PostCover::MAX_SIZE,
        mimeTypes: ['image/png'],
        minWidth: PostCover::WIDTH,
        maxWidth: PostCover::WIDTH,
        minHeight: PostCover::HEIGHT,
        maxHeight: PostCover::HEIGHT,
        maxSizeMessage: 'Файл больше 2 МБ. Сожмите PNG через TinyPNG или Squoosh.',
        mimeTypesMessage: 'Нужен файл PNG.',
        minWidthMessage: 'Нужен размер 1200 × 675 px, ширина файла {{ width }} px.',
        maxWidthMessage: 'Нужен размер 1200 × 675 px, ширина файла {{ width }} px.',
        minHeightMessage: 'Нужен размер 1200 × 675 px, высота файла {{ height }} px.',
        maxHeightMessage: 'Нужен размер 1200 × 675 px, высота файла {{ height }} px.',
    )]
    public ?UploadedFile $coverUpload = null;

    #[Assert\Length(max: PostCover::ALT_MAX_LENGTH)]
    public ?string $coverAlt = null;

    /** Снять текущую обложку. Если выбран новый файл, он заменяет обложку и флажок не важен. */
    public bool $removeCover = false;

    /** Текущий файл обложки из базы: не поле формы, нужен для проверки alt. */
    public ?string $currentCoverFile = null;

    /** Версия, с которой открыли форму: ловит одновременное редактирование. */
    public ?int $version = null;

    public static function fromEditData(PostEditData $data): self
    {
        $input = new self();
        $input->title = $data->title;
        $input->slug = $data->slug;
        $input->excerpt = $data->excerpt;
        $input->body = $data->body;
        $input->metaTitle = $data->metaTitle;
        $input->metaDescription = $data->metaDescription;
        $input->formTitle = $data->formTitle;
        $input->rubric = $data->rubric;
        $input->version = $data->version;
        $input->currentCoverFile = $data->cover?->file;
        $input->coverAlt = $data->cover?->alt;

        return $input;
    }

    /** Обложка после сохранения останется -- значит, нужен alt. */
    public function keepsCover(): bool
    {
        return null !== $this->coverUpload || (null !== $this->currentCoverFile && !$this->removeCover);
    }

    #[Assert\Callback]
    public function validateCoverAlt(ExecutionContextInterface $context): void
    {
        if ($this->keepsCover() && '' === trim((string) $this->coverAlt)) {
            $context->buildViolation('Опишите, что на обложке: текст нужен экранным дикторам.')
                ->atPath('coverAlt')
                ->addViolation();
        }
    }
}
