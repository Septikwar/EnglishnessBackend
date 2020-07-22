<?php


namespace App\Form;


use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ManyToOneEntityType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $repository = $options['em']->getRepository($options['data_class']);

        $builder
            ->add($options['id_field'])
            ->setDataMapper(new ManyToOneEntityDataMapper($repository, $options['id_field']));
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'allow_extra_fields' => true,
            'id_field' => 'id'
        ]);

        $resolver->setRequired('em');
        $resolver->setAllowedTypes('em', ['Doctrine\Common\Persistence\ObjectManager']);
    }
}