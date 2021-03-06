<?php

namespace Netgen\LiveVotingBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class UserType extends AbstractType
{
        /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('email', 'email', array('label'=>'User email'))
            //->add('user', 'entity', array('class'=>'Netgen\LiveVotingBundle\Entity\User', 'enabled'=>true))
        ;
    }
    
    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'Netgen\LiveVotingBundle\Entity\User',
            'attr' => array('class'=>'form-horizontal', 'role'=>'form')
        ));
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'netgen_livevotingbundle_user';
    }
}
