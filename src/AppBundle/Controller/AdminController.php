<?php
namespace AppBundle\Controller;

use JavierEguiluz\Bundle\EasyAdminBundle\Controller\AdminController as BaseAdminController;
use AppBundle\Entity\User;

class AdminController extends BaseAdminController
{
    public function prePersistEntity($entity) {
        if($entity instanceof User) {
            $this->encodePassword($entity);
        }
    }

    public function preUpdateEntity($entity) {
        if($entity instanceof User) {
            $this->encodePassword($entity);
        }
    }

    public function encodePassword($entity) {
        $plainPassword = $entity->getPlainPassword();

        if(!empty($plainPassword)) {
            $encoder = $this->get('security.password_encoder');
            $password = $encoder->encodePassword($entity,$plainPassword);
            $entity->setPassword($password);
            $entity->setPlainPassword('');
        }
    }
}
