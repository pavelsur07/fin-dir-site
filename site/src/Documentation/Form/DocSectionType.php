<?php

declare(strict_types=1);

namespace App\Documentation\Form;

use App\Documentation\DTO\DocSectionInput;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/** @extends AbstractType<DocSectionInput> */
final class DocSectionType extends AbstractType
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
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => DocSectionInput::class, 'slug_locked' => false, 'sections' => []]);
        $resolver->setAllowedTypes('slug_locked', 'bool');
        $resolver->setAllowedTypes('sections', 'array');
    }
}
