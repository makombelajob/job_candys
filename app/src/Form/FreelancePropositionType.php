<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class FreelancePropositionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('siret', HiddenType::class)

            ->add('email', ChoiceType::class, [
                'label' => 'Destinataire',
                'choices' => $options['contact_choices'],
                'placeholder' => 'Sélectionner une adresse email',
                'required' => true,
                'attr' => [
                    'class' => 'form-select',
                ],
            ])

            ->add('subject', TextType::class, [
                'label' => 'Sujet',
                'required' => true,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Sujet de votre proposition',
                ],
            ])

            ->add('message', TextareaType::class, [
                'label' => 'Message',
                'required' => true,
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 10,
                    'placeholder' => 'Rédigez votre proposition...',
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'contact_choices' => [],
        ]);

        $resolver->setAllowedTypes('contact_choices', 'array');
    }
}