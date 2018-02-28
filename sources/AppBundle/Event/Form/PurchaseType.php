<?php

namespace AppBundle\Event\Form;

use Afup\Site\Utils\Pays;
use AppBundle\Event\Model\Invoice;
use AppBundle\Event\Model\Ticket;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class PurchaseType extends AbstractType
{

    const MAX_NB_PERSONNES = 11;

    /**
     * @var Pays
     */
    private $country;

    private $tokenStorage;

    public function __construct(Pays $pays, TokenStorageInterface $tokenStorage)
    {
        $this->country = $pays;
        $this->tokenStorage = $tokenStorage;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $nbPersonnesChoices = [];
        for ($i=1; $i<=self::MAX_NB_PERSONNES; $i++) {
            $nbPersonnesChoices[$i] = $i;
        }

        $builder
            ->add('nbPersonnes', ChoiceType::class, [
                'choices' => $nbPersonnesChoices,
                'multiple' => false,
                'expanded' => false,
                'mapped' => false,
                'data' => 1
            ])
            ->add('tickets', CollectionType::class, [
                'entry_type' => TicketType::class,
                'prototype' => true,
                'allow_add'    => true,
                'entry_options' => [
                    'event_id' => $options['event_id'],
                    'member_type' => $options['member_type'],
                    'is_cfp_submitter' => $options['is_cfp_submitter'],
                ]
            ])
            ->add('paymentType', ChoiceType::class, [
                'label' => 'Règlement',
                'choices' => [
                    'Carte bancaire' => Ticket::PAYMENT_CREDIT_CARD,
                    'Virement' => Ticket::PAYMENT_BANKWIRE
                ],
                'expanded' => true,
                'multiple' => false
            ])
            ->add('firstname', TextType::class)
            ->add('lastname', TextType::class)
            ->add('company', TextType::class, ['required' => false])
            ->add('email', EmailType::class)
            ->add('address', TextType::class)
            ->add('zipcode', TextType::class, ['label' => 'Zip code'])
            ->add('city', TextType::class)
            ->add('countryId', ChoiceType::class, [
                'label' => 'Country',
                'choices' => array_flip($this->country->obtenirPays())
            ])
            ->add('companyCitation', CheckboxType::class, [
                'label'    => 'J\'accepte que ma société soit citée comme participant à la conférence',
                'required' => false,
                'mapped' => false
            ])
            ->add('newsletterAfup', CheckboxType::class, [
                'label' => 'Je souhaite être tenu au courant des rencontres de l\'AFUP sur des sujets afférents à PHP',
                'required' => false,
                'mapped' => false
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Invoice::class,
            'member_type' => TicketType::MEMBER_NOT,
            'is_cfp_submitter' => false,
            'event_id' => null,
            'cascade_validation' => true,
            'validation_groups' => function () {
                $groups = ['Default'];

                $user = $this->tokenStorage->getToken()->getUser();
                if (is_object($user) === false) {
                    $groups[] = 'not_logged_in';
                } elseif ((int) $user->getCompanyId() === 0) {
                    $groups[] = 'personal';
                } else {
                    $groups[] = 'corporate';
                }

                return $groups;
            },
        ]);
    }
}
