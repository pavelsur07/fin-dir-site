<?php

declare(strict_types=1);

namespace App\ClientCase\Form;

use App\ClientCase\DTO\ClientCaseInput;
use App\ClientCase\ValueObject\CaseIndustry;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Exception\TransformationFailedException;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotNull;

/**
 * @extends AbstractType<ClientCaseInput>
 */
final class ClientCaseType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, ['label' => 'Заголовок', 'empty_data' => ''])
            ->add('slug', TextType::class, [
                'label' => 'Адрес (slug)',
                'empty_data' => '',
                // Опубликованный кейс: поле не принимает ввод, в DTO остаётся текущий slug.
                'disabled' => $options['slug_locked'],
                'help' => $options['slug_locked']
                    ? 'Кейс публиковался: адрес больше не меняется.'
                    : 'Латиница, цифры и дефисы. Станет ссылкой /cases/адрес.',
            ])
            ->add('industry', EnumType::class, [
                'label' => 'Отрасль',
                'class' => CaseIndustry::class,
                'choice_label' => static fn (CaseIndustry $industry): string => $industry->label(),
                'placeholder' => 'Выберите отрасль',
            ])
            ->add('problem', TextareaType::class, [
                'label' => 'Была проблема',
                'empty_data' => '',
                'attr' => ['rows' => 4],
                'help' => 'Показывается в карточке кейса.',
            ])
            ->add('resultValue', TextType::class, [
                'label' => 'Результат: число',
                'empty_data' => '',
                'help' => 'Например, «3 200 000 ₽» или «+9 %».',
            ])
            ->add('resultLabel', TextType::class, [
                'label' => 'Результат: подпись',
                'empty_data' => '',
                'help' => 'Например, «высвобождено из остатков».',
            ])
            ->add('tags', TextType::class, [
                'label' => 'Теги',
                'required' => false,
                'help' => 'Через запятую: ДДС, ABC-анализ.',
            ])
            ->add('task', TextareaType::class, [
                'label' => 'Задача (для страницы кейса)',
                'required' => false,
                'attr' => ['rows' => 4],
                'help' => 'Пусто — на странице кейса показывается «Была проблема».',
            ])
            ->add('steps', TextareaType::class, [
                'label' => 'Что сделали',
                'required' => false,
                'attr' => ['rows' => 5],
                'help' => 'По одному шагу в строке.',
            ])
            ->add('metrics', TextareaType::class, [
                'label' => 'Метрики',
                'required' => false,
                'attr' => ['rows' => 4],
                'help' => 'По одной в строке: «значение | подпись». Пусто — на странице показывается результат кейса.',
                'invalid_message' => 'Метрика должна быть в виде «значение | подпись».',
            ])
            ->add('source', TextType::class, [
                'label' => 'Источник данных',
                'required' => false,
                'help' => 'Например, «Демо-данные».',
            ])
            ->add('featured', CheckboxType::class, [
                'label' => 'Главный кейс',
                'required' => false,
                'help' => 'Показывается развёрнуто над сеткой. Главным остаётся один кейс: прежний перестанет быть главным.',
            ])
            ->add('version', HiddenType::class, [
                // На редактировании версия обязательна: без неё не поймать параллельную правку.
                'constraints' => $options['is_edit'] ? [new NotNull(message: 'Форма устарела. Обновите страницу.')] : [],
            ]);

        // Скрытое поле приходит строкой, а в DTO версия -- int.
        $builder->get('version')->addModelTransformer(new CallbackTransformer(
            static fn (?int $version): ?string => null === $version ? null : (string) $version,
            static fn (?string $version): ?int => null === $version || '' === $version ? null : (int) $version,
        ));

        $builder->get('tags')->addModelTransformer(new CallbackTransformer(
            static fn (array $tags): string => implode(', ', $tags),
            static fn (?string $text): array => self::splitList((string) $text, ','),
        ));
        $builder->get('steps')->addModelTransformer(new CallbackTransformer(
            static fn (array $steps): string => implode("\n", $steps),
            static fn (?string $text): array => self::splitList((string) $text, "\n"),
        ));
        $builder->get('metrics')->addModelTransformer(new CallbackTransformer(
            static fn (array $metrics): string => implode("\n", array_map(static fn (array $metric): string => $metric['value'].' | '.$metric['label'], $metrics)),
            static fn (?string $text): array => self::parseMetrics((string) $text),
        ));
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ClientCaseInput::class,
            'slug_locked' => false,
            'is_edit' => false,
        ]);
        $resolver->setAllowedTypes('slug_locked', 'bool');
        $resolver->setAllowedTypes('is_edit', 'bool');
    }

    /**
     * @param non-empty-string $separator
     *
     * @return list<string>
     */
    private static function splitList(string $text, string $separator): array
    {
        $items = array_map('trim', explode($separator, str_replace("\r", '', $text)));

        return array_values(array_filter($items, static fn (string $item): bool => '' !== $item));
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    private static function parseMetrics(string $text): array
    {
        $metrics = [];
        foreach (self::splitList($text, "\n") as $line) {
            $parts = array_map('trim', explode('|', $line));
            if (2 !== \count($parts) || '' === $parts[0] || '' === $parts[1]) {
                throw new TransformationFailedException(\sprintf('Invalid metric line "%s".', $line), 0, null, 'Метрика должна быть в виде «значение | подпись».');
            }
            $metrics[] = ['value' => $parts[0], 'label' => $parts[1]];
        }

        return $metrics;
    }
}
