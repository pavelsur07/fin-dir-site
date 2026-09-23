<?php

declare(strict_types=1);

namespace App\Publication\Form;

use App\Publication\DTO\PostInput;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotNull;

/**
 * @extends AbstractType<PostInput>
 */
final class PostType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'label' => 'Заголовок',
                'empty_data' => '',
            ])
            ->add('slug', TextType::class, [
                'label' => 'Адрес (slug)',
                'required' => false,
                // Опубликованная статья: поле не принимает ввод, в DTO остаётся текущий slug.
                'disabled' => $options['slug_locked'],
                'help' => match (true) {
                    $options['slug_locked'] => 'Статья публиковалась: адрес больше не меняется.',
                    $options['is_edit'] => 'Латиница, цифры и дефисы. Пусто — адрес останется прежним.',
                    default => 'Латиница, цифры и дефисы. Пусто — сгенерируется из заголовка.',
                },
            ])
            ->add('excerpt', TextareaType::class, [
                'label' => 'Анонс',
                'required' => false,
                'attr' => ['rows' => 3],
            ])
            ->add('body', TextareaType::class, [
                'label' => 'Текст (Markdown)',
                'empty_data' => '',
                'attr' => ['rows' => 24],
            ])
            ->add('metaTitle', TextType::class, [
                'label' => 'SEO: title',
                'required' => false,
                'help' => 'До 70 символов. Пусто — берётся заголовок.',
            ])
            ->add('metaDescription', TextareaType::class, [
                'label' => 'SEO: description',
                'required' => false,
                'attr' => ['rows' => 2],
                'help' => 'До 170 символов. Пусто — берётся анонс.',
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
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => PostInput::class,
            'slug_locked' => false,
            'is_edit' => false,
        ]);
        $resolver->setAllowedTypes('slug_locked', 'bool');
        $resolver->setAllowedTypes('is_edit', 'bool');
    }
}
