<?php


namespace App\Form;

use App\Entity\File;
use Doctrine\DBAL\Types\TextType;
use RuntimeException;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class FileType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        if ($options['em'] !== null) {
            $em = $options['em'];
        } else {
            throw new RuntimeException('em must be set');
        }

        $builder
            ->add('url')
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => File::class,
            'csrf_protection' => false,
            'em' => null,
            'allow_extra_fields' => true
        ]);
    }
}