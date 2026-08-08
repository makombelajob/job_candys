<?php

namespace App\Form;

use App\Entity\Companies;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\Url;

class CompanyEditType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('fullName', null, [
                'label' => 'Nom de l’entreprise',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Nom de l’entreprise',
                ],
            ])

            ->add('siret', null, [
                'label' => 'SIRET',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Numéro SIRET',
                ],
            ])

            ->add('webSite', null, [
                'label' => 'Site web',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'https://www.exemple.com',
                ],
                'constraints' => [
                    new Length(
                        max: 100,
                        maxMessage: 'Le site web ne peut pas dépasser {{ limit }} caractères.'
                    ),
                    new Url(
                        message: 'Veuillez saisir une URL valide.'
                    ),
                ],
            ])

            ->add('carrePage', null, [
                'label' => 'Page Carré',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'https://...',
                ],
                'constraints' => [
                    new Length(
                        max: 100,
                        maxMessage: 'La page Carré ne peut pas dépasser {{ limit }} caractères.'
                    ),
                    new Url(
                        message: 'Veuillez saisir une URL valide.'
                    ),
                ],
            ])

            ->add('linkedin', null, [
                'label' => 'LinkedIn',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'https://www.linkedin.com/company/...',
                ],
                'constraints' => [
                    new Length(
                        max: 100,
                        maxMessage: 'LinkedIn ne peut pas dépasser {{ limit }} caractères.'
                    ),
                    new Url(
                        message: 'Veuillez saisir une URL LinkedIn valide.'
                    ),
                ],
            ])

            ->add('Phone', null, [
                'label' => 'Téléphone',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => '+33 6 00 00 00 00',
                ],
                'constraints' => [
                    new Length(
                        max: 20,
                        maxMessage: 'Le téléphone ne peut pas dépasser {{ limit }} caractères.'
                    ),
                ],
            ])

            ->add('address', null, [
                'label' => 'Adresse',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Adresse de l’entreprise',
                ],
            ])

            ->add('email', EmailType::class, [
                'label' => 'Email',
                'mapped' => false,
                'required' => true,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'contact@entreprise.com',
                    'autocomplete' => 'email',
                ],
                'constraints' => [
                    new Email(
                        message: 'Veuillez saisir une adresse email valide.'
                    ),
                    new Length(
                        max: 255,
                        maxMessage: 'L\'email ne peut pas dépasser {{ limit }} caractères.'
                    ),
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Companies::class,
        ]);
    }
}
