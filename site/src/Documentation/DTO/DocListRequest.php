<?php

declare(strict_types=1);

namespace App\Documentation\DTO;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final readonly class DocListRequest
{
    public function __construct(
        #[Assert\Positive] public int $page = 1,
        #[Assert\Length(max: 200)] public string $q = '',
        #[Assert\Choice(choices: ['', 'draft', 'published'])] public string $status = '',
        #[Assert\PositiveOrZero] public int $section = 0,
    ) {
    }

    public static function fromRequest(Request $request, ValidatorInterface $validator): self
    {
        if ($request->query->has('sort')) {
            throw new BadRequestHttpException('Сортировка не поддерживается.');
        }
        $input = new self($request->query->getInt('page', 1), trim($request->query->getString('q')), $request->query->getString('status'), $request->query->getInt('section', 0));
        if (count($validator->validate($input)) > 0) {
            throw new UnprocessableEntityHttpException('Проверьте параметры поиска и страницы.');
        }

        return $input;
    }
}
