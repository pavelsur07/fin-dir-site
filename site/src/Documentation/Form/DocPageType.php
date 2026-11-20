<?php

declare(strict_types=1);

namespace App\Documentation\Form;

use App\Documentation\DTO\DocPageInput;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/** @extends AbstractType<DocPageInput> */
final class DocPageType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('title', TextType::class, ['empty_data' => '', 'label' => 'Название'])
            ->add('slug', TextType::class, ['empty_data' => '', 'label' => 'Адрес страницы', 'disabled' => $options['slug_locked'], 'help' => 'Латиница, цифры и дефисы. После публикации адрес сохраняется.'])
            ->add('description', TextareaType::class, ['empty_data' => '', 'label' => 'Краткое описание', 'required' => false])
            ->add('position', IntegerType::class, ['empty_data' => '0', 'label' => 'Порядок', 'help' => 'Меньшее число — раньше в списке.'])
            ->add('version', HiddenType::class);
        $builder->get('version')->addModelTransformer(new CallbackTransformer(
            static fn (?int $v): ?string => null === $v ? null : (string) $v,
            static fn (?string $v): ?int => null === $v || '' === $v ? null : (int) $v,
        ));
        $builder->add('sectionId', ChoiceType::class, ['empty_data' => '0', 'label' => 'Раздел', 'choices' => $options['sections'], 'placeholder' => 'Выберите раздел'])
            ->add('body', TextareaType::class, ['trim' => false, 'empty_data' => '', 'label' => 'Текст (Markdown)', 'required' => false, 'attr' => ['rows' => 18, 'data-doc-body' => '']])
            ->add('relatedLinks', TextareaType::class, ['empty_data' => '', 'label' => 'Читайте также', 'required' => false, 'help' => 'До пяти ссылок на страницы, каждая с новой строки. Используйте выбор страницы.', 'attr' => ['rows' => 5, 'data-doc-related' => '']])
            ->add('metaTitle', TextType::class, ['empty_data' => '', 'label' => 'SEO: заголовок', 'required' => false])
            ->add('metaDescription', TextareaType::class, ['empty_data' => '', 'label' => 'SEO: описание', 'required' => false]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => DocPageInput::class, 'slug_locked' => false, 'sections' => []]);
        $resolver->setAllowedTypes('slug_locked', 'bool');
        $resolver->setAllowedTypes('sections', 'array');
    }
}
