<?php

declare(strict_types=1);

namespace App\Form\Type\Entity;

use App\Entity\Wish;
use App\Entity\Wishlist;
use App\Enum\CurrencyEnum;
use App\Enum\VisibilityEnum;
use App\Form\DataTransformer\FileToMediumTransformer;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Class WishType
 *
 * @package App\Form\Type\Entity
 */
class WishType extends AbstractType
{
    /**
     * @var EntityManagerInterface
     */
    private EntityManagerInterface $em;

    /**
     * @var FileToMediumTransformer
     */
    private FileToMediumTransformer $fileToMediumTransformer;

    /**
     * WishType constructor.
     * @param EntityManagerInterface $em
     * @param FileToMediumTransformer $fileToMediumTransformer
     */
    public function __construct(EntityManagerInterface $em, FileToMediumTransformer $fileToMediumTransformer)
    {
        $this->em = $em;
        $this->fileToMediumTransformer = $fileToMediumTransformer;
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('name', TextType::class, [
                'attr' => ['length' => 255],
                'required' => true,
            ])
            ->add('url', TextType::class, [
                'required' => false,
            ])
            ->add('price', TextType::class, [
                'required' => false,
            ])
            ->add('currency', ChoiceType::class, [
                'choices' => \array_flip(CurrencyEnum::getCurrencyLabels()),
                'expanded' => false,
                'multiple' => false,
                'required' => false,
            ])
            ->add('comment', TextareaType::class, [
                'required' => false,
            ])
            ->add(
                $builder->create('image', FileType::class, [
                    'required' => false,
                    'label' => false,
                ])->addModelTransformer($this->fileToMediumTransformer)
            )
            ->add('wishlist', EntityType::class, [
                'class' => Wishlist::class,
                'choice_label' => 'name',
                'choices' => $this->em->getRepository(Wishlist::class)->findAll(),
                'expanded' => false,
                'multiple' => false,
                'choice_name' => null,
                'required' => true,
            ])
            ->add('visibility', ChoiceType::class, [
                'choices' => \array_flip(VisibilityEnum::getVisibilityLabels()),
                'required' => false,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Wish::class,
        ]);
    }
}
