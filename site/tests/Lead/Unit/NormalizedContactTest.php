<?php

declare(strict_types=1);

namespace App\Tests\Lead\Unit;

use App\Lead\ValueObject\ContactType;
use App\Lead\ValueObject\NormalizedContact;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class NormalizedContactTest extends TestCase
{
    /**
     * @return iterable<string, array{string, string, ContactType}>
     */
    public static function contacts(): iterable
    {
        yield 'email' => ['  Ivan.Petrov@Mail.RU ', 'ivan.petrov@mail.ru', ContactType::EMAIL];
        yield 'телефон через 8' => ['8 (900) 123-45-67', '+79001234567', ContactType::PHONE];
        yield 'телефон через +7' => ['+7 900 123 45 67', '+79001234567', ContactType::PHONE];
        yield 'телефон 10 цифр' => ['9001234567', '+79001234567', ContactType::PHONE];
        yield 'иностранный телефон' => ['+375 29 123-45-67', '+375291234567', ContactType::PHONE];
        yield 'иностранный 10 цифр с плюсом' => ['+49 30 1234567', '+49301234567', ContactType::PHONE];
        yield '8 с плюсом -- не российский' => ['+8 900 123 45 67', '+89001234567', ContactType::PHONE];
        yield 'telegram' => ['@VashFindir_Ru', '@vashfindir_ru', ContactType::TELEGRAM];
        yield 'прочее' => ['пишите в ВК', 'пишите в вк', ContactType::OTHER];
    }

    #[DataProvider('contacts')]
    public function testNormalizes(string $raw, string $value, ContactType $type): void
    {
        $contact = NormalizedContact::fromRaw($raw);

        self::assertSame($value, $contact->value);
        self::assertSame($type, $contact->type);
    }
}
