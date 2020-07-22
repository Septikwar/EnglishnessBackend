<?php


namespace App\Form;


use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ManyToManyEntityType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $repository = $options['em']->getRepository($options['class']);

        $builder
            ->addViewTransformer(new ManyToManyEntityViewTransformer($repository, $options['id_field']))
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'compound' => false,
            'multiple' => true,
            'allow_extra_fields' => true,
            'class' => '',
            'id_field' => 'id',
        ]);

        $resolver->setRequired('class');
        $resolver->setRequired('em');
        $resolver->setAllowedTypes('em', ['Doctrine\Common\Persistence\ObjectManager']);
    }
}