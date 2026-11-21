<?php

declare(strict_types=1);

namespace App\Documentation\Mcp;

use App\Documentation\Exception\DocToolFailure;

final class DocMcpSchema
{
    /**
     * @return array<string,array<string,mixed>> */
    public static function inputs(): array
    {
        $id = ['type' => 'integer', 'minimum' => 1];
        $pageNumber = $id + ['default' => 1];
        $text = static fn (int $max): array => ['type' => 'string', 'maxLength' => $max];
        $page = self::object([
            'title' => $text(200) + ['minLength' => 1],
            'slug' => $text(180) + ['minLength' => 1, 'pattern' => '^[a-z0-9]+(?:-[a-z0-9]+)*$'],
            'sectionId' => $id,
            'description' => $text(300), 'body' => $text(100000),
            'position' => ['type' => 'integer', 'minimum' => 0, 'maximum' => 100000],
            'metaTitle' => ['type' => ['string', 'null'], 'maxLength' => 70],
            'metaDescription' => ['type' => ['string', 'null'], 'maxLength' => 170],
            'relatedIds' => ['type' => 'array', 'items' => $id, 'maxItems' => 5, 'uniqueItems' => true],
        ], ['title', 'slug', 'sectionId']);
        $write = ['requestKey' => ['type' => 'string', 'pattern' => '^[a-fA-F0-9]{8}-[a-fA-F0-9]{4}-4[a-fA-F0-9]{3}-[89aAbB][a-fA-F0-9]{3}-[a-fA-F0-9]{12}$'], 'page' => $page];

        return [
            'docs_list_sections' => self::object(['page' => $pageNumber]),
            'docs_search' => self::object(['q' => $text(200), 'page' => $pageNumber, 'sectionId' => $id, 'status' => ['type' => 'string', 'enum' => ['draft', 'published']]]),
            'docs_get_page' => self::object(['id' => $id], ['id']),
            'docs_create_draft' => self::object($write, ['requestKey', 'page']),
            'docs_update_draft' => self::object(['id' => $id, 'expectedVersion' => $id] + $write, ['id', 'expectedVersion', 'requestKey', 'page']),
            'docs_get_links' => self::object(['id' => $id, 'direction' => ['type' => 'string', 'enum' => ['incoming', 'outgoing']], 'page' => $pageNumber], ['id']),
            'docs_validate_page' => self::object(['id' => $id], ['id']),
            'docs_preview_page' => self::object(['id' => $id], ['id']),
        ];
    }

    /**
     * @return array<string,mixed> */
    public static function output(string $tool): array
    {
        $string = ['type' => 'string'];
        $integer = ['type' => 'integer'];
        $nullable = ['type' => ['string', 'null']];
        $boolean = ['type' => 'boolean'];
        $heading = self::object(['id' => $string, 'title' => $string], ['id', 'title']);
        $headings = ['type' => 'array', 'items' => $heading];
        $write = self::object(['pageId' => $integer, 'requestKey' => $string, 'replayed' => $boolean, 'adminEditUrl' => $string], ['pageId', 'requestKey', 'replayed', 'adminEditUrl']);
        if (in_array($tool, ['docs_create_draft', 'docs_update_draft'], true)) {
            return $write;
        }
        if ('docs_preview_page' === $tool) {
            return self::object(['pageId' => $integer, 'version' => $integer, 'html' => $string, 'toc' => $headings, 'adminPreviewUrl' => $nullable], ['pageId', 'version', 'html', 'toc', 'adminPreviewUrl']);
        }
        if ('docs_validate_page' === $tool) {
            return self::object(['pageId' => $integer, 'version' => $integer, 'readyToPublish' => $boolean, 'checkedAt' => $string, 'issues' => ['type' => 'array', 'items' => self::object(['code' => $string, 'message' => $string, 'targetId' => $integer], ['code', 'message'])]], ['pageId', 'version', 'readyToPublish', 'checkedAt', 'issues']);
        }
        if ('docs_get_page' === $tool) {
            return self::object(['id' => $integer, 'sectionId' => $integer, 'title' => $string, 'slug' => $string, 'description' => $string, 'body' => $string, 'position' => $integer, 'status' => $string, 'metaTitle' => $nullable, 'metaDescription' => $nullable, 'version' => $integer, 'slugLocked' => $boolean, 'relatedIds' => ['type' => 'array', 'items' => $integer], 'headings' => $headings, 'publicUrl' => $nullable, 'adminEditUrl' => $string], ['id', 'sectionId', 'title', 'slug', 'description', 'body', 'position', 'status', 'metaTitle', 'metaDescription', 'version', 'slugLocked', 'relatedIds', 'headings', 'publicUrl']);
        }
        $item = match ($tool) {
            'docs_list_sections' => self::object(['id' => $integer, 'title' => $string, 'slug' => $string, 'description' => $string, 'position' => $integer], ['id', 'title', 'slug', 'description', 'position']),
            'docs_search' => self::object(['id' => $integer, 'title' => $string, 'slug' => $string, 'description' => $string, 'status' => $string, 'sectionId' => $integer, 'publicUrl' => $nullable, 'adminEditUrl' => $string], ['id', 'title', 'slug', 'description', 'status', 'sectionId', 'publicUrl']),
            default => self::object(['sourceId' => $integer, 'targetId' => $integer, 'type' => $string, 'fragment' => $string, 'position' => $integer, 'sourceTitle' => $string, 'targetTitle' => $string], ['sourceId', 'targetId', 'type', 'fragment', 'position', 'sourceTitle', 'targetTitle']),
        };

        return self::object(['items' => ['type' => 'array', 'items' => $item], 'page' => $integer, 'pageSize' => ['const' => 20], 'total' => $integer, 'hasNext' => $boolean], ['items', 'page', 'pageSize', 'total', 'hasNext']);
    }

    /** @param array<string,mixed> $properties
     * @param list<string> $required
     *
     * @return array<string,mixed> */
    private static function object(array $properties, array $required = []): array
    {
        return ['type' => 'object', 'properties' => $properties, 'required' => $required, 'additionalProperties' => false];
    }

    /** @param array<string,mixed> $args */
    public static function assertIntegerTypes(array $args): void
    {
        foreach (['id', 'expectedVersion', 'sectionId', 'position'] as $key) {
            if (array_key_exists($key, $args) && !is_int($args[$key])) {
                throw new DocToolFailure('VALIDATION_FAILED', 'Ожидается целое JSON-число.');
            }
        }
        if (array_key_exists('page', $args)) {
            if (is_array($args['page'])) {
                self::assertIntegerTypes($args['page']);
            } elseif (!is_int($args['page'])) {
                throw new DocToolFailure('VALIDATION_FAILED', 'Ожидается целый номер страницы.');
            }
        }
        foreach ($args['relatedIds'] ?? [] as $id) {
            if (!is_int($id)) {
                throw new DocToolFailure('VALIDATION_FAILED', 'Ожидается целый ID.');
            }
        }
    }
}
