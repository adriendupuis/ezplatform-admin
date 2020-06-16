<?php

namespace AdrienDupuis\EzPlatformAdminBundle\Form\Type;

use EzSystems\EzPlatformAdminUi\Form\Type\ContentType\ContentTypeChoiceType;
use EzSystems\EzPlatformAdminUi\Form\Type\Language\LanguageChoiceType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class ExampleFinderType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('content_type', ContentTypeChoiceType::class, [
                'required' => true,
                'label' => 'common.content_type',
                'placeholder' => 'example_finder.select_content_type',
                'translation_domain' => 'ad_admin_content_usage',
            ])
            ->add('language_code', LanguageChoiceType::class, [
                'required' => false,
                'label' => 'common.language',
                'placeholder' => 'example_finder.all_languages',
                'translation_domain' => 'ad_admin_content_usage',
            ])
        ;
    }
}
