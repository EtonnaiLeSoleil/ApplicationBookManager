<?php

namespace App\Form;

use App\Entity\Book;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Image;

class BookType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title')
            ->add('author')
            ->add('description')
            ->add('genre', ChoiceType::class, [
                'choices' => [
                    'Roman'    => 'roman',
                    'Science-fiction' => 'sf',
                    'Histoire' => 'histoire',
                    'Poésie'   => 'poesie',
                ],
                'placeholder' => 'Choisir un genre',
            ])
            ->add('coverFile', FileType::class, [
                'label' => 'Image de couverture (JPG ou PNG)',
                'mapped' => false, // pas lié directement à l’entité
                'required' => false,
                'constraints' => [
                    new Image([
                        'mimeTypes' => ['image/jpeg', 'image/png'],
                        'mimeTypesMessage' => 'Veuillez choisir une image JPG ou PNG',
                        'maxSize' => '5M',
                    ])
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Book::class,
        ]);
    }
}
