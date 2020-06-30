<?php

namespace AdrienDupuis\EzPlatformAdminBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class IdentificationType extends AbstractType
{
    /** @var TranslatorInterface */
    private $translator;

    public function __construct(TranslatorInterface $translator)
    {
        //parent::__construct(); // There is no parent constructor.
        $this->translator = $translator;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('id', TextType::class, [
                'required' => true,
                'label' => 'identifier.label',
                //'help' => 'identifier.help', // Not automatically extracted nor translated
                'help' => $this->translator->trans('identifier.help', [], 'ad_admin_identification'),
                'translation_domain' => 'ad_admin_identification',
            ])
            ->add('identify', SubmitType::class, [
                'label' => 'identify',
                'translation_domain' => 'ad_admin_identification',
                'attr' => [
                    'class' => 'btn-secondary',
                ],
            ])
        ;
    }

    public function getBlockPrefix()
    {
        return '';
    }
}
