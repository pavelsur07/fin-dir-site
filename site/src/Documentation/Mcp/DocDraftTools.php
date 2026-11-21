<?php

declare(strict_types=1);

namespace App\Documentation\Mcp;

use App\Documentation\DTO\DocDraftWriteInput;
use App\Documentation\Exception\DocToolFailure;
use App\Documentation\Service\DocDraftCreator;
use App\Documentation\Service\DocDraftEditor;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class DocDraftTools
{
    public function __construct(private readonly DocDraftCreator $creator, private readonly DocDraftEditor $editor, private readonly ValidatorInterface $validator)
    {
    }

    /** @param array<string,mixed> $args
     * @return array<string,mixed> */
    public function call(string $tool, array $args, DocMcpAccess $access): array
    {
        $access->assertAllowed('docs:drafts:write');
        $input = DocDraftWriteInput::fromArray($args['requestKey'], $args['page']);
        if (count($this->validator->validate($input)) > 0) {
            throw new DocToolFailure('VALIDATION_FAILED', 'Проверьте поля страницы и requestKey.');
        }
        $result = 'docs_create_draft' === $tool ? $this->creator->create($input, $access->actor) : $this->editor->edit($args['id'], $args['expectedVersion'], $input, $access->actor);

        return $result->toArray();
    }
}
