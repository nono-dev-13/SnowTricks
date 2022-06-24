<?php

namespace App\Form;

use App\Entity\Article;
use App\Entity\Category;
use App\Entity\Video;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ArticleFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class,[
                'label' => 'Nom de la figure'
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description de la figure'
            ])
            ->add('categories', EntityType::class,[
                'class' => Category::class,
                'required' => true,
                'multiple' => true,
                'expanded' => true,
                'mapped' => true,
                'by_reference' => false,
                'choice_label' => 'name',
            ])
            ->add('image', FileType::class,[
                'label' => 'Images',
                'multiple' => true,
                'mapped' => false,
                'required' => true,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Article::class,
        ]);
    }
}
