<?php

declare(strict_types=1);

namespace App\Tests\Lead\Unit;

use App\Lead\DTO\LeadSubmission;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validation;

final class LeadSubmissionTest extends TestCase
{
    #[DataProvider('contacts')]
    public function testContactMustMatchSelectedType(?string $type, string $contact, ?string $expectedError): void
    {
        $submission = $this->submission(['contact_type' => $type, 'contact' => $contact]);

        $errors = [];
        foreach (Validation::createValidatorBuilder()->enableAttributeMapping()->getValidator()->validate($submission) as $violation) {
            $errors[$violation->getPropertyPath()] = (string) $violation->getMessage();
        }

        self::assertSame($expectedError, $errors['contact'] ?? null);
    }

    /**
     * @return iterable<string, array{?string, string, ?string}>
     */
    public static function contacts(): iterable
    {
        $phone = 'Укажите телефон в формате +7 900 000-00-00.';
        $telegram = 'Укажите имя пользователя с @, без пробелов, от 5 символов.';

        yield 'phone ok' => ['phone', '+7 900 123-45-67', null];
        yield 'phone 8-format ok' => ['phone', '8 (900) 123-45-67', null];
        yield 'phone too short' => ['phone', '+7 900 12', $phone];
        yield 'phone given username' => ['phone', '@anna_sokolova', $phone];
        yield 'phone given email' => ['phone', 'anna@example.com', $phone];
        yield 'telegram ok' => ['telegram', '@anna_sokolova', null];
        yield 'telegram without at' => ['telegram', 'anna_sokolova', $telegram];
        yield 'telegram with space' => ['telegram', '@anna sokolova', $telegram];
        yield 'telegram too short' => ['telegram', '@anna', $telegram];
        yield 'telegram given phone' => ['telegram', '+7 900 123-45-67', $telegram];
        yield 'no type: any contact' => [null, 'anna@example.com', null];
    }

    public function testUnknownContactTypeIsRejectedOnItsOwnField(): void
    {
        $violations = Validation::createValidatorBuilder()->enableAttributeMapping()->getValidator()->validate($this->submission(['contact_type' => 'fax']));

        $paths = array_map(static fn ($violation): string => $violation->getPropertyPath(), iterator_to_array($violations));
        self::assertContains('contactType', $paths);
    }

    /**
     * @param array<string, mixed> $override
     */
    private function submission(array $override): LeadSubmission
    {
        return LeadSubmission::fromFormData(array_replace([
            'form' => 'excursion',
            'name' => 'Анна',
            'contact' => '+7 900 123-45-67',
            'agreement' => 'on',
            'answers' => ['role' => 'owner', 'turnover' => 'under_2m'],
        ], $override));
    }
}
