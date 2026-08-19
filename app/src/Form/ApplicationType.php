<?php

namespace App\Form;

use App\Entity\Applications;
use App\Entity\CompanyContacts;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\NotBlank;

class ApplicationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder

            /**
             * Contact entreprise
             */
            ->add('contact', EntityType::class, [
                'class' => CompanyContacts::class,
                'choices' => $options['contacts'],
                'choice_label' => function (CompanyContacts $contact): string {
                    return $contact->getEmail();
                },
                'label' => 'Contact',
                'placeholder' => 'Choisir un contact',
                'mapped' => false,
                'required' => true,
                'attr' => [
                    'class' => 'form-select',
                ] ,
                'constraints' => [
                    new NotBlank(
                        message: 'Veuillez choisir un contact.'
                    ),
                ],
            ])


            /**
             * CV existant dans le profil
             */
            ->add('defaultCv', ChoiceType::class, [
                'label' => 'Choisir mon CV',
                'choices' => $options['profilCv']
                    ? [
                        $options['profilCv'] => $options['profilCv'],
                    ]
                    : [],
                'placeholder' => 'Choisir un CV existant',
                'mapped' => false,
                'required' => false,
                'attr' => [
                    'class' => 'form-select',
                ] ,
            ])


            /**
             * Nouveau CV
             */
            ->add('cv', FileType::class, [
                'label' => 'Ou envoyer un nouveau CV',
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    new File(
                        maxSize: '5M',
                        mimeTypes: [
                            'application/pdf',
                            'application/msword',
                            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                        ],
                        mimeTypesMessage: 'Veuillez envoyer un fichier PDF, DOC ou DOCX.'
                    ),
                ],
            ])


            /**
             * Lettre existante dans le profil
             */
            ->add('defaultLetter', ChoiceType::class, [
                'label' => 'Choisir ma lettre',
                'choices' => $options['profilLetter']
                    ? [
                        $options['profilLetter'] => $options['profilLetter'],
                    ]
                    : [],
                'placeholder' => 'Choisir une lettre existante',
                'mapped' => false,
                'required' => false,
                'attr' => [
                    'class' => 'form-select',
                ] ,
            ])


            /**
             * Nouvelle lettre
             */
            ->add('lettreMotivation', FileType::class, [
                'label' => 'Ou envoyer une nouvelle lettre de motivation',
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    new File(
                        maxSize: '5M',
                        mimeTypes: [
                            'application/pdf',
                            'application/msword',
                            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                        ],
                        mimeTypesMessage: 'Veuillez envoyer un fichier PDF, DOC ou DOCX.'
                    ),
                ],
            ])


            /**
             * Message rapide
             * Non sauvegardé en base
             */
            ->add('message', TextareaType::class, [
                'label' => 'Message rapide',
                'mapped' => false,
                'required' => false,
                'attr' => [
                    'rows' => 5,
                ],
                'data' => "Bonjour,
Admis en BTS SIO – option SISR à YNOV Campus pour la rentrée 2026/2027, je suis à la recherche d’une alternance dans le domaine de l’administration systèmes et réseaux.
Je me permets donc de vous adresser ma candidature spontanée. Vous trouverez en pièces jointes mon CV ainsi que ma lettre de motivation.
Je reste à votre disposition pour échanger avec vous.
Cordialement,

                ",
            ]);
    }


    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Applications::class,

            'contacts' => [],

            'profilCv' => null,

            'profilLetter' => null,
        ]);


        $resolver->setAllowedTypes('contacts', 'iterable');

        $resolver->setAllowedTypes('profilCv', [
            'null',
            'string',
        ]);

        $resolver->setAllowedTypes('profilLetter', [
            'null',
            'string',
        ]);
    }
}
