<?php

namespace App\Form;

use App\Entity\Profils;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Url;

class ProfileEditorType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder

            // =========================
            // INFORMATIONS UTILISATEUR
            // =========================

            ->add('firstName', null, [
                'label' => 'Prénom',
                'mapped' => false,
                'required' => true,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Votre prénom',
                    'autocomplete' => 'given-name',
                ],
                'constraints' => [
                    new NotBlank(
                        message: 'Le prénom est obligatoire.'
                    ),
                    new Length(
                        min: 2,
                        max: 50,
                        minMessage: 'Le prénom doit contenir au moins {{ limit }} caractères.',
                        maxMessage: 'Le prénom ne peut pas dépasser {{ limit }} caractères.'
                    ),
                ],
            ])

            ->add('lastName', null, [
                'label' => 'Nom',
                'mapped' => false,
                'required' => true,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Votre nom',
                    'autocomplete' => 'family-name',
                ],
                'constraints' => [
                    new NotBlank(
                        message: 'Le nom est obligatoire.'
                    ),
                    new Length(
                        min: 2,
                        max: 50,
                        minMessage: 'Le nom doit contenir au moins {{ limit }} caractères.',
                        maxMessage: 'Le nom ne peut pas dépasser {{ limit }} caractères.'
                    ),
                ],
            ])

            ->add('email', EmailType::class, [
                'label' => 'Adresse email',
                'mapped' => false,
                'required' => true,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'vous@exemple.com',
                    'autocomplete' => 'email',
                ],
                'constraints' => [
                    new NotBlank(
                        message: 'L’adresse email est obligatoire.'
                    ),
                    new Email(
                        message: 'Veuillez saisir une adresse email valide.'
                    ),
                    new Length(
                        max: 180,
                        maxMessage: 'L’adresse email ne peut pas dépasser {{ limit }} caractères.'
                    ),
                ],
            ])

            // =========================
            // PROFIL
            // =========================

            ->add('phone', TelType::class, [
                'label' => 'Téléphone',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => '+33 6 12 34 56 78',
                    'autocomplete' => 'tel',
                ],
                'constraints' => [
                    new Length(
                        max: 20,
                        maxMessage: 'Le numéro de téléphone ne peut pas dépasser {{ limit }} caractères.'
                    ),
                ],
            ])

            ->add('city', null, [
                'label' => 'Ville',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Toulouse',
                    'autocomplete' => 'address-level2',
                ],
                'constraints' => [
                    new Length(
                        max: 100,
                        maxMessage: 'La ville ne peut pas dépasser {{ limit }} caractères.'
                    ),
                ],
            ])

            // =========================
            // CV
            // =========================

            ->add('defaultCv', FileType::class, [
                'label' => 'CV par défaut',
                'required' => false,
                'mapped' => false,
                'attr' => [
                    'class' => 'form-control',
                    'accept' => '.pdf,.doc,.docx',
                ],
                'constraints' => [
                    new File(
                        maxSize: '5M',
                        mimeTypes: [
                            'application/pdf',
                            'application/msword',
                            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                        ],
                        mimeTypesMessage: 'Veuillez sélectionner un fichier PDF, DOC ou DOCX.',
                        maxSizeMessage: 'Le fichier ne peut pas dépasser {{ limit }}.'
                    ),
                ],
            ])

            // =========================
            // LETTRE DE MOTIVATION
            // =========================

            ->add('defaultLetter', FileType::class, [
                'label' => 'Lettre de motivation par défaut',
                'required' => false,
                'mapped' => false,
                'attr' => [
                    'class' => 'form-control',
                    'accept' => '.pdf,.doc,.docx',
                ],
                'constraints' => [
                    new File(
                        maxSize: '5M',
                        mimeTypes: [
                            'application/pdf',
                            'application/msword',
                            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                        ],
                        mimeTypesMessage: 'Veuillez sélectionner un fichier PDF, DOC ou DOCX.',
                        maxSizeMessage: 'Le fichier ne peut pas dépasser {{ limit }}.'
                    ),
                ],
            ])

            // =========================
            // LINKEDIN
            // =========================

            ->add('linkedin', null, [
                'label' => 'LinkedIn',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'https://www.linkedin.com/in/votre-profil',
                    'autocomplete' => 'url',
                ],
                'constraints' => [
                    new Length(
                        max: 255,
                        maxMessage: 'L’adresse LinkedIn ne peut pas dépasser {{ limit }} caractères.'
                    ),
                    new Url(
                        message: 'Veuillez saisir une URL LinkedIn valide.'
                    ),
                ],
            ])

            // =========================
            // SITE WEB
            // =========================

            ->add('website', null, [
                'label' => 'Site web',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'https://www.exemple.com',
                    'autocomplete' => 'url',
                ],
                'constraints' => [
                    new Length(
                        max: 255,
                        maxMessage: 'L’adresse du site web ne peut pas dépasser {{ limit }} caractères.'
                    ),
                    new Url(
                        message: 'Veuillez saisir une URL valide.'
                    ),
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Profils::class,
        ]);
    }
}
