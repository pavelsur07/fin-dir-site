<?php

declare(strict_types=1);

namespace App\Publication\DTO;

use App\Publication\Query\PostEditData\PostEditData;
use App\Publication\ValueObject\PostSlug;
use App\Publication\ValueObject\PostTitle;
use Symfony\Component\Validator\Constraints as Assert;

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
        $input->version = $data->version;

        return $input;
    }
}
