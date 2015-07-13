<?php
/**
 * Created by PhpStorm.
 * User: joe
 * Date: 7/9/15
 * Time: 2:13 PM
 */

namespace Netgen\LiveVotingBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;


class PresentationUserType extends AbstractType

{

    public function buildForm (FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('presenterName')
            ->add('presenterSurname')
            ->add('presentationName')
            ->add('votingEnabled')
            ->add('image', 'file', array(
                'data_class' => null,
                'required' => false
            ))
            ->add('begin')
            ->add('end')
            ->add('event')
        ;
    }

    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'Netgen\LiveVotingBundle\Entity\Presentation',
            'attr' => array('class'=>'form-horizontal', 'role'=>'form')
        ));
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'netgen_livevotingbundle_presentation';
    }

}